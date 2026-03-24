# test_load.py
import json
import unittest
import importlib
from unittest import mock
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime
import uuid


SERVER_MODULE = "server"


class DummyMCP:
    """Prevents network/server side-effects and keeps @mcp.tool() as identity."""
    def __init__(self, *args, **kwargs):
        pass

    def tool(self):
        def deco(fn):
            return fn
        return deco


with mock.patch("mcp.server.fastmcp.FastMCP", new=DummyMCP):
    srv = importlib.import_module(SERVER_MODULE)


def ensure_constants():
    if not hasattr(srv, "TANK_SENSOR_SOURCE"):
        setattr(srv, "TANK_SENSOR_SOURCE", 0x21)
    if not hasattr(srv, "ENGINE_SOURCE"):
        setattr(srv, "ENGINE_SOURCE", 0x23)


def make_distance_log_frames(log_m: int):
    # CAN ID with PGN 128275; travel() only checks PGN.
    can_id_int = (2 << 26) | (128275 << 8) | 0x23

    payload = bytearray(14)              # must be >= 14 for travel()
    payload[6:10] = int(log_m).to_bytes(4, "little", signed=False)
    payload_hex = " ".join(f"{b:02X}" for b in payload)
    pkts = srv.nmea_fast_packets(payload_hex, sequence=0, pad=0xFF)

    return json.dumps([{"can_id": can_id_int, "data": p} for p in pkts])


class TestServerLoad(unittest.TestCase):
    def setUp(self):
        ensure_constants()
        srv.sessions.clear()

    def tearDown(self):
        srv.sessions.clear()

    def _session_worker(self, idx: int):
        sid = srv.connect()

        # Call a mix of endpoints; failures should be actionable exceptions, not hangs/crashes.
        json.loads(srv.get_speed(sid))
        json.loads(srv.get_fuel_rate(sid))

        # Avoid flag branch randomness; ensure > 5.0 so it stays in "No FLAG..." path.
        srv.sessions[sid].fluid.level = 10.0
        json.loads(srv.alert(sid))

        # Validate fluid packet generation
        out = json.loads(srv.get_fluid_level(sid))
        if "can_id" not in out or "data" not in out:
            raise AssertionError("Malformed get_fluid_level output")

        # Exercise travel with a small distance (consumes fuel)
        frames = make_distance_log_frames(log_m=1000)  # should burn 7.5 L (1000/1000/40*300)
        ok = srv.travel(sid, frames)
        if ok is not True:
            raise AssertionError("travel() returned False unexpectedly")

        # Disconnect
        srv.disconnect(sid)
        return True

    def test_concurrent_sessions_mixed_calls(self):
        # Keep this moderate so it runs quickly in CI while still providing concurrency coverage.
        workers = 32
        tasks = 200

        # If you want to tune load: increase tasks, not workers, to avoid oversubscribing.
        with ThreadPoolExecutor(max_workers=workers) as ex:
            futs = [ex.submit(self._session_worker, i) for i in range(tasks)]
            for f in as_completed(futs):
                self.assertTrue(f.result())

        # All sessions should be closed
        self.assertEqual(len(srv.sessions), 0)

    def test_session_limit_enforcement_under_load(self):
        # Fill sessions beyond MAX_SESSIONS and verify connect raises.
        # connect() raises only when len(sessions) > MAX_SESSIONS (per your code).
        for _ in range(srv.MAX_SESSIONS + 1):
            sid = uuid.uuid4()
            srv.sessions[sid] = srv.SessionData(
                fluid=srv.FluidLevel(),
                creation_time=datetime.now(),
                session_id=sid,
            )

        with self.assertRaises(RuntimeError):
            srv.connect()


if __name__ == "__main__":
    unittest.main(verbosity=2)
