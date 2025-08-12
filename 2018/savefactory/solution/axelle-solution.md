# Axelle's solution

## Save the factory

It's easier to have an interactive shell to see communication with the server.
For that, uncomment the `embed()` line in `client_example.py`:

```python
# Uncomment if you want to interact with server using IPython
embed()
```

Then, launch the client: `python client_example.py` and obtain an interactive Python shell.

### Subscribing

The description tells us to subscribe to the main board notifications. The `client_example.py` shows how to do this.
So, in the interactive shell, I implement the following helper function:

```python
def subscribe(client, root, handler):
    subscription = client.create_subscription(500, handler)
    event_type = root.get_child(["0:Types", "0:EventTypes", "0:BaseEventType", "2:MainBoardNotif"])
    event_obj = root.get_child("2:MainBoard")
    hilt = subscription.subscribe_events(event_obj, event_type)
```

and call `subscribe(client, root, handler)`


### Re-connecting

We only have limited time to save the factory. If we are too slow, we need to reconnect, and start again.

To reconnect:

```python
client.connect()
```


### Nodes

Nodes are organized in a tree structure. We list all nodes we see under root.
For that, I implement the following helper function:

```python
def show_children(node):
    for child in node.get_children():
        print child.get_display_name()
	print child.get_description()
```

Then, I call `show_children(root)`:

```
LocalizedText(Encoding:2, Locale:None, Text:Objects) <-- this is the one we get with root.get_objects_node()
LocalizedText(Encoding:2, Locale:None, Text:The browse entry point when looking for objects in the server address space.)
LocalizedText(Encoding:2, Locale:None, Text:Types)
LocalizedText(Encoding:2, Locale:None, Text:The browse entry point when looking for types in the server address space.)
LocalizedText(Encoding:2, Locale:None, Text:Views)
LocalizedText(Encoding:2, Locale:None, Text:The browse entry point when looking for views in the server address space.)
LocalizedText(Encoding:2, Locale:None, Text:}HGva_J;uv99)
LocalizedText(Encoding:2, Locale:None, Text:}HGva_J;uv99)
LocalizedText(Encoding:2, Locale:None, Text:0J,BICyQu9\>@)
LocalizedText(Encoding:2, Locale:None, Text:0J,BICyQu9\>@)
LocalizedText(Encoding:2, Locale:None, Text:unit_2)
LocalizedText(Encoding:2, Locale:None, Text:unit_2)
LocalizedText(Encoding:2, Locale:None, Text:unit_4)
LocalizedText(Encoding:2, Locale:None, Text:unit_4)
LocalizedText(Encoding:2, Locale:None, Text:BYsqOwY-)
LocalizedText(Encoding:2, Locale:None, Text:BYsqOwY-)
LocalizedText(Encoding:2, Locale:None, Text:unit_1)
LocalizedText(Encoding:2, Locale:None, Text:unit_1)
LocalizedText(Encoding:2, Locale:None, Text:7PFG6qG+>qtlx/t)
LocalizedText(Encoding:2, Locale:None, Text:7PFG6qG+>qtlx/t)
LocalizedText(Encoding:2, Locale:None, Text:unit_5)
LocalizedText(Encoding:2, Locale:None, Text:unit_5)
LocalizedText(Encoding:2, Locale:None, Text:unit_3)
LocalizedText(Encoding:2, Locale:None, Text:unit_3)
LocalizedText(Encoding:2, Locale:None, Text:MainBoard)
LocalizedText(Encoding:2, Locale:None, Text:MainBoard)
LocalizedText(Encoding:2, Locale:None, Text:BlackBox)
LocalizedText(Encoding:2, Locale:None, Text:BlackBox)
```

The description says to inspect logs of the black box.

```python
blackbox = root.get_child("2:BlackBox")
```

We show the logs using `get_data_value()` of log nodes.
I use the following helper method:

```python
def show_data(node):
     for i in node.get_children():
        print i.get_description()
        print i.get_data_value()
```

```
LocalizedText(Encoding:2, Locale:None, Text:log21)
DataValue(Value:Variant(val:[12-10-2018 14:40] Suspicious read and write operations!,type:VariantType.String), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-12 14:40:22.264266)
LocalizedText(Encoding:2, Locale:None, Text:log23)
DataValue(Value:Variant(val:[12-10-2018 14:40] Suspicious permission change. Some nodes may not respond correctly (including the black box)!,type:VariantType.String), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-12 14:40:22.279319)
LocalizedText(Encoding:2, Locale:None, Text:log24)
DataValue(Value:Variant(val:[12-10-2018 14:40] The cooling system of unit_1 at UTX_3 is NOT responding!,type:VariantType.String), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-12 14:40:52.315650)
```

We see a given unit (`unit_1`) is failing. We have to fix it.
Note carefully the name of the unit (`unit_1`), child (`UTX_3`) and which system is failing (`cooling system`).
This is what we need to fix, and it changes all the time.

You need to list the children of each node to see how things are organized.
For example, `unit_5` is organized in North, West, East, South, and inside, there is a regulator.
In the case below, this regular was failing, and we can list its status using
`show_data(root.get_child('unit_5').get_child('North').get_child('P@regulator_u5_Nort'))`


