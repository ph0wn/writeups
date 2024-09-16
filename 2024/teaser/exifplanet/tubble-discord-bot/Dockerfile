FROM python:3.10-slim-buster

WORKDIR /app
RUN useradd -m -r user && chown user /app

COPY requirements.txt ./
COPY .env ./
RUN pip install -U pip
RUN pip install --no-cache-dir -r requirements.txt
COPY tubblebot.py ./
COPY mc021_motor_controller_firmware_0356.zip .
COPY telescope.jpg .
COPY space-invaders.mp3 . 

USER user

CMD [ "python", "-u", "./tubblebot.py" ]
