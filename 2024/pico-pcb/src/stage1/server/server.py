from http.server import BaseHTTPRequestHandler, HTTPServer

class SimpleRequestHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/pcb-key":
            try:
                # Read the content of the local file 'pcb-key'
                with open("pcb-key", "r") as file:
                    content = file.read()

                self.send_response(200)
                self.send_header("Content-type", "text/plain")
                self.end_headers()
                # Send the content of the file as the response
                self.wfile.write(content.encode("utf-8"))
            except FileNotFoundError:
                # Handle case where the file does not exist
                self.send_response(500)
                self.end_headers()
                self.wfile.write(b"Error: pcb-key file not found.")
            except Exception as e:
                # Handle other potential errors
                self.send_response(500)
                self.end_headers()
                self.wfile.write(f"Error: {e}".encode("utf-8"))
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b"Page not found.")

    def log_message(self, format, *args):
        # Suppress logging
        return

if __name__ == "__main__":
    server_address = ("", 9099)
    httpd = HTTPServer(server_address, SimpleRequestHandler)
    print("Server running on port 9099...")
    httpd.serve_forever()
