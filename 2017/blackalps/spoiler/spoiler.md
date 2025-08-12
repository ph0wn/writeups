= BlackAlps badge I - Xtensible reversing

== Commentaires :
    Un reverse assez basique mais avec une architecture xtensa histoire
    de mettre les gens dans le bain avec le chip.

Flag: Ph0wn{Fr0m_.ch_w1th_<3}


= BlackAlps badge II - Let's get graphical

== Commentaires :
    Le firmware du badge est fourni. Le but est de reverser le firmware
    pour trouver ou sont stockées les images affichées sur le badge.
    Ensuite, il faut comprendre comment les images sont affichées sur
    l'écran (ie. lire la doc du pcd8544) et recomposer une image qui
    affiche le flag.

Flag: PH0WN{P1X3L_P3RF3CT}


= BlackAlps badge III - TBA
== Commentaires :
    Ce challenge nécessite l'accès physique au badge. L'idée est de
    pouvoir uploader sa propre image sur le badge. L'écriture de l'image
 déborde sur une structure qui gère les menus de l'affichage. écraser
    la structure permet de réécrire un pointeur de fonction et ainsi de
    pouvoir exécuter une fonction get_flag qui affiche le dernier flag.

Pour le resoudre, il faut avoir fait le stage 1 obligatoirement (pour
acceder a l'interface serial).
La deux est recommandee, puisqu'elle te permet de comprendre
l'organisation memoire et les structures qui sont utilisees (dont celle
que tu dois ecraser pour recuperer le flag).

Ph0wn{0MG_YoU_D1d_17!}



