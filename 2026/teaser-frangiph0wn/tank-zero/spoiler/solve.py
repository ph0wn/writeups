import json
import httpx
import logging
import re
import struct
import binascii

MCP_URL = "http://34.155.95.172:9999/mcp"

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")

def post_mcp(client: httpx.Client, payload: dict, session_header: str | None):
    headers = {"Accept": "application/json, text/event-stream"}
    if session_header:
        headers["Mcp-Session-Id"] = session_header

    r = client.post(MCP_URL, headers=headers, json=payload)
    ct = r.headers.get("content-type", "")

    # Non-streaming JSON response
    if "application/json" in ct:
        return ("json", r.headers.get("Mcp-Session-Id"), r.json())

    # Streaming SSE response
    if "text/event-stream" in ct:
        messages = []
        for line in r.iter_lines():
            if not line:
                continue
            # Typical SSE: "data: { ...json... }"
            if line.startswith("data:"):
                data = line[len("data:"):].strip()
                try:
                    messages.append(json.loads(data))
                except json.JSONDecodeError:
                    pass
        return ("sse", r.headers.get("Mcp-Session-Id"), messages)

    raise RuntimeError(f"Unexpected content-type {ct}: {r.text}")

def get_uuid(resp):
    s = resp if isinstance(resp, str) else resp["result"]["content"][0]["text"]
    s = (json.loads(s) if isinstance(s, str) and s[:1] in "\"{" else s)
    return re.search(r"[0-9a-fA-F]{8}(-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}", str(s)).group(0)

def extract_pgn(can_id: int) -> int:
    prio = (can_id >> 26) & 0x7
    edp  = (can_id >> 25) & 0x1
    dp   = (can_id >> 24) & 0x1
    pf   = (can_id >> 16) & 0xFF
    ps   = (can_id >> 8)  & 0xFF
    sa   = can_id & 0xFF

    rdp = (edp << 1) | dp  # for N2K this is effectively just dp

    if pf < 0xF0:  
        logging.warning("PDU1 CAN ID encountered pf={pf:02X}. If we do that: bug. If participant does that: his/her error.")
        return (rdp << 16) | (pf << 8)
    else:          
        # PDU2
        return (rdp << 16) | (pf << 8) | ps

def get_level_capacity(resp):
    s = resp if isinstance(resp, str) else resp["result"]["content"][0]["text"]
    s = json.loads(s)
    
    pgn = extract_pgn(int(s["can_id"]))
    if pgn != 127505:
        logging.warning("Unexpected PGN value: pgn={pgn}")
        
    b = bytes(int(x, 16) for x in s["data"].split())
    level = (b[2] << 8) | b[1]
    capacity = (b[6] << 24) | (b[5] << 16) | (b[4] << 8) | b[3]
    logging.debug(f'level={hex(level)} capacity={hex(capacity)}')
    return level * 0.004, capacity * 0.1

def get_speed(resp):
    s = resp if isinstance(resp, str) else resp["result"]["content"][0]["text"]
    s = json.loads(s)
    
    pgn = extract_pgn(int(s["can_id"]))
    if pgn != 128259:
        logging.warning("Unexpected PGN value: pgn={pgn}")
        
    b = bytes(int(x, 16) for x in s["data"].split())
    speed = (b[2] << 8) | b[1]
    return speed * 0.01

def get_fuel_rate(resp):
    s = resp if isinstance(resp, str) else resp["result"]["content"][0]["text"]
    frames = json.loads(s)
    pgn = extract_pgn(int(frames[0]["can_id"]))
    if pgn != 127489:
        logging.warning(f"Unexpected PGN value: pgn={pgn}")
        
    b = bytes(int(x, 16) for f in frames for x in f["data"].split()[1:])
    # we won't be checking the Fast Packet formatting, just grab the fuel rate
    rate = (b[11] << 8) | b[10]  # B8 0B -> 0x0bb8
    logging.debug(f'rate={hex(rate)}')
    return rate * 0.1

def create_travel_packets(distance: int):
    priority = 6
    source = 0x22
    pgn = 0x1f513
    r = 0
    dp = 1
    pf = (pgn >> 8) & 0xff
    ps = pgn & 0xff
    can_id = ((priority & 0x7) << 26) | ((r & 0x1) << 25) | ((dp & 0x1) << 24) | ((pf & 0xFF) << 16) | ((ps & 0xFF) << 8) | (source & 0xFF)
    logging.debug(f'can_id={hex(can_id)}')

    distance = int(distance) & 0xffffffff
    payload = struct.pack('<HIII', 0, 0, distance, distance)
    
    L = len(payload)
    p = list(payload)
    logging.debug(f'payload={binascii.hexlify(payload)} len={L}')

    f0 = [0x00, L] + p[:6]                + [0xFF] * (8 - (2 + 6))
    f1 = [0x01]    + p[6:13]              + [0xFF] * (8 - (1 + 7))
    f2 = [0x02]    + p[13:14]             + [0xFF] * (8 - (1 + 1))
    mk = lambda a: " ".join(f"{x:02X}" for x in a[:8])
    answer = json.dumps([
        {"can_id": can_id, "data": mk(f0)},
        {"can_id": can_id, "data": mk(f1)},
        {"can_id": can_id, "data": mk(f2)},
    ])
    
    logging.debug(f'{answer}')
    return answer

