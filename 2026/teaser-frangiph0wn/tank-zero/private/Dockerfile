FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

# Minimal OS deps
RUN apt-get update && apt-get install -y --no-install-recommends \
      ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Create an unprivileged user/group
# Use a fixed UID/GID to play nicely with certain orchestrators and bind mounts.
RUN groupadd --system --gid 10001 app \
 && useradd  --system --uid 10001 --gid 10001 --home-dir /nonexistent --shell /usr/sbin/nologin app

# Install deps first for caching
COPY requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r /app/requirements.txt
COPY server.py app.py /app

# Ensure the unprivileged user can read the app and write only where needed.
# If your app needs to write files, prefer creating a dedicated writable dir (e.g., /app/data or /tmp).
RUN chown -R app:app /app

USER app

EXPOSE 9999

CMD ["sh", "-c", "\
  gunicorn app:app \
    --worker-class uvicorn.workers.UvicornWorker \
    --bind 0.0.0.0:${PORT:-9999} \
    --workers ${WORKERS:-4} \
    --worker-tmp-dir /dev/shm \
    --timeout ${TIMEOUT:-0} \
    --graceful-timeout 30 \
    --keep-alive 75 \
    --access-logfile - \
    --error-logfile - \
    --log-level ${LOG_LEVEL:-info} \
"]
