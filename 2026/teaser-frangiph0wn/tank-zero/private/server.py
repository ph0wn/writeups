from mcp.server.fastmcp import FastMCP
from mcp.server.transport_security import TransportSecuritySettings
from dataclasses import dataclass, field
from datetime import datetime, timedelta
from typing import Dict
import uuid
from uuid import UUID
import json
import logging
import re
import os

# Source addresses
TANK_SENSOR_SOURCE = 0x21
LOG_DEVICE_SOURCE = 0x22
ENGINE_SOURCE = 0x23

# Configure logging
def _configure_logging() -> None:
    level_name = os.getenv("LOG_LEVEL", "INFO").upper()
    level = getattr(logging, level_name, logging.INFO)

    # Ensure Python log records go to stdout (Docker logs)
    handler = logging.StreamHandler()
    formatter = logging.Formatter(
        "%(asctime)s - %(levelname)s - %(name)s - %(funcName)s - %(message)s"
    )
    handler.setFormatter(formatter)

    root = logging.getLogger()
    root.setLevel(level)

    # Avoid duplicate handlers if something else configured logging first (gunicorn/uvicorn, reloads, etc.)
    if not any(isinstance(h, logging.StreamHandler) for h in root.handlers):
        root.addHandler(handler)

def silence_noisy_loggers():
    # Default levels; override via env if you want
    mcp_level = os.getenv("MCP_LOG_LEVEL", "INFO").upper()
    uvicorn_access_level = os.getenv("UVICORN_ACCESS_LOG_LEVEL", "INFO").upper()

    for name in [
        "mcp",
        "mcp.server",
        "mcp.server.streamable_http",
        "mcp.server.streamable_http_manager",
        "mcp.server.lowlevel",
        "sse_starlette.sse"
    ]:
        logging.getLogger(name).setLevel(getattr(logging, mcp_level, logging.INFO))

    # This logger is responsible for the per-request lines in many setups
    logging.getLogger("uvicorn.access").setLevel(
        getattr(logging, uvicorn_access_level, logging.INFO)
    )


_configure_logging()
silence_noisy_loggers()
logger = logging.getLogger(__name__)

class FluidLevel:
    def __init__(self):
        self.instance = 0
        # level is a percentage
        self.level = 100.0
        # capacity is expressed in liters
        self.capacity = 200.0

    def use(self, amount: int):
        logger.debug(f'Using {amount}L - Old capacity={self.capacity} and level={self.level}')
        if amount > self.capacity:
            self.capacity = 0
        else:
            self.capacity = self.capacity - amount
        self.level = (float) (self.capacity / 2)
        logger.debug(f'New capacity={self.capacity} and level={self.level}')

    def __str__(self):
        print(f'Instance: {self.instance} Level: {self.level:.1f}% Capacity: {self.capacity:.1f}')

@dataclass
class SessionData:
    fluid: FluidLevel
    creation_time: datetime
    session_id: UUID

sessions: Dict[UUID, SessionData] = {}
MAX_SESSIONS = 1000

def cleanup_sessions():
    now = datetime.now()
    to_delete = []
    for sid, data in sessions.items():
        if now - data.creation_time > timedelta(minutes=5):
            logger.debug(f'uuid={sid} creation_time={data.creation_time} current={now}: too old')
            to_delete.append(sid)
    for sid in to_delete:
        logger.debug(f'Deleting obsolete session uuid={sid}')
        del sessions[sid]

        
# -------------- Create an MCP server
# Read host/port from environment so we don't hard-code remote IPs in the script.
# If EXTERNAL_HOST is not provided we disable DNS-rebinding protection so the
# server still runs in local/dev setups without an externally-known host.
EXTERNAL_HOST = os.getenv("EXTERNAL_HOST", "").strip()
PORT = int(os.getenv("PORT", os.getenv("MCP_PORT", "9999")))

if EXTERNAL_HOST:
    transport_security = TransportSecuritySettings(
        enable_dns_rebinding_protection=True,
        allowed_hosts=[f"{EXTERNAL_HOST}:{PORT}"],
        allowed_origins=[f"http://{EXTERNAL_HOST}:{PORT}"],
    )
else:
    transport_security = TransportSecuritySettings(enable_dns_rebinding_protection=False)

mcp = FastMCP(
    "Luxury Yacht MCP Server",
    json_response=True,
    port=PORT,
    transport_security=transport_security,
)


yachts = []
FLAG='ph0wn{the_cR0wn_iS_sh1ning}'



