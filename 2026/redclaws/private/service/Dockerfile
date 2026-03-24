FROM python:3.11-slim

RUN mkdir /app
WORKDIR /app

COPY app/main.py /app/main.py
COPY app/static /app/static
COPY app/templates /app/templates
COPY app/flag.txt /opt/flag

RUN pip install flask

ENV FLASK_APP=main.py
ENV FLASK_RUN_HOST=0.0.0.0

CMD ["flask", "run"]
