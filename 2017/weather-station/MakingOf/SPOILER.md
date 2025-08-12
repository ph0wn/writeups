L'idée est de partir sur un objet simple : une station météo, qu'il
 faudra hacker.
  
Pour le moment elle se compose des éléments suivants :

  * Le CPU est une board STM32 Nucleo 64, modèle L476RG
  * un afficheur OLED 0.96 pouce de 128x64 (ça rigole pas !). Il est de
  couleur jaune sur la partie supérieur et bleu sur le reste.
  * un capteur de température et d'humidité.
  * un module USBsérie FTDI qui permet sur le simple branchement à PC
  de communiquer avec le CPU sur une liaison série.
  * un bouton RESET
  
  J'ai validé tous les composants, je maitrise le kit de dev avec Eclipse,
  je n'ai donc normalement plus d'inconnue technique.
  En PJ tu as les photos du proto sur la breadboard.
  Afin d'avancer et voir de finaliser sous peu le challenge, j'ai quand
  même besoin de ton avis sur le scénario. Le voici :
  
La station météo sera fonctionnelle : elle affichera un bandeau avec le
  nom du produit, Fortinet Weather Machine par exemple. En dessous la
  température et l'humidité. Souffler sur le capteur permet de le faire
  varier et de voir en temps réel que ce n'est pas du chiqué.
  Sera remis aux participants, le firmware en .elf qui tourne dans le
  STM32 (pas de modification, il sera avec les flags dedans etc.).
  Afin de ne pas faire peur et d'essayer de faire défiler tout le monde
  sur la manip, divers challenges seront proposés :
  
-  50 : Cold start & leek (Hardware/MISC)
-  150 : Find the password (Hardware/Reverse)
-  300 : Call the deadbeef (Hardware/Exploitation)
-  500 : Shellcode (Hardware/Exploitation)

  Je vais détailler chacun :
  
  50 : Le but est d'amener le participant a connecter le PC sur l'USB,
  voir l'USB ID et trouver que c'est un module FTDI pour installer le
  driver (ou lire sur le CHIP qui sera apparent :) ). Ensuite, une fois la
  liaison série en place, il doit trouver le baudrate et il s'apercevoir
  qu'il tombe sur un mot de passe. Un bouton RESET est présent, et il doit
  comprendre que regarder la séquence de boot est tjrs intéressent. Dans
  la séquence de boot il y a le flag.
  
  150 : une fois la liaison série établie, il faudra qu'il trouve le mot
  de passe pour se connecter champ password, une chaine + XOR + un truc
  assez simple (add/sub tableau de constantes). Là on est obligé de
  reverser le firmware pour trouver le passwd. Le bon password
  déverrouille le menu de setup de la station météo.
  
  300 : appeler un menu planqué qui est tout prêt dans le firmware, mais
  aucun moyen de le faire via l'interface série. Dans un des menus
  derrière le passwd il y aura la possibilité de changer le nom du produit
  et l'overflow sera là. A travers une chaine de caractère pourrie il
  devra faire le jump au bon endroit et sur l'afficheur le flag sortira.
  
  500 : shellcode. Pour le moment je n'ai qu'une ébauche, le souci étant
  structurel pour cette dernière partie : comme le firmware est donné, il
  y a accès à tout. Il faut donc trouver une action qui n'est pas toute
  faite, basique de préférence pour être réalisable, tout en demandant de
  coder un truc exec depuis la stack. La seule idée est pour le moment de
  faire afficher le numéro de série du STM32. Dans le firmware, il y aura
  des routines d'affichages sur le LCD de partout et également du code
  envoyant des données sur le port série. Seulement il faudra crafter
  correctement l'appel avec le numéro de série du STM32 (il faut savoir
  que chaque STM32 a un numéro de série différent sur 96bits). Sur le
  papier ça semble OK, à tester grandeur nature.
  Coté matériel, pour que ça ressemble à un truc propre, je vais garder la
  board Nucleo et fabriquer avec une plaque pré-étamée un "shield" avec
  les 3 principaux modules (OLED, capteur et USBsérie) qui viendra tout
  simplement s'enficher sur la nucléo. J'ai normalement ce qu'il faut et
  ce n'est pas si dur que ça.


Cote logiciel embarque j'ai fini les challenges 1 2 et 3 dont on a deja
parle dnas les precedents emails. CAD que j'ai tout, le design general (il y
a meme des beaux dessins 128x64 mono-couleur au boot :) ), les menus sur le
port serie une fois le password trouve, le script d'exploit "exemple" en
python du stack-smashing pour l'appel de l'easter egg afin de verifier que
l'exo est bien faisable. J'ai soigne la generation de code du binaire afin
de couper toutes les optimisations pour ne pas rendre infernale les etapes
de reverse lie au offset de la stack ou de manipulation de chaines. Le
firmware sera donne en .BIN *et* .ELF pour que tous les outils soient
utilisables, mais sera strippe avec le switch --strip-all .

Le scenario du challenge 4 est le suivant : la station meteo a la
possibilite de se diagnostiquer a travers l'I2C et fournir des informations
internes sensibles mais dans ce mode uniquement. Elle garde donc des secrets
le reste du temps. L'info sensible en question est son numero de serie, un
ID unique. L'attaquant doit le recuperer mais par un autre moyen car il
n'aura pas de quoi fabriquer et brancher cette sonde sur le port I2C (et si
c'est le cas, il merite les points car il aura reverse la fonction I2C,
ponte 2 fils sur le bus I2C et compris comment abuser le systeme de diag de
la station meteo :) ).

Techniquement ca fonctionne comme ca : Au boot la station lit une valeur sur
l'I2C et si et seulement si elle est trouvee elle calcul un ID unique base
sur le lot de fabrication du MCU et le renvoie via l'I2C. Si la valeur
specifique sur l'I2C n'est pas trouvee le boot se poursuit normalement et
les infos sensibles ne sont meme pas calculees. --> il n'y a donc pas de
moyen de recuperer simplement une zone de RAM pour flaguer.
Ce que devra faire l'attaquant c'est placer dans un buffer un payload qui
devra appeler la fonction de calcul du numero de serie, coder ce qu'il faut
pour leeker l'information et ensuite appeler tout ca via un buffer overflow
(le meme buffer overflow que pour le challenge n3 d'appel de l'easteregg).
Pour leeker l'info; soit il la sort sur l'afficheur, mais ce n'est pas une
super idee car c'est complexe a appeler et toute les secondes c'est efface
par l'interruption d'affichage des parametres. Ou soit il la balance tout
simplement par le port serie et la relit via le meme script qu'il a utilise
pour lancer le payload.

