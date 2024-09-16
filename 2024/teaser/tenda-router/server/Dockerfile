FROM python:3.10-slim

RUN groupadd -g 950 ac15
RUN useradd -u 950 -g 950 -ms /bin/bash ac15

WORKDIR /app
COPY run.py sessions.py limiterPrintCtrl.py gunicorn.conf.py /app
COPY webroot_ro /app/webroot_ro

# Use RUN to install Python packages
RUN pip3 install --no-cache-dir Flask==3.0.0 Flask-Limiter==3.6.0 gunicorn==22.0.0

RUN chown -R ac15:ac15 /app
USER ac15

EXPOSE 8080
CMD ["python3", "-u", "/usr/local/bin/gunicorn", "--threads", "8",  "--bind", "0.0.0.0:8080", "run:app"] 
