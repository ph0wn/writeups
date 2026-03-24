import redis
import os
import logging

# Create a logger for auth.py
logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)
if not logger.handlers:
    ch = logging.StreamHandler()
    formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
    ch.setFormatter(formatter)
    logger.addHandler(ch)


# Redis-backed rate limiting configuration
REDIS_URL = os.getenv('REDIS_URL', 'redis://localhost:6379/0')
try:
    redis_client = redis.from_url(REDIS_URL, decode_responses=True)
    logger.info(f'Connected to Redis at {REDIS_URL}')
except Exception as e:
    redis_client = None
    logger.warning(f'Could not connect to Redis ({REDIS_URL}): {e}. Rate-limiting disabled.')

# Constants for rate limiting
FAIL_WINDOW = 60          # seconds to count failures
FAIL_THRESHOLD = 5        # fails within window to trigger block
BLOCK_SECONDS = 300       # seconds to block after threshold reached

def get_block_ttl(badge_id: str) -> int:
    if not redis_client:
        return 0
    key = f'blocked:{badge_id}'
    ttl = redis_client.ttl(key)
    try:
        return int(ttl)
    except Exception:
        return 0

def record_failed_login(badge_id: str):
    if not redis_client:
        return
    fails_key = f'fails:{badge_id}'
    blocked_key = f'blocked:{badge_id}'
    # Increment failures and set expiry on first failure
    cnt = redis_client.incr(fails_key)
    if cnt == 1:
        redis_client.expire(fails_key, FAIL_WINDOW)
    if cnt >= FAIL_THRESHOLD:
        # set block key for BLOCK_SECONDS and clear fails
        redis_client.set(blocked_key, 1, ex=BLOCK_SECONDS)
        try:
            redis_client.delete(fails_key)
        except Exception:
            pass

def clear_failures(badge_id: str):
    if not redis_client:
        return
    try:
        redis_client.delete(f'fails:{badge_id}')
        redis_client.delete(f'blocked:{badge_id}')
    except Exception:
        pass


