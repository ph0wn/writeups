FROM python:3.10-slim-buster

# get python packages
RUN pip3 install --upgrade pip
COPY requirements.txt .
RUN pip3 install -r /requirements.txt

# copy the app
RUN mkdir -p /app/static
COPY fakebianserver.py /app
COPY static/ /app/static
WORKDIR /app

EXPOSE 9000
CMD [ "gunicorn", "-b", "0.0.0.0:9000", "fakebianserver:app" ]