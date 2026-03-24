import hashlib
import logging
import os
import random
import time
import secrets
from flask import Flask, request, jsonify, session, render_template, redirect, url_for
from functools import wraps
from auth import compute_authcode
from limit import get_block_ttl, record_failed_login, clear_failures, redis_client, FAIL_THRESHOLD

app = Flask(__name__)

# mandatory for sessions
if os.environ.get('SECRET_KEY') is None:
    secret = secrets.token_urlsafe(16)
else:
    secret = os.environ.get('SECRET_KEY')
app.config['SECRET_KEY'] = secret

# Create a logger for server.py
logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)
if not logger.handlers:
    ch = logging.StreamHandler()
    formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
    ch.setFormatter(formatter)
    logger.addHandler(ch)

def check_badge_id(badge_id: str) -> bool:
    # we don't want participants to use a fake badge id - 
    # we can't guess them all but we can block obvious test/weak ones
    if len(badge_id) != 16:
        logger.warning(f'Invalid badge_id length: {badge_id}')
        return False
    allowed_chars = '0123456789abcdef'
    for c in badge_id:
        if c not in allowed_chars:
            logger.warning(f'Invalid badge_id character: {badge_id}')
            return False
    if badge_id == '0123456789abcdef':
        logger.warning(f'Test badge_id used: {badge_id}')
        return False
    for i in allowed_chars:
        if badge_id == i * 16:
            logger.warning(f'Test/Weak badge_id used: {badge_id}')
            return False
    return True


# Login decorator to protect routes
def login_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'username' not in session:
            return redirect(url_for('login_page'))
        return f(*args, **kwargs)
    return decorated_function


@app.route('/')
def index():
    """Home page - redirects to dashboard if logged in, otherwise to login."""
    if 'username' in session:
        return redirect(url_for('dashboard'))
    return redirect(url_for('login_page'))


@app.route('/login', methods=['GET', 'POST'])
def login_page():
    if request.method == 'POST':
        # Handle standard form submission
        username = request.form.get('username')
        authcode = request.form.get('authcode', '').strip().upper()
        badge_id = request.form.get('badge_id', '').strip().lower()

        # Fallback to JSON if form data is missing (for compatibility or API usage)
        if not username and request.is_json:
            data = request.get_json()
            username = data.get('username')
            authcode = data.get('authcode', '').upper()
            badge_id = data.get('badge_id', '').lower()

        error_message = None

        if not all([username, authcode, badge_id]):
            error_message = 'Missing required fields: username, authcode, badge_id'
        
        elif username != 'picolecroco' and username != 'deckhand':
            error_message = 'Invalid credentials'
        
        elif check_badge_id(badge_id) is False:
            error_message = f'Invalid credentials'
        
        else:
            # Get challenge from session
            challenge = session.get('challenge')
            if not challenge:
                error_message = 'No challenge found. Please request a challenge first.'
            else:
                # Check challenge is fresh
                challenge_time = session.get('challenge_time', 0)
                if time.time() - challenge_time > 300:  # 5 minutes
                    error_message = 'Challenge expired. Please request a new challenge.'
                else:
                    # Check for an active block for this badge_id
                    if redis_client:
                        blocked_ttl = get_block_ttl(badge_id)
                        if blocked_ttl and blocked_ttl > 0:
                            logger.warning(f'Login attempt blocked for badge_id={badge_id} ttl={blocked_ttl}s')
                            return render_template('login.html', error=f'Too many failed attempts. Try again in {blocked_ttl} seconds.'), 429
                    # check authorization code
                    try:
                        expected_authcode = compute_authcode(challenge, badge_id, username)
                        logger.debug(f'Login attempt: user={username} challenge={challenge} badge_id={badge_id}')
                        
                        # Check if authcode matches
                        if authcode == expected_authcode:
                            session['username'] = username
                            session['badge_id'] = badge_id
                            # Clear challenge after successful login
                            session.pop('challenge', None)
                            session.pop('challenge_time', None)
                            # Clear any recorded failures/blocks on successful login
                            clear_failures(badge_id)
                            logger.debug(f'Login successful for user={username}')
                            return redirect(url_for('dashboard'))
                        else:
                            logger.warning(f'Login failed for user={username}: invalid authcode')
                            # Record failed login for this badge_id
                            record_failed_login(badge_id)
                            # If the badge is now blocked, inform the user
                            blocked_ttl = get_block_ttl(badge_id)
                            if blocked_ttl and blocked_ttl > 0:
                                logger.warning(f'Badge {badge_id} blocked for {blocked_ttl}s')
                                return render_template('login.html', error=f'Too many failed attempts. Try again in {blocked_ttl} seconds.'), 429
                            error_message = 'Invalid credentials'
                    except Exception as e:
                        logger.error(f'Error computing authcode: {e}')
                        # treat as a failed attempt as well
                        record_failed_login(badge_id)
                        error_message = 'Internal server error.'

        if error_message:
            logger.error(f'Login failed: {error_message}')
            return render_template('login.html', error=error_message)

    # GET request
    return render_template('login.html')

@app.route('/challenge')
def get_challenge():
    """Generate a new challenge for login."""
    ALPH = "LR"
    challenge = ''.join(random.choice(ALPH) for _ in range(12))
    
    session['challenge'] = challenge
    session['challenge_time'] = time.time()
    
    logger.info(f'Generated new challenge: {challenge}')
    return jsonify({'challenge': challenge})


@app.route('/location/palm-trees')
@login_required
def location_palm_trees():
    """Public location accessible to all logged-in pirates."""
    return render_template('location_palmtrees.html')

@app.route('/location/black-pearl')
@login_required
def location_black_pearl():
    """Public location accessible to all logged-in pirates."""
    return render_template('location_blackpearl.html')

@app.route('/location/treasure-chest')
@login_required
def location_treasure_chest():
    """Restricted location accessible only to the Captain."""
    username = session.get('username')
    if username == 'picolecroco':
        try:
            f = open('FLAG2','r') 
            flag = f.read()
            f.close()
            return render_template('location_chest.html', flag=flag)
        except Exception as e:
            logger.error(f'Exception: {e}')
            jsonify({'success': False, 'message': 'Internal Server Error'}), 500
            
    else:
        return render_template('chest_denied.html')


@app.route('/logout', methods=['POST', 'GET'])
@login_required
def logout():
    """Logout endpoint that clears the session."""
    username = session.get('username')
    session.clear()
    logger.info(f'User {username} logged out')
    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return jsonify({'success': True, 'message': 'Logged out successfully'}), 200
    return redirect(url_for('login_page'))


@app.route('/dashboard')
@login_required
def dashboard():
    """Protected dashboard page for logged-in users."""
    username = session.get('username')
    return render_template('dashboard.html', username=username)

# Comment this for the CTF
'''
@app.route('/hack', methods=['GET'])
def hack():
    username = request.args.get('username', 'deckhand')
    badge_id = request.args.get('badge_id', '0000000000000000')
    session['username'] = username
    session['badge_id'] = badge_id
    logger.debug(f'Hacked login for user={username}')
    return redirect(url_for('dashboard', username=username))
'''

if __name__ == '__main__':
    host = os.getenv('HOST', '0.0.0.0')
    port = int(os.getenv('PORT', 5000))
    debug = os.getenv('DEBUG', 'False').lower() == 'true'
    logger.info(f'Starting Flask server on {host}:{port}')
    app.run(host=host, port=port, debug=debug)
