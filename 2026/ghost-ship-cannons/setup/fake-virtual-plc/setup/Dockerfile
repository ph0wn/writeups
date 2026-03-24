FROM python:3.12-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY server_modbus.py .

EXPOSE 5020

CMD ["python", "-u", "server_modbus.py"]

