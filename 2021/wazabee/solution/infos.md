# Infos given by Romain

Cet APK contient le challenge. Le fonctionnement est très simple : tu dois installer l'apk sur un téléphone supportant les extended advertising et le LE 2M (tu peux vérifier facilement avec nRFconnect, dans Device information, tout doit être en vert), puis lancer le challenge en appuyant sur le bouton "Launch challenge"
dès lors, le flag sera advertisé via le BLE sous la forme d'un paquet Zigbee. Les canaux 17 du BLE et 18 du Zigbee sont superposés, ce qui permet d'émettre l'un avec l'autre
le flag est "ph0wn{9=kp\\cvZ|wU}", il est contenu dans le payload du paquet Zigbee transmis sur le canal 18 du zigbee
il y a deux façons de résoudre le challenge : en reversant l'APK et en comprenant à partir des sources de quelle attaque il s'agit (pas évident), ou en sniffant le Zigbee
pour faciliter la piste via les sources, j'ai ajouté un easter egg, une chaîne de caractères nommée SIRET comme attribut de l'une des classes et contenant le SIRET d'une entreprise nommée WazaBee (homonyme)
Pour un descriptif du principe général de l'attaque (exploitation de l'extended advertising), tu peux lire la section VI.B de cet article
https://hal.laas.fr/hal-03193299/document

# Sniffing

Pour le sniffing, le plus simple est de sniffer le Zigbee directement, avec un sniffer 802.15.4 (e.g. rzusbstick d'atmel avec le firmware killerbee)

- https://www.mouser.fr/ProductDetail/Microchip/ATZB-X-212B-USB?qs=sGAEpiMZZMukxKgYRb08uDL5%252BybE6Tt8%252BAsb7VEH4JM%3D

Damien Cauquil a developpe un sniffer il y a peu:

- https://github.com/virtualabs/cc2531-killerbee-fw
- https://virtualabs.fr/bumblebee-zigbee-killerbee

Si tu veux sniffer le BLE, il faut que tu monitores les extended advertisements. Mais tu n'obtiendra pas plus que ce qui est passe a l'advertiser dans le code.

# Zigbee

une trame Zigbee est prefixee par un preambule de 8 zeros
Pour emettre en Zigbee, il y a une table de correspondance entre les symboles Zigbee et des sequences de 32 bits en BLE (cf Tableau 2 page 26 de l'article SSTIC). La repetition de 03f73a1b au debut de la chaine correspond aux 0 par ex.
(note : l'endianess est peut etre different de la table dans l'article)
apres la serie de 8 zeros, en Zigbee, il y a un octet dont la valeur est systematiquement 0xa7


# Additional infos

To uninstall the app: `adb uninstall radio.sploit.phownchallenge`

# References

- https://hal.laas.fr/hal-03193299/document
- [SSTIC 2020](https://www.sstic.org/media/SSTIC2020/SSTIC-actes/wazabee_attaque_de_reseaux_zigbee_par_detournement/SSTIC2020-Article-wazabee_attaque_de_reseaux_zigbee_par_detournement_de_puces_bluetooth_low_energy-galtier_marconat_AhcLCGU.pdf)
- https://www.real-world-systems.com/docs/hcitool.1.html
