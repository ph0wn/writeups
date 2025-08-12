FROM python:2.7-alpine
MAINTAINER axelle@fortinet.com

RUN pip install --upgrade pip
RUN pip install gunicorn
RUN pip install flask
RUN pip install requests

COPY ./flaskr /root/flaskr

WORKDIR /root
EXPOSE 21345
CMD [ "gunicorn", "-w", "4", "-b", ":21345", "flaskr:app" ] 