def show_flag(resp):
    o = json.loads(resp) if isinstance(resp, str) else resp
    d = json.loads(o["result"]["content"][0]["text"])["data"]
    b0 = bytes(int(x,16) for x in d[0].split()); L = b0[1]
    pl = b0[2:] + b"".join(bytes(int(x,16) for x in s.split()[1:]) for s in d[1:])
    print(pl[:L].decode("latin1"))
    

with httpx.Client(timeout=60.0) as client:
    # initialize
    kind, sid, init_resp = post_mcp(client, {
        "jsonrpc": "2.0",
        "id": 1,
        "method": "initialize",
        "params": {
            "protocolVersion": "2025-06-18",
            "capabilities": {},
            "clientInfo": {"name": "mcp-script", "version": "1.0.0"},
        },
    }, session_header=None)
    logging.debug('[+] Initialize. session_id = {sid}')

    session_id_header = sid  # may be None if server is stateless

    client.post(
        MCP_URL,
        headers={
            "Accept": "application/json, text/event-stream",
            **({"Mcp-Session-Id": session_id_header} if session_id_header else {}),
        },
        json={"jsonrpc":"2.0","method":"notifications/initialized","params":{}},
    )
    logging.debug('[+] Notifications initialized')

    # Connect -------------------------
    kind, sid2, tool_resp = post_mcp(client, {
        "jsonrpc":"2.0",
        "id": 2,
        "method":"tools/call",
        "params":{ "name":"connect"  }
    }, session_header=session_id_header)
    logging.debug('[+] Calling connect')
    uuid = get_uuid(tool_resp)
    logging.debug('uuid={uuid}')

    # Read Fluid level -----------------
    kind, sid2, tool_resp = post_mcp(client, {
        "jsonrpc":"2.0",
        "id": 2,
        "method":"tools/call",
        "params":{ "name":"get_fluid_level",
                   "arguments" : { "session_id" : uuid  } }
    }, session_header=session_id_header)
    logging.debug('[+] Calling get_fluid_level')
    level, capacity = get_level_capacity(tool_resp)
    logging.info(f'Level={level}% Capacity={capacity} L')

    # Read speed -----------------------
    kind, sid2, tool_resp = post_mcp(client, {
        "jsonrpc":"2.0",
        "id": 2,
        "method":"tools/call",
        "params":{ "name":"get_speed",
                   "arguments" : { "session_id" : uuid  } }
    }, session_header=session_id_header)
    logging.debug('[+] Calling get_speed')
    speed = get_speed(tool_resp)
    logging.info(f'Speed={speed} m/s')

    # Get fuel rate ---------------------
    kind, sid2, tool_resp = post_mcp(client, {
        "jsonrpc":"2.0",
        "id": 2,
        "method":"tools/call",
        "params":{ "name":"get_fuel_rate",
                   "arguments" : { "session_id" : uuid  } }
    }, session_header=session_id_header)
    logging.debug('[+] Calling get_fuel_rate')
    rate = get_fuel_rate(tool_resp)
    logging.info(f'Fuel rate={rate} L/h')

    # Travel -----------------------------
    distance = ((capacity * 3600) / rate) * speed
    logging.info(f'We need to travel {distance} meters')
    frames = create_travel_packets(distance)
    
    kind, sid2, tool_resp = post_mcp(client, {
        "jsonrpc":"2.0",
        "id": 2,
        "method":"tools/call",
        "params":{ "name":"travel",
                   "arguments" : { "session_id" : uuid,
                                   "frames": frames } }
    }, session_header=session_id_header)
    logging.info('[+] Travel done')

    # Read alert --------------------------
    kind, sid2, tool_resp = post_mcp(client, {
        "jsonrpc":"2.0",
        "id": 2,
        "method":"tools/call",
        "params":{ "name":"alert",
                   "arguments" : { "session_id" : uuid } }
    }, session_header=session_id_header)
    logging.debug(f'{json.dumps(tool_resp, indent=2)}')
    logging.info(f'[+] Flag: {show_flag(tool_resp)}')
    
