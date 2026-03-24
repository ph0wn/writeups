TODO: I should probably move this to my password manager :)

**Wire format:** `[65 B ephemeral pubkey (uncompressed P-256)][12 B nonce][16 B tag][ciphertext]`


- ECDH(your_private_key, ephemeral_pubkey)  
- HKDF-SHA256(salt=none, info=`NAVTEX-GRIB-ECIES-P256`, len=32) 
- AES-256-GCM(key, nonce, ciphertext||tag, no AAD)