@mcp.tool()
def connect() -> UUID:
    if len(sessions) > MAX_SESSIONS:
        raise RuntimeError('Too many sessions. Try again later.')
    sid = uuid.uuid4()
    
    data = SessionData(
        fluid=FluidLevel(),
        creation_time=datetime.now(),
        session_id=sid
    )
    sessions[sid] = data
    logger.debug(f'[+] Created new session: uuid={sid} time={data.creation_time}')
    return sid

@mcp.tool()
def disconnect(session_id: UUID) -> bool:
    if session_id in sessions:
        del sessions[session_id]
        logger.debug(f'[+] Successfully disconnected uuid={session_id}')
        return True
    logger.error(f'[-] uuid={session_id} not found')
    return False

def nmea_fast_packets(payload_hex: str, sequence: int = 0, pad: int = 0xFF):
    if not isinstance(payload_hex, str):
        raise ValueError("Missing payload_hex string")
    parts = payload_hex.strip().split()

    HEX2 = re.compile(r"^[0-9A-Fa-f]{2}$")
    if not parts or any(not HEX2.fullmatch(p) for p in parts) or not (0 <= sequence <= 7) or not (0 <= pad <= 255):
        raise ValueError("Invalid arguments")

    # split the payload in multiple NMEA-2000 fast packet frames
    data = [int(p, 16) for p in parts]
    n = len(data)
    if n > 223:
        raise ValueError("payload too long (max 223 bytes)")
    if n <= 8:
        raise ValueError("payload too short (min 9 bytes for fast packet)")

    frames = []
    i = 0
    frame = 0
    seq = (sequence & 7) << 5

    # first frame: hdr + total_len + 6 data bytes
    f = [seq | frame, n] + data[:6]
    f += [pad] * (8 - len(f))
    frames.append(" ".join(f"{b:02X}" for b in f))
    i = 6

    # following frames: hdr + 7 data bytes
    while i < n:
        frame += 1
        f = [seq | (frame & 31)] + data[i:i+7]
        f += [pad] * (8 - len(f))
        frames.append(" ".join(f"{b:02X}" for b in f))
        i += 7

    return frames

def sanitize_session_id(session_id) -> UUID:
    """
    Accepts UUID or UUID string, returns UUID.
    Raises ValueError on anything invalid/unexpected.
    """
    if isinstance(session_id, UUID):
        return session_id

    if isinstance(session_id, str):
        s = session_id.strip()
        if s.startswith("{") and s.endswith("}"):
            s = s[1:-1].strip()
        # UUID(...) validates format; will raise ValueError if invalid
        return UUID(s)

    raise ValueError("Invalid session_id type")

def make_can_id(priority: int, pgn: int, source: int, dest: int = 0xFF) -> int:
    """
    Build a 29-bit NMEA2000 CAN identifier (ID28..ID0).

    Inputs:
      priority: 0..7
      pgn: 0..0x3FFFF (18-bit PGN; includes DP in bit 16, PF in bits 8..15, PS in bits 0..7 for PDU2)
      source: 0..255
      dest: 0..255 (used only for PDU1; for PDU2 it is ignored and PS comes from PGN)

    Returns:
      29-bit CAN ID as int: (priority<<26) | (R<<25) | (DP<<24) | (PF<<16) | (PS<<8) | SA
      where R (EDP) is always 0 for NMEA2000.
    """
    if not (0 <= priority <= 7 and 0 <= source <= 0xFF and 0 <= dest <= 0xFF and 0 <= pgn <= 0x3FFFF):
        raise ValueError("Invalid priority/pgn/source/dest")

    r = 0  # NMEA2000 reserved/EDP bit is always 0

    # PGN decomposes as: [RDP(2 bits)][PF(8 bits)][PS/00(8 bits)]
    dp = (pgn >> 16) & 0x01          # DP is bit 16 of PGN (RDP high bit is always 0 for N2K)
    pf = (pgn >> 8) & 0xFF
    ps_from_pgn = pgn & 0xFF

    if pf < 0xF0:                    # PDU1: PS is destination, PGN low byte is 0
        ps = dest
    else:                            
        # PDU2: PS is group extension (part of PGN); destination is global
        # we should always be in this case for this server
        ps = ps_from_pgn

    can_id = ((priority & 0x7) << 26) | ((r & 0x1) << 25) | ((dp & 0x1) << 24) | ((pf & 0xFF) << 16) | ((ps & 0xFF) << 8) | (source & 0xFF)
    return can_id

