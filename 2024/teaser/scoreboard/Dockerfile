FROM python:3.10-slim

RUN groupadd -g 951 scoreboard
RUN useradd -u 951 -g 951 -ms /bin/bash scoreboard

WORKDIR /app
COPY run.py /app
COPY gunicorn.conf.py /app
COPY server.crt /app
COPY server.key /app
COPY ./templates /app/templates
COPY ./static /app/static

RUN mkdir /app/logs && chmod 777 /app/logs

# Use RUN to install Python packages
RUN pip3 install --no-cache-dir Flask==3.0.0 Flask-Limiter==3.6.0 termcolor==2.4.0 gunicorn==22.0.0 pytz

RUN chown -R scoreboard:scoreboard /app
USER scoreboard

EXPOSE 8090
CMD gunicorn -c gunicorn.conf.py --threads 8 --bind 0.0.0.0:8090 --certfile=server.crt --keyfile=server.key run:app

