FROM python:3.12-slim

# Create non-root user
RUN useradd -m appuser

WORKDIR /app

# Install deps
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy app
COPY server.py auth.py limit.py FLAG2 .
COPY templates/ ./templates/
COPY static/ ./static/

# Switch to non-root
USER appuser

# Flask config
ENV FLASK_APP=server.py
ENV FLASK_RUN_HOST=0.0.0.0
ENV FLASK_RUN_PORT=9888

EXPOSE 9888

CMD ["gunicorn", \
     "--bind", "0.0.0.0:9888", \
     "--workers", "4", \
     "--threads", "4", \
     "server:app"]
