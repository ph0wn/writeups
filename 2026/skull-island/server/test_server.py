import unittest
from unittest import mock
import sys
import os
import time

# Ensure we can import server and auth
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

import server

from server import app
import limit

TEST_BADGE_ID = '0123aa6789abcdef' # valid test badge_id


class FakeRedis:
    def __init__(self):
        self.store = {}
        self.expire_times = {}

    def _expired(self, key):
        et = self.expire_times.get(key)
        if et is None:
            return False
        if time.time() > et:
            # expire
            self.store.pop(key, None)
            self.expire_times.pop(key, None)
            return True
        return False

    def incr(self, key):
        if self._expired(key):
            pass
        val = int(self.store.get(key, 0)) + 1
        self.store[key] = val
        return val

    def expire(self, key, seconds):
        self.expire_times[key] = time.time() + seconds

    def set(self, key, val, ex=None):
        self.store[key] = val
        if ex is not None:
            self.expire_times[key] = time.time() + ex

    def delete(self, key):
        self.store.pop(key, None)
        self.expire_times.pop(key, None)

    def ttl(self, key):
        if self._expired(key):
            return -2
        et = self.expire_times.get(key)
        if et is None:
            return -1
        return int(et - time.time())

class TestServer(unittest.TestCase):
    
    def setUp(self):
        app.config['TESTING'] = True
        app.config['SECRET_KEY'] = 'test_secret'
        self.app = app.test_client()
        self.ctx = app.app_context()
        self.ctx.push()
        # Ensure the limit module uses a fake in-memory Redis for tests
        # (functions like get_block_ttl read `limit.redis_client`)
        limit.redis_client = FakeRedis()

    def tearDown(self):
        self.ctx.pop()

    def test_login_page_get(self):
        """Test that the login page renders correctly on GET."""
        response = self.app.get('/login')
        self.assertEqual(response.status_code, 200)
        self.assertIn(b'Skull Seal Login', response.data)

    def test_challenge_generation(self):
        """Test that a challenge can be requested."""
        response = self.app.get('/challenge')
        self.assertEqual(response.status_code, 200)
        self.assertTrue(response.is_json)
        data = response.get_json()
        self.assertIn('challenge', data)
        # Check session usage requires a context, which we have via self.app cookie handling in tests usually
        # but verifying the response is enough for basic connectivity

    @mock.patch('server.compute_authcode')
    def test_login_success(self, mock_compute):
        """Test successful login with mocked authcode."""
        # Setup session with a challenge
        with self.app.session_transaction() as sess:
            sess['challenge'] = 'TESTCHALLENGE'
            sess['challenge_time'] = 10000000000 # future proof

        mock_compute.return_value = 'AAAA-BBBB-CCCC-DDDD'

        response = self.app.post('/login', data={
            'username': 'deckhand',
            'badge_id': TEST_BADGE_ID,
            'authcode': 'AAAA-BBBB-CCCC-DDDD'
        }, follow_redirects=True)

        self.assertEqual(response.status_code, 200)
        # Should be on dashboard
        self.assertIn(b'Welcome to Your Treasure Map', response.data)

    def test_login_failure_no_challenge(self):
        """Test login fails when no challenge is present."""
        response = self.app.post('/login', data={
            'username': 'deckhand',
            'badge_id': TEST_BADGE_ID,
            'authcode': 'XXXX-XXXX-XXXX-XXXX'
        }, follow_redirects=True)
        
        self.assertEqual(response.status_code, 200) # Returns 200 but stays on login with error

    def test_login_failure_invalid_badge(self):
        response = self.app.post('/login', data={
            'username': 'deckhand',
            'badge_id': '0'*16,
            'authcode': 'AAAA-BBBB-CCCC-DDDD'
        }, follow_redirects=True)
        
        self.assertIn(b'Invalid credentials', response.data)

    def test_access_control_protected_route(self):
        """Test that protected routes redirect to login if not authenticated."""
        response = self.app.get('/dashboard', follow_redirects=True)
        self.assertIn(b'Skull Seal Login', response.data)
        # check the flag does not leak
        self.assertNotIn(b'ph0wn{', response.data)

    def test_access_control_palm_trees(self):
        # Login as deckhand
        with self.app.session_transaction() as sess:
            sess['username'] = 'deckhand'
            sess['badge_id'] = TEST_BADGE_ID

        response = self.app.get('/location/palm-trees')
        self.assertEqual(response.status_code, 200)
        self.assertIn(b'Coconuts', response.data)

    def test_access_control_blackpearl(self):
        # Login as deckhand
        with self.app.session_transaction() as sess:
            sess['username'] = 'deckhand'
            sess['badge_id'] = TEST_BADGE_ID

        response = self.app.get('/location/black-pearl')
        self.assertEqual(response.status_code, 200)
        self.assertIn(b'Frozen', response.data)


    def test_access_control_chest_denied(self):
        """Test that deckhand cannot access the treasure chest."""
        # Login as deckhand
        with self.app.session_transaction() as sess:
            sess['username'] = 'deckhand'
            sess['badge_id'] = TEST_BADGE_ID

        response = self.app.get('/location/treasure-chest')
        self.assertEqual(response.status_code, 200)
        self.assertIn(b'HALT - Captain', response.data)

    def test_access_control_chest_allowed(self):
        """Test that picolecroco can access the treasure chest."""
        # Login as picolecroco
        with self.app.session_transaction() as sess:
            sess['username'] = 'picolecroco'
            sess['badge_id'] = TEST_BADGE_ID

        response = self.app.get('/location/treasure-chest')
        self.assertEqual(response.status_code, 200)
        self.assertIn(b'Lots of gold', response.data)
        # Check for presence of flag
        self.assertIn(b'ph0wn{', response.data)

    @mock.patch('server.compute_authcode')
    def test_redis_blocking(self, mock_compute):
        """Ensure 5 failures in window cause blocking for badge_id."""
        badge = TEST_BADGE_ID
        # Setup session with a fresh challenge
        with self.app.session_transaction() as sess:
            sess['challenge'] = 'TESTCHALLENGE'
            sess['challenge_time'] = time.time()

        mock_compute.return_value = 'GOOD-AUTHCODE'

        last_resp = None
        # Make FAIL_THRESHOLD attempts with wrong authcode
        for i in range(server.FAIL_THRESHOLD):
            last_resp = self.app.post('/login', data={
                'username': 'deckhand',
                'badge_id': badge,
                'authcode': 'WRONG'
            }, follow_redirects=False)

        # The request that triggers the threshold should be blocked (429)
        self.assertIsNotNone(last_resp)
        self.assertEqual(last_resp.status_code, 429)
        self.assertIn(b'Too many failed attempts', last_resp.data)

    @mock.patch('server.compute_authcode')
    def test_redis_ok(self, mock_compute):
        """3 failures in the window, then one success"""
        badge = TEST_BADGE_ID
        # Setup session with a fresh challenge
        with self.app.session_transaction() as sess:
            sess['challenge'] = 'TESTCHALLENGE'
            sess['challenge_time'] = time.time()

        mock_compute.return_value = 'GOOD-AUTHCODE'

        last_resp = None
        # Make FAIL_THRESHOLD attempts with wrong authcode
        for i in range(server.FAIL_THRESHOLD-2):
            last_resp = self.app.post('/login', data={
                'username': 'deckhand',
                'badge_id': badge,
                'authcode': 'WRONG'
            }, follow_redirects=False)

        # Now do a correct login
        last_resp = self.app.post('/login', data={
                'username': 'deckhand',
                'badge_id': badge,
                'authcode': 'GOOD-AUTHCODE'
            }, follow_redirects=False)
        # The request that triggers the threshold should be blocked (429)
        self.assertIsNotNone(last_resp)
        self.assertEqual(last_resp.status_code, 302)
        self.assertIn(b'dashboard', last_resp.data)


if __name__ == "__main__":
    unittest.main()
