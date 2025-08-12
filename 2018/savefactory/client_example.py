import binascii
import struct
from opcua import Client
from IPython import embed
from tragen import *


class SubscriptionHandler(object):
    """
    Subscription Handler. To receive events from server for a subscription
    """
    def datachange_notification(self, node, val, data):
        print("New data change event", node, val)

    def event_notification(self, event):
        print("Mainboard Alert", event.Message.Text)

def main():
    srv_url="opc.tcp://127.0.0.1:XXXX/FearFactory/supervision_unit/"
    client = Client(srv_url)
    client.connect()
    
    root = client.get_root_node()
    objs = client.get_objects_node()
    
    # Subscribe to the main board
    event_obj = root.get_child("2:MainBoard")
    handler = SubscriptionHandler()
    subscription = client.create_subscription(500, handler)
    event_type = root.get_child(["0:Types", "0:EventTypes", "0:BaseEventType", "2:MainBoardNotif"])
    hilt = subscription.subscribe_events(event_obj, event_type)
    #subscription.unsubscribe(hilt)
    #subscription.delete()

    # Uncomment if you want to interact with server using IPython
    embed()

    # Disconnecting
    #client.disconnect()




if __name__ == "__main__":
    main()
