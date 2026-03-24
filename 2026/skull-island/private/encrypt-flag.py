FLAG_FILE='../FLAG4'

def quick_crypt(s, key=7):
    return bytes((ord(ch) + key) % 256 for ch in s)

def quick_decrypt(data, key=7):
    return ''.join(chr((b - key) % 256) for b in data)

with open(FLAG_FILE, 'r') as f:
    flag = f.read()
print(f'Flag={flag}')
    
encrypted = quick_crypt(flag)
print(f"Encrypted: {encrypted}")

print(f"Decrypted: {quick_decrypt(encrypted)}")
