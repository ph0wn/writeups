# Historique

Il est possible d'écrire des Dockers multi-architecture, et donc d'avoir par exemple un Docker ARM qui tourne sur une machine x86. Cependant, après essais, il s'est avéré que cela finissait par poser des problèmes, donc je suis revenue à la solution de Saumil Shah qui monte un QEMU ARM64 complet.

- coté participant: créer un Dockerfile avec installation de gdb et gef posait problème (ptrace pb à l'exécution etc)

- coté server: sur l'instance Google, le Docker container n'avait pas le même noyau Linux ni la même libc que le côté participant, ce qui aurait rendu l'exploitation trop difficile.

Donc: on utilise l'environnement de Saumil des deux côtés.

# Setup des participants

Aux participants, on donne `participants.zip`, qui contient un flag bidon.


# Setup sur le serveur

- On utilise également `participants.zip` : `scp participants.zip axelle@chal.ph0wn.org`, mais on va le modifier un peu.

- Uploader `./server/Dockerfile-ph0wn`, `./server/run-trail-server` et `./server/shutdown-trail-server` qui sont de légères modifs de `run-ph0wn-docker` et `shutdown-ph0wn-docker`. On ne se servira sur le serveur que des scripts trail-server.

- Lancer `./run-trail-server`. Ca va mettre ~2 minutes (plus la première fois) car QEMU se lance. Un docker `trail-server` sera présent.

- Il faut changer le flag à la main dans l'instance.

- Pour arrêter le serveur, `shutdown-trail-server`.



