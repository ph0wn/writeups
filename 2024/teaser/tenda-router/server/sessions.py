from flask import request
import string
import random
import time


class Sessions:
    def __init__(self):
        self.sessions = {}

    def check(self):
        cookie = request.cookies.get("password")
        timeout = self.sessions.get(cookie)

        if timeout is None:
            return False
        if timeout > time.time():
            return True
        return False

    def create(self, password):
        # Create the cookie
        prepend = random.choice('abcdef0123456789') + random.choice(
            'abcdef0123456789') + random.choice('abcdef0123456789')
        append = random.choice('abcdef0123456789') + random.choice(
            'abcdef0123456789') + random.choice('abcdef0123456789')
        password = prepend + password + append

        timeout = time.time() + 5*60  # Sessions for a maximum of 5 minutes
        self.sessions[password] = timeout

        # Clean past sessions
        self.clean()

        return password

    def destroy(self, password):
        timeout = self.sessions.get(password)
        if timeout is None:
            return False

        del self.sessions[password]

        # Clean past sessions
        self.clean()

        return True

    def clean(self):
        timeout = time.time()
        for key in list(self.sessions.keys()):
            if self.sessions[key] < timeout:
                del self.sessions[key]
