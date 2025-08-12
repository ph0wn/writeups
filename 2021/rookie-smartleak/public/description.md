# Smart leak

You have heard that the Illuminatis have inserted secrets in smart cards. To know whether there is such as secret in smart cards, you have gently opened the chip, and you have put probes on MCUs and buses, as well as on the interface device (a paiement terminal). And then, you have plugged your smart card.

Soon after, you have noticed that the application bus seems to transfer a 16-bit AES key (ECB mode). After several computations on the application MCU, the smart card seems to leak a secret to the interface.

Help deciphering this secret, and the flag will be yours!

# How to proceed
We have saved our probing in VCD format
