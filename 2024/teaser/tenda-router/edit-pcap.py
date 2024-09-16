from scapy.all import *
from scapy.layers import http

def modify_http_header(pkt):
    if 'HTTPResponse' in pkt:
        if b'Werkzeug' in pkt['HTTPResponse'].Server:
            print(f"Modifying packet={pkt['HTTPResponse']}")
            pkt['HTTPResponse'].Server = 'Http Server'
            # Recalculate length
            pkt[IP].len = len(pkt)
            del pkt[IP].chksum
            del pkt[TCP].chksum
    if 'HTTPRequest' in pkt:
        if 'Cookie' in pkt['HTTPRequest'].fields:
            print(f"Modifying packet={pkt['HTTPRequest'].Cookie}")
            pkt['HTTPRequest'].Cookie = 'password=56a3a2270f887b02c94126dc03b3a738a2589f'
            
        
    return pkt

# Read pcap file
packets = rdpcap('./public/router.pcapng')

# Modify packets
modified_packets = [modify_http_header(pkt) for pkt in packets]

# Write modified packets to a new pcap file
wrpcap('modified.pcap', modified_packets)