def extract_pgn(can_id: int) -> int:
    """
    Extract PGN from a 29-bit NMEA2000 CAN identifier.

    Rules:
      - priority: bits 26..28 (0..7)
      - R/EDP: bit 25 (must be 0 for NMEA2000)
      - DP: bit 24
      - PF: bits 16..23
      - PS: bits 8..15 (dest for PDU1, group extension for PDU2)
      - SA: bits 0..7

    PGN formation:
      - RDP is 2 bits: [EDP][DP]; for N2K EDP must be 0, so RDP == DP.
      - If PF < 0xF0 (PDU1): PGN = (RDP<<16) | (PF<<8) | 0x00
      - Else (PDU2):         PGN = (RDP<<16) | (PF<<8) | PS
    """
    if not isinstance(can_id, int):
        raise ValueError("Invalid can_id")
    if can_id < 0 or can_id > 0x1FFFFFFF:   # must fit 29 bits
        raise ValueError("Invalid can_id")

    prio = (can_id >> 26) & 0x7
    edp  = (can_id >> 25) & 0x1
    dp   = (can_id >> 24) & 0x1
    pf   = (can_id >> 16) & 0xFF
    ps   = (can_id >> 8)  & 0xFF
    sa   = can_id & 0xFF

    # Basic sanity checks for NMEA2000
    #if prio > 7 or sa > 0xFF or pf > 0xFF or ps > 0xFF:
    #    raise ValueError("Invalid can_id")
    #if edp != 0:
    #    raise ValueError("Invalid can_id (EDP/R bit must be 0 for NMEA2000)")

    rdp = (edp << 1) | dp  # for N2K this is effectively just dp

    if pf < 0xF0:  
        logger.warning("PDU1 CAN ID encountered pf={pf:02X}. If we do that: bug. If participant does that: his/her error.")
        return (rdp << 16) | (pf << 8)
    else:          
        # PDU2
        return (rdp << 16) | (pf << 8) | ps



@mcp.tool()
def get_fluid_level(session_id: UUID) -> str:
    """
    Returns the current level of tank 0 as a NMEA-2000 packet in a JSON string:
    {"can_id": int, "data": str }
    Raises RuntimeError on invalid session ID.    
    """
    session_id = sanitize_session_id(session_id)
    cleanup_sessions()
    if session_id not in sessions:
        raise RuntimeError('Invalid session ID')
    data = sessions[session_id]

    # those should not occur
    if data.fluid.level < 0.0 or data.fluid.level > 100.0:
        raise RuntimeError('Fluid level out of range')
    
    if data.fluid.capacity < 0.0 or data.fluid.capacity > 200.0:
        raise RuntimeError('Fluid capacity out of range')
    
    if data.fluid.instance != 0:
        raise RuntimeError('Invalid fluid instance')

    # Build CAN ID
    can_id = make_can_id(priority= 6, pgn=127505, source=TANK_SENSOR_SOURCE)

    # Build payload
    FLUID_TYPE = 0 # fuel

    # resolution of fluid level is 0.4% so / 0.004 = * 250
    raw_level = int(round(data.fluid.level * 250.0)) & 0xFFFF

    # resolution of capacity is 0.1 litre
    raw_capacity = int(round(data.fluid.capacity * 10.0)) & 0xFFFFFFFF

    # creating the payload
    pkt = [0xFF] * 8

    # Byte 0: 4 bits Instance, 4 bits Type (Fuel)
    pkt[0] = ((data.fluid.instance & 0x0F) << 4) | (FLUID_TYPE & 0x0F)

    # Bytes 1–2: Level (little-endian)
    pkt[1] = raw_level & 0xFF
    pkt[2] = (raw_level >> 8) & 0xFF

    # Bytes 3–6: Capacity (little-endian, 32-bit)
    pkt[3] = raw_capacity & 0xFF
    pkt[4] = (raw_capacity >> 8) & 0xFF
    pkt[5] = (raw_capacity >> 16) & 0xFF
    pkt[6] = (raw_capacity >> 24) & 0xFF

    # Byte 7: Reserved → 0xFF (already set by default)

    data_str = " ".join(f"{b:02X}" for b in pkt)
    packet = { "can_id": can_id, "data": data_str }
    logger.debug(f'uuid={session_id} answer: {packet}')
    return json.dumps(packet)
    

