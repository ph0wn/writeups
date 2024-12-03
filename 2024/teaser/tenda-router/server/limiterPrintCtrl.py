from flask import request
import time


class LimiterPrintCtrl:
    def __init__(self, timeout_s):
        self.sessions = {}
        self.timeout = timeout_s

    def check(self):
        timeout = self.sessions.get(request.remote_addr)

        if timeout == None:
            return False
        if timeout > time.time():
            return True
        return False

    def create(self):
        timeout = time.time() + self.timeout  # Sessions for a maximum of 5 minutes
        self.sessions[request.remote_addr] = timeout

        # Clean past sessions
        self.clean()

    def clean(self):
        timeout = time.time()
        for key in list(self.sessions.keys()):
            if self.sessions[key] < timeout:
                del self.sessions[key]
