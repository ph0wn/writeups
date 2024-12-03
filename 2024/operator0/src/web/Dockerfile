FROM python:3.10.0 

LABEL maintainer="Brehima -- @bcoul"
LABEL name="operator0"

WORKDIR /code 

COPY ./requirements.txt /code/requirements.txt

RUN python3 -m pip install --no-cache-dir --upgrade -r /code/requirements.txt

COPY . /code

CMD ["fastapi", "dev", "--host", "0.0.0.0", "--port", "80"]