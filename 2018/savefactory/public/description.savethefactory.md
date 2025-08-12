# Save the Factory

Title: Save The Factory
Category: OT
Points: 500 - 20 - 100
Author: m0eukh
Tags: no-equipment, intermediate

*FearFactory* is an industrial factory whose sensitive units are supervised and controlled by a **main board**.
The communication between the **main board** and the rest of the physical equipment (machines, sensors, ...) is done using the **OPC-UA protocol**.

Due to a misconfiguration of access rights, a **smart hacker managed to tamper with the server on the main board and send random requests to nodes**. This represents a **safety hazard to the factory!!!**.

Can you save the factory (and its workers) in time and reset tampered nodes to normal operating values?


**Factory credentials:**

- OPC-UA URL path: `opc.tcp://35.233.31.82:PORT/FearFactory/supervision_unit`
- Port: get `PORT` for your team from the scoreboard's "Team Keys" menu. Do not use another port on the factory. This won't help you. Teams intentionally trying to cause havoc will be banished :=)

**Shell access:**

You may connect get a shell at FearFactory via **SSH**:

- SSH Host: `35.233.31.82`
- SSH Port: `6666`
- Username: *supplied in Team Keys menu*
- Password: *supplied in Team Keys menu*

Your shell account contains a **Python OPC-UA script** named `client_example.py`, which (1) connects to the factory's server, and (2) subscribes to notifications of the `MainBoard` to receive alerts.

Don't forget to inspect the contents of the **black box** to see if there are any interesting logs :)



