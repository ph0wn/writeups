*Pico le Croco* would like to control the **power consumption of equipment in his wine cellar and in his pool**. For that, he has bought a nice power meter, [EcoCompteur from Legrand](https://www.legrand.fr/pro/catalogue/31736-ecocompteurs-ip/ecocompteur-modulaire-ip-pour-mesure-consommation-sur-6-postes-110v-a-230v-6-modules).

Can you please help Pico monitor the **power consumption** of his two devices? Pico uses [Domoticz](https://www.domoticz.com/), which is an open source Home Automation software. You can access you own instance of Domoticz by pressing the "Deploy Container" button. This container is running on the same network as the EcoCompteur of Pico.

Configuration:

- In **Setup / Hardware**, a Ph0wn **EcoCompteur hardware** is already partly configured for you (along with weather sensors), but you need to **fix the IP address** to: `http://10.210.17.66:20000`. Unfotunately, you may encounter a bug where you are unable to modify the existing hardware. In that case, delete it and create a new one. Also, the port `20000` must be specified in both the remote address and the port (other bug).
- Then, among **Devices**, you might like to tag "Conso 1" and "Conso 2":  Conso 1 is the wine cellar, Conso 2 is the pool.
- Find the flag :)

Notes:

- If you don't obtain a "container deployed" + URL in the CTF website one minute after you have clicked on the "deploy container", contact us.
- The Domoticz home automation is dedicated to your team. If you screw it up, hit the button "Redeploy container" button.
- The *EcoCompteur* is shared for all teams (but the container is specific to your team). As for any challenge, please no DoS.
- You might find it easier to wait for 15-20 minutes to understand what is happening. Although if you are *very smart*, you won't have to wait long.


