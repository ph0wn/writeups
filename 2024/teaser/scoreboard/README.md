This is the scoreboard for the Ph0wn CTF Teaser.

- The home page provides the scoreboard + the challenge to download
- The submission page is intentionally "hidden" (+rate limiter) for people to submit their flags.

# Volume

The Hall of Fame is in a Docker *volume*. To access it: 

```
docker compose run scoreboard /bin/bash
$ cat /app/logs/success_records.csv
```


To remove the docker volume:

1. docker compose rm
2. docker volume rm scoreboard_logs


# Obtain SSL certificates

`openssl req -x509 -nodes -days 730 -newkey rsa:2048 -keyout server.key -out server.crt -config certificate.conf`
