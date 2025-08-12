import socket
import ob

def http_get(url, port):
    _, _, host, path = url.split('/', 3)
    query = {}
    query['text'] = b"\xb1\x9c\xeb\xd6d\xe2^\xfe'\xa8\x15M-F\x80\x0bE\x05\xef\xd5\x17\xf6HkR\xbd\xa1Z\xf7\xbc\xa9L\xde\x9f\xea\xe1\x82\x17\xda\n\x98\xafk\x94Z.\xc5\r"
    query['length'] = 38
    query = ob.decrypt(query)
    addr = socket.getaddrinfo(host, port)[0][-1]
    path += query
    # hack for ph0wn 2021
    host = '34.140.103.157' 
    s = socket.socket()
    s.connect(addr)
    s.send(bytes('GET /%s HTTP/1.0\r\nHost: %s\r\n\r\n' % (path, host), 'utf8'))
    response = ''
    while True:
        data = s.recv(100)
        if data:
            response += str(data, 'utf8')
        else:
            break
    s.close()
    return response











