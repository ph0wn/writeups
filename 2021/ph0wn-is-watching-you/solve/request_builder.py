class Request_Builder:
    def __init__(self):
        self.url = "/hello"
        self.host = "0.0.0.0:8000"
        self.add_content_length_header = False
        self.add_content_length_body = False
        self.content_length_offset = 0
        self.cookie = "Cookie: session=c7c3452f-970c-4e09-b9c0-b4da5793a608"
        self.add_chunked_encoding_header = False
        self.add_chunked_encoding_header_value = ""
        self.add_chunked_encoding_body = False
        self.body = ""
        self.extra_body = ""
    
    def build(self):
        startline = "GET {} HTTP/1.1".format(self.url)
        headers = [
            "Host: {}".format(self.host)
        ]
        if self.add_content_length_header:
            # calculate body length including offset
            body_length = str(len(self.body) + self.content_length_offset)
            headers.append("Content-Length: {}".format(body_length))

        if self.add_chunked_encoding_header:
            headers.append("Transfer-Encoding: {}chunked".format(self.add_chunked_encoding_header_value))

        if self.cookie:
            headers.append(self.cookie)

        if self.add_content_length_body and self.add_chunked_encoding_body:
            raise Exception("Can't add a body for both Content-Length and Chunked-Encoding")

        if self.add_content_length_body:
            # include line break after body and extra body
            body = "{}{}".format(self.body, self.extra_body)

        if self.add_chunked_encoding_body:\
            # if the body is empty
            if len(self.body) == 0:
                body = "0{}".format(self.extra_body)
            else:
                # calculate body_length as hex
                body_length = hex(len(self.body)).replace("0x","")
                # for chunked encoding the body looks as follows:
                # <length of body in hex>
                # <body>
                # 0 (end of body)
                # <extra body if provided>
                body = "{}\r\n{}\r\n0{}".format(body_length, self.body, self.extra_body)

        request = "{}\r\n".format(startline)
        # double linebreak after headers
        request += "{}\r\n\r\n".format("\r\n".join(headers))
        if self.add_content_length_body or self.add_chunked_encoding_body:
            # double linebreak after body
            request += "{}\r\n\r\n".format(body)
        return request
