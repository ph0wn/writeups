# Use a lightweight Python base image
FROM python:3.11-slim

# Set working directory
WORKDIR /app

# Copy server script to container
COPY server.py /app/server.py
COPY pcb-key /app/pcb-key

# Create a non-root user
RUN useradd -ms /bin/bash appuser

# Switch to the non-root user
USER appuser

# Expose port
EXPOSE 9099

# Run the server
CMD ["python", "server.py"]
