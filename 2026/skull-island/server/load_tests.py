#!/usr/bin/env python3
"""Load testing helpers for skull-server.

Usage examples:
  python3 load_tests.py concur --host 127.0.0.1 --port 9888 --users 250
  python3 load_tests.py sustain --host 127.0.0.1 --port 9888 --duration 60 --rps 250
  python3 load_tests.py failures --host 127.0.0.1 --port 9888 --badge-count 50

This script provides three tests:
- concur: concurrent login throughput (GET /challenge + POST /login + GET /dashboard)
- sustain: sustained read load + spikes against /dashboard
- failures: failure/rate-limit resilience test (triggers blocks)

These tests are intended for pre-deploy load verification against a staging cluster.
"""
import argparse
import time
import random
import statistics
from concurrent.futures import ThreadPoolExecutor, as_completed
import requests

from auth import compute_authcode


def make_badge(i: int) -> str:
    # deterministic 16-char badge id
    return f"{i:010d}abcdef"[:16]


def login_flow(base_url: str, username: str, badge_id: str, timeout=5):
    s = requests.Session()
    try:
        r = s.get(f"{base_url}/challenge", timeout=timeout)
        if r.status_code != 200:
            return False, f'challenge status {r.status_code}'
        ch = r.json().get('challenge')
        if not ch:
            return False, 'no challenge'
        auth = compute_authcode(ch, badge_id, username)
        payload = {'username': username, 'badge_id': badge_id, 'authcode': auth}
        r2 = s.post(f"{base_url}/login", data=payload, timeout=timeout, allow_redirects=False)
        if r2.status_code in (302, 303):
            # follow to dashboard to verify
            r3 = s.get(f"{base_url}/dashboard", timeout=timeout)
            if r3.status_code == 200:
                return True, None
            return False, f'dashboard status {r3.status_code}'
        else:
            return False, f'login status {r2.status_code}'
    except Exception as e:
        return False, str(e)


def concurrent_challenge_test(base_url: str, concurrency: int = 100, timeout: int = 5):
    """Run concurrent GET /challenge requests and return (successes, failures_list)."""
    successes = 0
    failures = []
    def _one():
        s = requests.Session()
        try:
            r = s.get(f"{base_url}/challenge", timeout=timeout)
            if r.status_code != 200:
                return False, f'status={r.status_code}'
            data = r.json()
            if 'challenge' not in data:
                return False, 'no challenge key'
            return True, None
        except Exception as e:
            return False, str(e)

    with ThreadPoolExecutor(max_workers=concurrency) as ex:
        futures = [ex.submit(_one) for _ in range(concurrency)]
        for fut in as_completed(futures):
            ok, err = fut.result()
            if ok:
                successes += 1
            else:
                failures.append(err)
    return successes, failures


def check_dashboard_anonymous(base_url: str, timeout: int = 5):
    """Verify anonymous GET /dashboard is denied (redirects to login or shows login page)."""
    try:
        r = requests.get(f"{base_url}/dashboard", timeout=timeout, allow_redirects=True)
        # Redirect to login (302) or login page content indicates correct behavior
        if r.status_code == 302:
            return True, None
        if r.status_code == 200 and ('Skull Seal Login' in r.text or 'login' in r.url or '/login' in r.url):
            return True, None
        return False, f'unexpected status {r.status_code} url {r.url}'
    except Exception as e:
        return False, str(e)


def concurrent_login_throughput(base_url: str, users: int = 250, username: str = 'deckhand'):
    timings = []
    successes = 0
    failures = []
    with ThreadPoolExecutor(max_workers=min(users, 500)) as ex:
        futures = []
        for i in range(users):
            badge = make_badge(i)
            futures.append(ex.submit(_timed_login, base_url, username, badge))

        for fut in as_completed(futures):
            ok, elapsed, err = fut.result()
            timings.append(elapsed)
            if ok:
                successes += 1
            else:
                failures.append(err)

    if timings:
        p95 = statistics.quantiles(timings, n=100)[94]
        p99 = statistics.quantiles(timings, n=100)[98]
    else:
        p95 = p99 = 0

    return {'users': users, 'successes': successes, 'failures': len(failures), 'p95_s': p95, 'p99_s': p99, 'errors': failures[:5]}


def _timed_login(base_url, username, badge):
    start = time.time()
    ok, err = login_flow(base_url, username, badge)
    return ok, time.time() - start, err


