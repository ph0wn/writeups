#!/usr/bin/env python3

def encrypt(plaintext, substitutions):
    encrypted_text = []
    for char in plaintext:
        if 'a' <= char <= 'z':
            index = ord(char) - ord('a')
            encrypted_text.append(substitutions[index])
        else:
            encrypted_text.append(char)
    return ''.join(encrypted_text)

def decrypt(ciphertext, substitutions):
    decrypted_text = []
    for char in ciphertext:
        if 'a' <= char <= 'z':
            index = substitutions.index(char)
            decrypted_text.append(chr(index + ord('a')))
        else:
            decrypted_text.append(char)
    return ''.join(decrypted_text)

plaintext = "vroum_crocobeatsmario"
substitutions = ['d', 'g', 'm', 'v', 'y', 'f', 'b', 's', 'r', 'l', 
                 'h', 'i', 'n', 'o', 'q', 'j', 'z', 'e', 'a', 
                 'x', 'c', 'p', 'u', 't', 'w', 'k']

encrypted_text = encrypt(plaintext, substitutions)
print("Plaintext:", plaintext)
print("Encrypted:", encrypted_text)
print("Decrypted:", decrypt(encrypted_text, substitutions))
