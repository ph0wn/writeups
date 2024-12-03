Sous Linux, télécharger [l'emulateur Atari 800](https://github.com/atari800/atari800).
Il y a un fichier de config `~/.atari800.cfg`.

- Il faut télécharger les ROMs pour Atari 800 ! C'est indiqué dans la [doc d'install](https://github.com/atari800/atari800/blob/master/DOC/INSTALL) ou dans `/usr/share/doc/atari800/FAQ`:

```
0. Q: Where to get ROM files for the emulator?
   A: Download the xf25.zip from http://prdownloads.sf.net/atari800/xf25.zip
      You'll get three .ROM files - ATARIBAS.ROM, ATARIOSB.ROM and ATARIXL.ROM.
      We cannot distribute them due to licensing and copyright issues. Sorry.
      In Atari800 UI there is an option to locate these ROM images.
```

Pour ce jeu, nous avons besoin:

- de la Rom `ATARIOSB.ROM`
- de la Rom `ATARIXL.ROM`

Le lancement de la cassette est immédiat avec:

```
atari800 -win-width 1024 -win-height 768 -xl -osb_rom ./ATARIOSB.ROM -xlxe_rom ./ATARIXL.ROM -boottape ./Ph0wn.cas 
Using Atari800 config file: /home/axelle/.atari800.cfg
Created by Atari 800 Emulator, Version 5.2.0

Keyboard mapped to emulated joystick 0
Requested resolution 336x240 is not available, using 640x480 instead.
Video Mode: 1008x720x32 windowed without vsync
```

Et le flag 1 s'affiche rapidement.