def sustained_read_load(base_url: str, users: int = 250, duration: int = 60, rps: int = 250, spike_every: int = 15, spike_mul: int = 3):
    # Prepare sessions by logging in users
    sessions = []
    for i in range(users):
        badge = make_badge(i)
        s = requests.Session()
        r = s.get(f"{base_url}/challenge")
        ch = r.json().get('challenge')
        auth = compute_authcode(ch, badge, 'deckhand')
        s.post(f"{base_url}/login", data={'username': 'deckhand', 'badge_id': badge, 'authcode': auth}, allow_redirects=False)
        sessions.append(s)

    end = time.time() + duration
    stats = {'requests': 0, 'errors': 0}
    seq = 0
    while time.time() < end:
        batch = rps
        if spike_every and seq and seq % spike_every == 0:
            batch *= spike_mul
        seq += 1
        with ThreadPoolExecutor(max_workers=min(batch, 500)) as ex:
            futures = [ex.submit(_get_dashboard, sessions[i % len(sessions)], base_url) for i in range(batch)]
            for f in as_completed(futures):
                ok = f.result()
                stats['requests'] += 1
                if not ok:
                    stats['errors'] += 1
        time.sleep(1)

    return stats


def _get_dashboard(session, base_url):
    try:
        r = session.get(f"{base_url}/dashboard", timeout=5)
        return r.status_code == 200
    except Exception:
        return False


def failure_rate_resilience(base_url: str, badge_count: int = 50, attempts_per_badge: int = 6):
    """For each badge, perform attempts_per_badge failed logins quickly and assert the (attempts_per_badge)th is blocked (429)."""
    results = {'total': 0, 'blocked_seen': 0, 'errors': []}
    with ThreadPoolExecutor(max_workers=min(badge_count, 200)) as ex:
        futures = []
        for i in range(badge_count):
            badge = make_badge(100000 + i)
            futures.append(ex.submit(_attempt_failures_for_badge, base_url, badge, attempts_per_badge))

        for f in as_completed(futures):
            res = f.result()
            results['total'] += 1
            if res.get('blocked'):
                results['blocked_seen'] += 1
            if res.get('errors'):
                results['errors'].extend(res['errors'])

    return results


def _attempt_failures_for_badge(base_url, badge, attempts):
    s = requests.Session()
    errors = []
    blocked = False
    for i in range(attempts):
        # request fresh challenge to avoid replay
        try:
            r = s.get(f"{base_url}/challenge", timeout=5)
            ch = r.json().get('challenge')
            # wrong authcode intentionally
            bad_auth = 'AAAA-BBBB-CCCC-DDDD'
            r2 = s.post(f"{base_url}/login", data={'username': 'deckhand', 'badge_id': badge, 'authcode': bad_auth}, allow_redirects=False, timeout=5)
            if r2.status_code == 429:
                blocked = True
                break
        except Exception as e:
            errors.append(str(e))
    return {'blocked': blocked, 'errors': errors}


def main():
    parser = argparse.ArgumentParser()
    sub = parser.add_subparsers(dest='cmd')

    p1 = sub.add_parser('concur')
    p1.add_argument('--host', required=True)
    p1.add_argument('--port', required=True)
    p1.add_argument('--users', type=int, default=250)

    p2 = sub.add_parser('sustain')
    p2.add_argument('--host', required=True)
    p2.add_argument('--port', required=True)
    p2.add_argument('--duration', type=int, default=60)
    p2.add_argument('--rps', type=int, default=250)
    p2.add_argument('--users', type=int, default=250)

    p3 = sub.add_parser('failures')
    p3.add_argument('--host', required=True)
    p3.add_argument('--port', required=True)
    p3.add_argument('--badge-count', type=int, default=50)
    p3.add_argument('--attempts', type=int, default=6)

    args = parser.parse_args()
    if not args.cmd:
        parser.print_help()
        return

    base_url = f"http://{getattr(args, 'host')}:{getattr(args, 'port')}"

    if args.cmd == 'concur':
        print('Running concurrent challenge test...')
        succ, fails = concurrent_challenge_test(base_url, concurrency=args.users)
        print(f'challenge successes: {succ}/{args.users}, failures: {len(fails)}')
        if succ != args.users:
            print('Concurrent challenge test FAILED')

        print('Running concurrent login throughput test...')
        res = concurrent_login_throughput(base_url, users=args.users)
        print(res)

        print('Checking anonymous dashboard access...')
        ok, err = check_dashboard_anonymous(base_url)
        if ok:
            print('Anonymous dashboard correctly denied')
        else:
            print('Anonymous dashboard check FAILED:', err)
    elif args.cmd == 'sustain':
        print('Running sustained read load test...')
        res = sustained_read_load(base_url, users=args.users, duration=args.duration, rps=args.rps)
        print(res)
    elif args.cmd == 'failures':
        print('Running failure/rate-limit resilience test...')
        res = failure_rate_resilience(base_url, badge_count=args.badge_count, attempts_per_badge=args.attempts)
        print(res)


if __name__ == '__main__':
    main()
