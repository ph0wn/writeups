FROM python:3.8-buster

COPY requirements.txt /
RUN pip3 install --upgrade pip
RUN pip3 install -r requirements.txt
RUN rm requirements.txt

COPY challenge .

