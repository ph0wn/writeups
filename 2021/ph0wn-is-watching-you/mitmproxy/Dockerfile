FROM mitmproxy/mitmproxy:6.0.0
WORKDIR /app
COPY filter.py filter.py
COPY cert.pem cert.pem
ENTRYPOINT mitmdump --certs *=cert.pem --mode reverse:http://${SERVER_HOSTNAME}:${SERVER_PORT} -p 8002 -s filter.py --set block_global=false --no-http2
