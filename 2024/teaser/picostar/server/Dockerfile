# Use an official Python runtime as a parent image
FROM python:3.10-slim

RUN groupadd -g 961 pico
RUN useradd -u 961 -g 961 -ms /bin/bash pico

# Set the working directory in the container
WORKDIR /app

# Copy the current directory contents into the container at /app
COPY picoflask.py .
COPY conf.py .
COPY requirements.txt .
COPY server.key .
COPY server.crt .
COPY FLAG_AND_NEXT .

# Install any needed packages specified in requirements.txt
RUN pip install -r requirements.txt

RUN chown -R pico:pico /app
USER pico

# Expose the port the app runs on
EXPOSE 9950

# Run the command to start the Flask app
CMD gunicorn -c conf.py --threads 8 --bind 0.0.0.0:9950 --certfile=server.crt --keyfile=server.key picoflask:app
