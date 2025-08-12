from mitmproxy import http
import re

def request(flow):
    if 'left' in flow.request.url or re.match(r'^http://server:8000/[a-z0._/]*$', flow.request.url) is None:
        flow.response = http.HTTPResponse.make(403, b"ph0wn WAF: Forbidden\n")
