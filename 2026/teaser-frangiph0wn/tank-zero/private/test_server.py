# test_yacht_server.py
import unittest
from unittest import mock
from datetime import datetime, timedelta
import uuid
import importlib
import json


class DummyMCP:
    """Prevents network/server side-effects and keeps @mcp.tool() as identity."""
    def __init__(self, *args, **kwargs):
        pass

    def tool(self):
        def deco(fn):
            return fn
        return deco


with mock.patch("mcp.server.fastmcp.FastMCP", new=DummyMCP):
    yacht_server = importlib.import_module("server")


class TestServer(unittest.TestCase):
    def setUp(self):
        yacht_server.sessions.clear()

    def test_sanitize_session(self):
        uuid = '00def2ce-412b-42aa-ab7d-d152bf16a79d'
        self.assertTrue(yacht_server.sanitize_session_id(uuid))
        self.assertTrue(yacht_server.sanitize_session_id('{'+uuid+'}'))

        with self.assertRaises(ValueError):
            self.assertFalse(yacht_server.sanitize_session_id('#*'))
            self.assertFalse(yacht_server.sanitize_session_id('101-qwerty'))

    def test_connect_creates_session(self):
        sid = yacht_server.connect()
        self.assertIn(sid, yacht_server.sessions)
        self.assertEqual(yacht_server.sessions[sid].session_id, sid)
        self.assertEqual(yacht_server.sessions[sid].fluid.capacity, 200.0)
        self.assertEqual(yacht_server.sessions[sid].fluid.level, 100.0)

    def test_multiple_connect_creates_distinct_sessions(self):
        sid1 = yacht_server.connect()
        sid2 = yacht_server.connect()
        self.assertNotEqual(sid1, sid2)
        self.assertIn(sid1, yacht_server.sessions)
        self.assertIn(sid2, yacht_server.sessions)
        s1 = yacht_server.sessions[sid1]
        s1.fluid.use(20)
        self.assertEqual(s1.fluid.capacity, 180.0)
        s2 = yacht_server.sessions[sid2]
        self.assertEqual(s2.fluid.capacity, 200.0)


    def test_disconnect_existing_session(self):
        sid = yacht_server.connect()
        ok = yacht_server.disconnect(sid)
        self.assertTrue(ok)
        self.assertNotIn(sid, yacht_server.sessions)

    def test_disconnect_missing_session(self):
        sid = uuid.uuid4()
        ok = yacht_server.disconnect(sid)
        self.assertFalse(ok)

    def test_cleanup_sessions_removes_old_only(self):
        old_sid = yacht_server.connect()
        new_sid = yacht_server.connect()

        yacht_server.sessions[old_sid].creation_time = datetime.now() - timedelta(minutes=6)
        yacht_server.sessions[new_sid].creation_time = datetime.now()

        yacht_server.cleanup_sessions()

        self.assertNotIn(old_sid, yacht_server.sessions)
        self.assertIn(new_sid, yacht_server.sessions)

    def test_fluidlevel_use(self):
        f = yacht_server.FluidLevel()
        f.use(10)
        self.assertEqual(f.capacity, 190.0)
        self.assertEqual(f.level, 95.0)

        f.use(500)
        self.assertEqual(f.capacity, 0)
        self.assertEqual(f.level, 0.0)

    def test_connect_raises_when_sessions_too_many(self):
        # connect() raises only when len(sessions) > MAX_SESSIONS (per current code)
        for _ in range(yacht_server.MAX_SESSIONS + 1):
            sid = uuid.uuid4()
            yacht_server.sessions[sid] = yacht_server.SessionData(
                fluid=yacht_server.FluidLevel(),
                creation_time=datetime.now(),
                session_id=sid,
            )

        with self.assertRaises(RuntimeError):
            yacht_server.connect()

    def test_nmea_fast_packets_requires_min_9_bytes(self):
        for n in (0, 1, 3, 6, 8):
            payload = " ".join(["01"] * n) if n else ""
            with self.assertRaises(ValueError):
                yacht_server.nmea_fast_packets(payload, sequence=0, pad=0xFF)

    def test_nmea_fast_packets_two_frames_headers_and_lengths(self):
        # 10 bytes -> first frame carries 6, second carries 4 + padding
        frames = yacht_server.nmea_fast_packets(
            "01 02 03 04 05 06 07 08 09 0A", sequence=3, pad=0xFF
        )
        self.assertEqual(frames[0], "60 0A 01 02 03 04 05 06")   # seq=3 => 0x60, frame=0
        self.assertEqual(frames[1], "61 07 08 09 0A FF FF FF")   # frame=1 => 0x61
        self.assertEqual(len(frames), 2)

    def test_nmea_fast_packets(self):
        frames = yacht_server.nmea_fast_packets( 
            "DE AD BE EF CA FE BA BE DA D0 0D", sequence=0, pad=0x00
        )
        self.assertEqual(len(frames), 2)
        self.assertEqual(frames[0], "00 0B DE AD BE EF CA FE")
        self.assertEqual(frames[1], "01 BA BE DA D0 0D 00 00")

    def test_nmea_fast_packets_min_valid_9_bytes(self):
        # 9 bytes -> two frames: 6 in first, 3 in second + padding
        frames = yacht_server.nmea_fast_packets(
            "01 02 03 04 05 06 07 08 09", sequence=0, pad=0xFF
        )
        self.assertEqual(frames, ["00 09 01 02 03 04 05 06", "01 07 08 09 FF FF FF FF"])

    def test_nmea_fast_packets_argument_validation(self):
        with self.assertRaises(ValueError):
            yacht_server.nmea_fast_packets(None)  # not a string
        with self.assertRaises(ValueError):
            yacht_server.nmea_fast_packets("0 01")  # invalid hex token
        with self.assertRaises(ValueError):
            yacht_server.nmea_fast_packets("00 " * 9, sequence=8)  # sequence out of range
        with self.assertRaises(ValueError):
            yacht_server.nmea_fast_packets("00 " * 9, pad=256)      # pad out of range

    def test_nmea_fast_packets_payload_too_long(self):
        payload = " ".join(["00"] * 224)
        with self.assertRaises(ValueError):
            yacht_server.nmea_fast_packets(payload)

    def test_make_can_id(self):
        can_id = yacht_server.make_can_id(priority=2, pgn=128259, source=0x23)
        self.assertEqual(can_id >> 26 & 0x7, 2)          # priority
        self.assertEqual(can_id & 0xFF, 0x23)          # source
        pgn = yacht_server.extract_pgn(can_id)
        self.assertEqual(pgn, 128259)

        can_id = yacht_server.make_can_id(priority=6, pgn=128275, source=0x23)
        self.assertEqual(can_id, 0x19F51323)
        self.assertEqual(can_id >> 26 & 0x7, 6)          # priority
        self.assertEqual(can_id & 0xFF, 0x23)          # source
        self.assertEqual(can_id >> 25 & 0x1, 0) # Reserved bit
        self.assertEqual(can_id >> 24 & 0x1, 1) # DP
        pgn = yacht_server.extract_pgn(can_id)
        self.assertEqual(pgn, 128275)


    # ---------- Helpers ----------
    def _ensure_constants(self):
        # Avoid NameError if you changed/forgot these constants in the server module.
        if not hasattr(yacht_server, "TANK_SENSOR_SOURCE"):
            setattr(yacht_server, "TANK_SENSOR_SOURCE", 0x21)
        if not hasattr(yacht_server, "ENGINE_SOURCE"):
            setattr(yacht_server, "ENGINE_SOURCE", 0x23)

    def _make_distance_log_frames(self, log_m: int, *, can_id_int: int):
        # Build minimal PGN 128275 payload (14 bytes)
        payload = bytearray(14)
        payload[6:10] = int(log_m).to_bytes(4, "little", signed=False)
        payload_hex = " ".join(f"{b:02X}" for b in payload)
        pkts = yacht_server.nmea_fast_packets(payload_hex, sequence=0, pad=0xFF)
        return json.dumps([{"can_id": can_id_int, "data": p} for p in pkts])

    def _reassemble_fast_packet_payload(self, frames_as_hex_strings):
        # Reassemble payload according to your framing rules (frame 0 uses bytes 2..7, others use 1..7).
        parsed = []
        for s in frames_as_hex_strings:
            b = [int(x, 16) for x in s.split()]
            parsed.append((b[0] & 0x1F, b))
        parsed.sort(key=lambda t: t[0])
        total_len = parsed[0][1][1]
        out = []
        out.extend(parsed[0][1][2:8])
        for _, b in parsed[1:]:
            out.extend(b[1:8])
        return bytes(out[:total_len])

    # ---------- get_fluid_level ----------
    def test_get_fluid_level_valid_packet(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        s = yacht_server.sessions[sid]
        s.fluid.level = 50.0
        s.fluid.capacity = 100.0
        s.fluid.instance = 0

        out = json.loads(yacht_server.get_fluid_level(sid))

        expected_can_id = (6 << 26) | (127505 << 8) | yacht_server.TANK_SENSOR_SOURCE
        self.assertEqual(out["can_id"], expected_can_id)

        raw_level = int(round(50.0 * 250.0)) & 0xFFFF
        raw_capacity = int(round(100.0 * 10.0)) & 0xFFFFFFFF

        pkt = [0xFF] * 8
        pkt[0] = 0x00  # instance=0, fluid_type=0
        pkt[1] = raw_level & 0xFF
        pkt[2] = (raw_level >> 8) & 0xFF
        pkt[3] = raw_capacity & 0xFF
        pkt[4] = (raw_capacity >> 8) & 0xFF
        pkt[5] = (raw_capacity >> 16) & 0xFF
        pkt[6] = (raw_capacity >> 24) & 0xFF
        pkt[7] = 0xFF

        self.assertEqual(out["data"], " ".join(f"{b:02X}" for b in pkt))

    def test_get_fluid_level_invalid_session(self):
        import uuid
        with self.assertRaises(RuntimeError):
            yacht_server.get_fluid_level(uuid.uuid4())

    def test_get_fluid_level_range_checks(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        s = yacht_server.sessions[sid]

        s.fluid.level = 101.0
        with self.assertRaises(RuntimeError):
            yacht_server.get_fluid_level(sid)

        s.fluid.level = 50.0
        s.fluid.capacity = 201.0
        with self.assertRaises(RuntimeError):
            yacht_server.get_fluid_level(sid)

        s.fluid.capacity = 100.0
        s.fluid.instance = 1
        with self.assertRaises(RuntimeError):
            yacht_server.get_fluid_level(sid)

    # ---------- get_fuel_rate ----------
    def test_get_fuel_rate_frames_and_can_id(self):
        self._ensure_constants()
        sid = yacht_server.connect()

        out = json.loads(yacht_server.get_fuel_rate(sid))
        self.assertIsInstance(out, list)

        expected_can_id = (2 << 26) | (127489 << 8) | yacht_server.ENGINE_SOURCE
        self.assertTrue(all(row["can_id"] == expected_can_id for row in out))

        payload = '00 03 00 92 0E 30 75 00 00 B8 0B 00 00 00 00 02 00 02 00 00 00 00 00 00 00'
        expected_frames = yacht_server.nmea_fast_packets(payload, sequence=0, pad=0xFF)
        self.assertEqual([row["data"] for row in out], expected_frames)

    # ---------- get_speed ----------
    def test_get_speed_packet(self):
        self._ensure_constants()
        sid = yacht_server.connect()

        out = json.loads(yacht_server.get_speed(sid))

        expected_can_id = (2 << 26) | (128259 << 8) | yacht_server.ENGINE_SOURCE
        self.assertEqual(out["can_id"], expected_can_id)
        self.assertEqual(out["data"], "01 57 04 00 00 01 FF FF")

    # ---------- alert ----------
    def test_alert_no_flag_branch_contains_text(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        yacht_server.sessions[sid].fluid.level = 10.0  # > 5.0 => "No FLAG..." branch

        out = json.loads(yacht_server.alert(sid))
        self.assertIn("can_id", out)
        self.assertIn("data", out)
        self.assertIsInstance(out["data"], list)

        payload = self._reassemble_fast_packet_payload(out["data"])
        self.assertIn(b"You do not deserve a FLAG yet!", payload)

    def test_alert_flag_branch_contains_flag(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        yacht_server.sessions[sid].fluid.level = 5.0  # <= 5.0 => FLAG branch

        old_flag = getattr(yacht_server, "FLAG", "")
        try:
            yacht_server.FLAG = "TESTFLAG{UNIT}"
            out = json.loads(yacht_server.alert(sid))
            payload = self._reassemble_fast_packet_payload(out["data"])
            self.assertIn(b"TESTFLAG{UNIT}", payload)
        finally:
            yacht_server.FLAG = old_flag

    # ---------- travel ----------
    def test_travel_consumes_fuel_on_valid_distance(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        s = yacht_server.sessions[sid]
        s.fluid.capacity = 200.0
        s.fluid.level = 100.0

        # PGN 128275 CAN ID (priority doesn't matter for travel(), only PGN extraction)
        can_id_int = (2 << 26) | (128275 << 8) | 0x23

        frames = self._make_distance_log_frames(log_m=4000, can_id_int=can_id_int)  # fuel = 30L
        self.assertTrue(isinstance(frames, str))
        ok = yacht_server.travel(sid, frames)
        self.assertTrue(ok)
        self.assertAlmostEqual(s.fluid.capacity, 170.0)
        self.assertAlmostEqual(s.fluid.level, 85.0)

    def test_travel_27kms(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        s = yacht_server.sessions[sid]
        s.fluid.capacity = 200.0
        s.fluid.level = 100.0

        # PGN 128275 CAN ID (priority doesn't matter for travel(), only PGN extraction)
        can_id_int = (2 << 26) | (128275 << 8) | 0x23

        frames = self._make_distance_log_frames(log_m=27000, can_id_int=can_id_int)  # fuel = 300L+
        ok = yacht_server.travel(sid, frames)
        self.assertTrue(ok)
        self.assertTrue(s.fluid.level >= 0.0 )
        self.assertTrue(s.fluid.capacity >= 0.0 )
        self.assertAlmostEqual(s.fluid.capacity, 0.0)
        self.assertAlmostEqual(s.fluid.level, 0.0)

    def test_two_travels(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        s = yacht_server.sessions[sid]
        s.fluid.capacity = 200.0
        s.fluid.level = 100.0

        # PGN 128275 CAN ID (priority doesn't matter for travel(), only PGN extraction)
        can_id_int = (2 << 26) | (128275 << 8) | 0x23

        frames = self._make_distance_log_frames(log_m=4000, can_id_int=can_id_int)  # fuel = 30L
        ok = yacht_server.travel(sid, frames)
        self.assertTrue(ok)

        frames = self._make_distance_log_frames(log_m=4000, can_id_int=can_id_int)  # fuel = 30L
        ok = yacht_server.travel(sid, frames)
        self.assertTrue(ok)

        self.assertAlmostEqual(s.fluid.capacity, 140.0)
        self.assertAlmostEqual(s.fluid.level, 70.0)

    def test_travel_zero_distance_no_change(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        s = yacht_server.sessions[sid]
        s.fluid.capacity = 200.0
        s.fluid.level = 100.0

        can_id_int = (2 << 26) | (128275 << 8) | 0x23
        frames = self._make_distance_log_frames(log_m=0, can_id_int=can_id_int)
        ok = yacht_server.travel(sid, frames)
        self.assertTrue(ok)
        self.assertAlmostEqual(s.fluid.capacity, 200.0)
        self.assertAlmostEqual(s.fluid.level, 100.0)

    def test_travel_rejects_wrong_pgn(self):
        self._ensure_constants()
        sid = yacht_server.connect()

        wrong_can_id = (2 << 26) | (128259 << 8) | 0x23  # not 128275
        frames = self._make_distance_log_frames(log_m=100, can_id_int=wrong_can_id)

        with self.assertRaises(ValueError):
            yacht_server.travel(sid, frames)

    def test_travel_rejects_empty_frames(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        with self.assertRaises(ValueError):
            yacht_server.travel(sid, [])

    def test_travel_rejects_payload_too_short(self):
        self._ensure_constants()
        sid = yacht_server.connect()

        can_id_int = (2 << 26) | (128275 << 8) | 0x23

        # Build 10-byte payload (<14) but still >=9 so it passes your nmea_fast_packets min length check.
        payload = " ".join(["00"] * 10)
        pkts = yacht_server.nmea_fast_packets(payload, sequence=0, pad=0xFF)
        frames = [{"can_id": f"{can_id_int:X}", "data": p} for p in pkts]

        with self.assertRaises(ValueError):
            yacht_server.travel(sid, frames)

    def test_travel_rejects_unrealistic_high_distance(self):
        self._ensure_constants()
        sid = yacht_server.connect()

        can_id_int = (2 << 26) | (128275 << 8) | 0x23
        frames = self._make_distance_log_frames(log_m=10_000_001, can_id_int=can_id_int)

        with self.assertRaises(ValueError):
            yacht_server.travel(sid, frames)

    def test_flag_simu(self):
        self._ensure_constants()
        sid = yacht_server.connect()
        yacht_server.FLAG = "TEST_FLAG"

        can_id_int = (2 << 26) | (128275 << 8) | 0x23
        frames = self._make_distance_log_frames(log_m=27000, can_id_int=can_id_int)
        yacht_server.travel(sid, frames)
        alert = json.loads(yacht_server.alert(sid))
        payload = self._reassemble_fast_packet_payload(alert["data"])
        self.assertIn(b"TEST_FLAG", payload)

    

if __name__ == "__main__":
    unittest.main()
