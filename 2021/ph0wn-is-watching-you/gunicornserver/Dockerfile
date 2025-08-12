FROM python:3
RUN pip3 install -v gunicorn==20.0.4
RUN pip3 install -v Flask==1.1.1
RUN pip3 install -v Flask-Session
WORKDIR /app
COPY w.py w.py
COPY camera.py camera.py
COPY static static
COPY templates templates
COPY images images
COPY conf.py conf.py
CMD gunicorn -c conf.py --threads 8 --bind 0.0.0.0:8000 w:app
