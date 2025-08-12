# Ph0wn Day

On a separate Google instance:

**Step 1. SSH to port 22222**

[Move SSH to port 22222](https://hackertarget.com/cowrie-honeypot-ubuntu/):

- In `/etc/ssh/sshd_config`, modify port to **22222**
- Make sure to have a firewall rule for 22222. Don't block 22 yet.
- Restart SSH to new port: `sudo systemctl restart ssh`
- Check (`system status ssh`), then when ok remove port 22

**Step 2. Setup Firewall**

Make sure this Google instance is only reachable from:

- ftnt-employee-aapvrille
- IP addresses coming out of Ph0wn CTF (e.g. University IP address)

**Step 3. Setup challenge**

- Copy the challenge directory.
- Run `docker-compose up -d --build`
- In `./medfusion/meltingpot/meltingpot.cfg` specify `public_ip` with the IP address of the Google instance.

# Ph0wn Test Sessions

- We can use a landing VM and access it via SSH key
- The server to access has IP address: `10.132.0.29`

# Local test

- On your computer, run `docker-compose up -d --build`

