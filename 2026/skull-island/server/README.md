
# Testing

- Source Python venv `python3 -m venv venv` then `source ./venv/bin/activate` then `pip3 install -r requirements.txt`
- Run unit tests with `python3 -m unittest -v`
- Run load tests:

```
python3 load_tests.py concur --host 127.0.0.1 --port 9888 --users 250
python3 load_tests.py sustain --host 127.0.0.1 --port 9888 --duration 60 --rps 250
python3 load_tests.py failures --host 127.0.0.1 --port 9888 --badge-count 50
```

NB. To test flag is retrievable, use `get_flag.py` from the spoiler dir