@mcp.tool()
def get_fuel_rate(session_id: UUID) -> str:
    """
    Current fuel rate in L/h for the yacht corresponding to user's session ID.
    Returns a JSON string for NMEA-2000 Engine Parameters, Fast Packet Frames.
    Raises RuntimeError on invalid session ID.    
    """
    session_id = sanitize_session_id(session_id)
    cleanup_sessions()
    if session_id not in sessions:
        raise RuntimeError('Invalid session ID')
    data = sessions[session_id]

    # CAN header
    can_id = make_can_id(priority=2, pgn=127489, source=ENGINE_SOURCE)

    '''
    PGN 127489 - Engine Parameters, Dynamic
    instance: 00
    Oil pressure: 300 Pa, resolution 100 Pa = 03 00
    Oil temperature: 373 K, resolution 0.1 K = 92 0E
    Temperature: 300K, resolution 0.01K = 30 75
    Alternator potential: 00 00 
    Fuel rate: 300 L/h, resolution 0.1 L/h = B8 0B
    Total engine hours: 00 00 00 00
    Coolant pressure: 200 Pa, resolution 100 Pa = 02 00
    Fuel pressure: 2000 Pa, resolution 1000 Pa = 02 00
    All other values are ignored (0)    
    reserved (8 bits),  status 1 (16), status 2 (16), engine load (8)
    '''
    packets = nmea_fast_packets('00 03 00 92 0E 30 75 00 00 B8 0B 00 00 00 00 02 00 02 00 00 00 00 00 00 00')

    rows = [ 
        {"can_id": can_id, "data": pkt}
        for pkt in packets
    ]
    logger.debug(f'uuid={session_id} JSON array: {rows}')
    return json.dumps(rows)

@mcp.tool()
def get_speed(session_id: UUID) -> str:
    """
    Current speed in m/s for the yacht
    Returns a JSON string for NMEA-2000 water speed single-frame packet
    Raises RuntimeError on invalid session ID.    
    """
    session_id = sanitize_session_id(session_id)
    cleanup_sessions()
    if session_id not in sessions:
        raise RuntimeError('Invalid session ID')
    data = sessions[session_id]

    can_id = make_can_id(priority=2, pgn=128259, source=ENGINE_SOURCE)

    # this is a speed of 40 km/h (11.11 m/s) for PGN 128259
    packet = { "can_id": can_id, "data": '01 57 04 00 00 01 FF FF' }
    logger.debug(f'uuid={session_id} JSON packet: {packet}')
    return json.dumps(packet)

@mcp.tool()
def alert(session_id: UUID) -> str:
    """
    Returns the current alert as a JSON string NMEA-2000 alert text - fast packet frames.
    Unused/non implemented fields get placeholder values.
    Raises RuntimeError on invalid session ID.    
    """
    session_id = sanitize_session_id(session_id)
    cleanup_sessions()
    if session_id not in sessions:
        raise RuntimeError('Invalid session ID')
    data = sessions[session_id]

    alert_text = 'You do not deserve a FLAG yet!'
    if data.fluid.level > 5.0:
        logger.debug(f'uuid={session_id} Fluid level={data.fluid.level} - no flag yet.')
    else:
        logger.info(f'uuid={session_id} Fluid level={data.fluid.level} is low. Generating alert packet with flag :)')
        alert_text = FLAG
    
    can_id = make_can_id(priority=6, pgn=126983, source=LOG_DEVICE_SOURCE)

    # alert type: 5 (Warning)
    # alert category: 1 (technical)
    # alert system: 1
    # alert subsystem: 1
    # alert id: 0x1337: 37 13
    # data source instance: 0x22
    # data source index: 0x22
    # language id: 0x00 (English US)
    # 51 01 01 01 37 13 00 00 00 00 00 00 00 00 22 22 01 00 <TEXT>
    payload = '51 01 01 01 37 13 00 00 00 00 00 00 00 00 22 22 01 00'
    for i in range(0, len(alert_text)):
        payload = payload + ' ' + f'{ord(alert_text[i]):02X}'
    logger.debug(f'uuid={session_id} alert payload={payload}')

    packet = { "can_id": can_id, "data":  nmea_fast_packets(payload, sequence=0, pad=0x00) }
    logger.debug(f'uuid={session_id} - packet={packet}')
    return json.dumps(packet)

