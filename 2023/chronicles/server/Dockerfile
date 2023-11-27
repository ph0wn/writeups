# Use an official Python runtime as a parent image
FROM python:3.10-slim

# Set the working directory in the container
WORKDIR /app

# Copy the current directory contents into the container at /app
COPY chronicles_server.py .


# Expose the port the app runs on
EXPOSE 9910

# Run the command to start the Flask app
CMD [ "python", "chronicles_server.py" ]