```
DataValue(Value:Variant(val:13.7494928811,type:VariantType.Double), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-25 14:11:28.558238)
DataValue(Value:Variant(val:NORMAL,type:VariantType.String), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-25 14:10:58.554723)
DataValue(Value:Variant(val:TRUE,type:VariantType.String), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-25 14:10:58.555093)
```

The value `13.7494928811` is not normal, and needs to be reset for example to `NORMAL`.

`root.get_child('unit_5').get_child('North').get_child('P@regulator_u5_Nort').get_children()[0].set_value('NORMAL')`

Now, we have the following status: `show_data(root.get_child('unit_5').get_child('North').get_child('P@regulator_u5_Nort'))`

```
DataValue(Value:Variant(val:NORMAL,type:VariantType.String), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-25 14:14:32.754946)
DataValue(Value:Variant(val:NORMAL,type:VariantType.String), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-25 14:10:58.554723)
DataValue(Value:Variant(val:TRUE,type:VariantType.String), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-25 14:10:58.555093)
```

You will get a notification saying things are back to normal, or you can inspect the black box logs with `show_data(blackbox)`

The issue happens a second time, and you need to fix it the same way.
If you do fix it twice, at the end, you will get the following log:

```
DataValue(Value:Variant(val:[25-10-2018 14:53] Everything has been handled very well. We're back to normal. Congratz! Here's your flag: ph0wn{0pc-M4n_c0mIng_tO_The_R3SCue},type:VariantType.String), StatusCode:StatusCode(Good), SourceTimestamp:2018-10-25 14:53:35.236485)
```

## Track the Hacker

We noticed that some nodes have a very strange name like `}HGva_J;uv99`. When we inspect those nodes, we see they have multiple children whose name is `frg_xxx`

```python
n1 = root.get_child('}HGva_J;uv99')
show_children(n1)
```

For example:

```
LocalizedText(Encoding:2, Locale:None, Text:frg_116)
LocalizedText(Encoding:2, Locale:None, Text:frg_116)
LocalizedText(Encoding:2, Locale:None, Text:frg_96)
```

You can get the value of each of those node using `get_value()`.
The idea is to concatenate each fragment in the right order: starting at `frg_0` and ending at `frg_131`.
Then, you must also notice that most fragments contain 4 bytes, but some of them are shorter. They must be padded with leading zeroes.



```python
goodvalues = ''
for i in range(0,131):
     for j in ['}HGva_J;uv99', '0J,BICyQu9\>@', 'BYsqOwY-', '7PFG6qG+>qtlx/t']:
         try:
             goodvalues = goodvalues + "%08x" % (int(root.get_child(j).get_child('frg_%d' % (i)).get_value(),16))
             print "reading %s frg_%d : %s " % (j, i, "%08x" % (int(root.get_child(j).get_child('frg_%d' % (i)).get_value(),16)))
         except:
             pass
```

This provides the following result:

```
1f8b0800f92bad5b3edd33d6814411400e0373b7b9bddbdcb6576ef140b953d496bb2ff8b3fa0103835288ac4221838ceb84934de71e6eefc412c5258a4b8c2425289a585d8446c2585a88d8d1050322a411ed6d2cd6198d180f4210b211c9fb067696997933f1eaf365abf78696610d26473511488d989027bedfc0b389e1778ae1b446104b6e304b6756906a56abdacd5675c6b2a066c7ede9a9f5cf6db4ff9faafdac7ff98a7f6ca075a395ca1ba2c061e8af5f7f37ecaabf1b452158762ad974d9e6f56f4cd9d7ebb7ecc6f8fe737ec569564e3aadd26dfd5f6785b6ca6affbb93f59b515ca9b7e30bf1c0e5c6e4a6beb151ff7912ffadf776cc7e383f7bfef0701f6ff5648de261f810d974f94811000c20724cb30045412c49772b2220baaa264144dd5754dd5343dd797d3b3f9aca6f51abdf93ec6ca6e7cc82c90afc9f894b8888a4b2ca35956cbb2bf962c82a182022a2506486a106499e435124f90795afee65f023e3dfab844ab2612aa59ee24ebebf8390ae3059e1cbfbf8c544ca64d68449d43041ce949cc2d9abb37793f790a384bf4ed380213f0e8d7cfb3c1fbf5cfaf47a70e87cf1e8d88783f7660f7f7d625e9b3bb0bcab3fbf62d5264e0d37fb1fbd193597f6dc393edd79fa7891d1339db9ddd52ff39d8517d557f174edf7f369ebc4bada808218410428218410420821841042082184b69defc10f39280000
```

We write that to a file:

```python
f = open('thefile', 'w')
f.write(goodvalues)
f.close()
```

```bash
-rw-r--r-- 1 axelle axelle 524 Oct 26 10:48 file.gz
```

The file `thefile` is a GZIP. We `gunzip` and see it is a **tar**. We untar and get the flag in one of the files.

## OPC-UA client GUI

- https://github.com/FreeOpcUa/opcua-client-gui
- `sudo -H pip install opcua-client`
- `sudo apt-get install python3-pyqt5`