@mcp.tool()
def travel(session_id: UUID, frames: str) -> bool:
    """
    Simulate travel from NMEA-2000 fast packets.

    session_id: UUID of the user (from connect())
    frames: a JSON string representing the NMEA-2000 Distance Log (PGN 128275) packets.
    This is a fast-packet PGN, so we expect something like:
    '[ { "can_id": int, "data": "payload of packet 1" },
       { "can_id": int, "data": "payload of packet 2" },
       { "can_id": int, "data": "payload of packet 3" },
       ... ]'
    And each payload is a space-separated hex string representing the 8 bytes of that packet.
    Example: see output of get_fuel_rate() for formatting.

    Travel log is *not* cumulative, but last distance since last call.
    Trip log and date/time fields are ignored (not implemented).

    If your input is correct, the vessel will consume fluid based on the current speed
    and fuel rate.
    If your input is incorrect, nothing happens, and the function returns False.

    Raises ValueError if input is invalid.
    Raises RuntimeError on invalid session ID.    
    """
    session_id = sanitize_session_id(session_id)
    if not isinstance(frames, str):
        raise ValueError("Invalid frames")
    frames = frames.strip()
    if not frames:
        raise ValueError("Invalid frames")
    
    try:
        frames = json.loads(frames)
    except json.JSONDecodeError:
        raise ValueError("Invalid JSON string")
    
    logger.debug(f'uuid={session_id} frames={frames}')
    cleanup_sessions()
    if session_id not in sessions:
        raise RuntimeError('Invalid session ID')
    data = sessions[session_id]
    
    # Parse data and create array of frames with (frame_index, data_bytes)
    parsed_frames = []
    for f in frames:
        # Check PGN is 128275
        can_id_int = f["can_id"]
        logger.debug(f'CAN ID: {can_id_int} (hex={hex(can_id_int)})')
        pgn = extract_pgn(can_id_int)
        if pgn != 128275:
            logger.debug(f'uuid={session_id} Unexpected PGN for frame={f} pgn={pgn}')
            raise ValueError("Not PGN 128275 frames")
        # we don't check priority nor source address
        # we could if we wanted this to be more difficult

        data_str = f["data"]
        data_bytes = [int(b, 16) for b in data_str.split()]
        if not data_bytes:
            continue

        # sequence index high 3 bits
        # frame index lower 5 bits
        frame_index = data_bytes[0] & 0x1F  # lower 5 bits: 0,1,2,...
        logger.debug(f'frame_index={frame_index} sequence_index={data_bytes[0] >> 5 & 0x07} data_bytes={data_bytes}')
        parsed_frames.append((frame_index, data_bytes))

    if not parsed_frames:
        raise ValueError("No valid data bytes in frames")

    # sort frame based on frame index
    parsed_frames.sort(key=lambda x: x[0])

    # read length
    first_index, first_data = parsed_frames[0]
    logger.debug(f'First frame: index={first_index} data={first_data}')
    if len(first_data) < 2:
        raise ValueError("First frame too short to contain length")

    total_len = first_data[1]
    logger.debug(f'uuid={session_id} total_len={total_len}')
    if total_len <= 0:
        raise ValueError(f"Invalid payload length: {total_len}")

    # Reassemble payload: frame 0 uses bytes 2..7, others use bytes 1..7
    payload = []
    payload.extend(first_data[2:8])

    for idx, data_bytes in parsed_frames[1:]:
        payload.extend(data_bytes[1:8])

    # Trim to declared length
    payload = payload[:total_len]
    logger.debug(f'payload={payload}')

    # PGN 128275 layout (14 bytes total):
    #   0-1: Date (days since 1970-01-01)       [ignored]
    #   2-5: Time (seconds since midnight)      [ignored]
    #   6-9: Log (meters), uint32 LE
    #  10-13: Trip Log (meters), uint32 LE      [ignored]
    if len(payload) < 14:
        raise ValueError(f"Payload too short for PGN 128275: {len(payload)} bytes")

    log_m = int.from_bytes(bytes(payload[6:10]), byteorder="little", signed=False)
    logger.debug(f'uuid={session_id} log={log_m} m')

    if log_m == 0:
        # no distance, no travel
        return True
    
    if log_m > 10000000:
        # this distance is far too high
        raise ValueError(f'Unrealistic high log distance')

    # Calculate burned fuel based on speed and fuel rate
    fuel = (log_m / 1000.0) / 40.0 * 300.0
    logger.debug(f'uuid={session_id} Using {fuel} liters')
    if fuel > 0: 
        data.fluid.use(fuel)

    return True

        
# Run with streamable HTTP transport
if __name__ == "__main__":
    mcp.run(transport="streamable-http")
