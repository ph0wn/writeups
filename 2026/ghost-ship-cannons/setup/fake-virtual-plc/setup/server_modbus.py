#!/usr/bin/env python3

import os
import asyncio
import logging
from typing import Dict, Optional, Tuple

from pymodbus.datastore import (
    ModbusSequentialDataBlock,
    ModbusDeviceContext,
    ModbusServerContext,
)
from pymodbus.server import StartAsyncTcpServer

# ---------------- Config ----------------
HOST = "0.0.0.0"
PORT = 5020
DEVICE_ID = 1

FAKE_FLAG = os.environ.get("FLAG")
if FAKE_FLAG is None:
    raise RuntimeError("FLAG environment variable must be set")

HR_BASE = 0
HR_COUNT = 64  # enough for the flag
RESET_INTERVAL_SECONDS = 60  # 1 minute

# Your real challenge coil numbers (keep them identical)
COILS: Dict[str, int] = {
    "CAPTAIN_CONTROL": 0x0D,
    "STOP_LOAD": 0x35,
    "RAISE_GUN": 0x43,
    "BALL_READY_CMD": 0x7B,
    "POWDER_READY_CMD": 0xB1,
}

state: Dict[str, int] = {k: 0 for k in COILS.keys()}
flag_available_for_read = False


# ---------------- Helpers ----------------
def str_to_registers(s: str, reg_count: int) -> list[int]:
    b = s.encode("utf-8")
    if len(b) % 2:
        b += b"\x00"
    regs = [(b[i] << 8) | b[i + 1] for i in range(0, len(b), 2)]
    regs = regs[:reg_count]
    regs += [0] * (reg_count - len(regs))
    return regs


def registers_to_ascii(values: list[int]) -> str:
    b = bytearray()
    for v in values:
        b.extend(int(v).to_bytes(2, "big", signed=False))
    return b.replace(b"\x00", b"").decode(errors="ignore")


def is_solved() -> bool:
    return all(state[k] == 1 for k in COILS.keys())


def map_raw_addr_to_name(raw_addr: int) -> Tuple[Optional[str], str]:
    """
    IMPORTANT: Your tests showed the server receives raw_addr = expected + 1.
    Example: you write 0x0D (13) but server sees 14.

    So we map using (raw_addr - 1) to match your real PLC coil numbers.
    """
    shifted = raw_addr - 1
    for name, a in COILS.items():
        if a == shifted:
            return name, f"mapped using (raw_addr-1): {raw_addr}->{shifted}"
    return None, f"unmapped (raw_addr={raw_addr}, raw_addr-1={shifted})"


def update_holding_registers(device: ModbusDeviceContext):
    global flag_available_for_read
    if is_solved():
        regs = str_to_registers(FAKE_FLAG, HR_COUNT)
        device.setValues(3, HR_BASE, regs)
        flag_available_for_read = True
        logging.info("[FLAG] SOLVED -> wrote fake flag into holding registers.")
    else:
        device.setValues(3, HR_BASE, [0] * HR_COUNT)
        flag_available_for_read = False
        logging.info("[FLAG] Not solved yet -> holding registers cleared.")


def reset_plc(device: ModbusDeviceContext, reason: str):
    global flag_available_for_read
    for key in state:
        state[key] = 0

    coil_size = len(device.store["c"].values)
    device.setValues(1, 0, [0] * coil_size)
    device.setValues(3, HR_BASE, [0] * HR_COUNT)
    flag_available_for_read = False

    logging.info(f"[RESET] {reason} | state={state}")


class HookedHoldingRegisterBlock(ModbusSequentialDataBlock):
    def __init__(self, address, values, device: ModbusDeviceContext):
        super().__init__(address, values)
        self.device = device

    def _consume_if_needed(self, address: int, count: int):
        global flag_available_for_read
        read_start = address
        read_end = address + count
        flag_start = HR_BASE
        flag_end = HR_BASE + HR_COUNT
        overlaps_flag = (read_start < flag_end) and (read_end > flag_start)

        if overlaps_flag and flag_available_for_read:
            # consume the one-shot flag immediately after this read succeeds
            flag_available_for_read = False
            logging.info(
                f"[READ] Flag area read at address={address} count={count} -> scheduling one-shot reset."
            )
            reset_plc(self.device, "flag read detected (one-shot consume)")

    def getValues(self, address, count=1):
        result = super().getValues(address, count)
        self._consume_if_needed(address, count)

        return result

    async def async_getValues(self, address, count=1):
        if hasattr(super(), "async_getValues"):
            result = await super().async_getValues(address, count)
        else:
            result = super().getValues(address, count)
        self._consume_if_needed(address, count)
        return result


class HookedDeviceContext(ModbusDeviceContext):
    def _consume_flag_if_read(self, fc_as_hex: int, address: int, count: int, values: list[int]):
        global flag_available_for_read
        if fc_as_hex != 3:
            return

        read_start = address
        read_end = address + count
        flag_start = HR_BASE
        flag_end = HR_BASE + HR_COUNT
        overlaps_flag = (read_start < flag_end) and (read_end > flag_start)
        if not overlaps_flag:
            return

        ascii_payload = registers_to_ascii(values).lower()
        looks_like_flag = "ph0wn{" in ascii_payload
        logging.info(
            f"[READ] FC3 read observed at address={address} count={count} "
            f"overlaps_flag={overlaps_flag} looks_like_flag={looks_like_flag} "
            f"flag_available_for_read={flag_available_for_read}"
        )

        if flag_available_for_read or looks_like_flag:
            flag_available_for_read = False
            logging.info(
                f"[READ] Flag area read via context at address={address} count={count} "
                f"(looks_like_flag={looks_like_flag}) -> triggering one-shot reset."
            )
            reset_plc(self, "flag read detected (one-shot consume)")

    def getValues(self, fc_as_hex, address, count=1):
        values = super().getValues(fc_as_hex, address, count)
        if isinstance(values, list):
            self._consume_flag_if_read(fc_as_hex, address, count, values)
        return values

    async def async_getValues(self, fc_as_hex, address, count=1):
        if hasattr(super(), "async_getValues"):
            values = await super().async_getValues(fc_as_hex, address, count)
        else:
            values = super().getValues(fc_as_hex, address, count)
        if isinstance(values, list):
            self._consume_flag_if_read(fc_as_hex, address, count, values)
        return values


class HookedCoilBlock(ModbusSequentialDataBlock):
    def __init__(self, address, values, device: ModbusDeviceContext):
        super().__init__(address, values)
        self.device = device

    def setValues(self, address, values):
        v_list = values if isinstance(values, list) else [values]

        for i, raw in enumerate(v_list):
            raw_addr = address + i
            val = (
                1
                if (raw is True) or (raw == 1) or (isinstance(raw, int) and raw != 0)
                else 0
            )

            name, note = map_raw_addr_to_name(raw_addr)
            if name:
                state[name] = val
                logging.info(f"[WRITE] raw_addr={raw_addr} value={val} -> {note} | coil={name}")
            else:
                logging.info(f"[WRITE] raw_addr={raw_addr} value={val} -> {note} (ignored)")

        # store coils as 0/1
        norm = [1 if (x is True or x == 1 or (isinstance(x, int) and x != 0)) else 0 for x in v_list]
        super().setValues(address, norm)

        logging.info(f"[STATE] {state} | solved={is_solved()}")
        update_holding_registers(self.device)


async def periodic_reset_task(device: ModbusDeviceContext):
    while True:
        logging.info(f"[TIMER] Next automatic reset in {RESET_INTERVAL_SECONDS} seconds.")
        await asyncio.sleep(RESET_INTERVAL_SECONDS)
        logging.info("[TIMER] Triggering automatic periodic reset now.")
        reset_plc(device, "periodic 1-minute reset")


async def main():
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s | %(levelname)s | %(message)s",
    )

    coil_size = max(COILS.values()) + 32 + 2  # +2 to safely cover shift
    hr_size = HR_BASE + HR_COUNT + 16

    di_block = ModbusSequentialDataBlock(0, [0] * 2000)
    ir_block = ModbusSequentialDataBlock(0, [0] * 200)

    device = HookedDeviceContext(
        di=di_block,
        co=ModbusSequentialDataBlock(0, [0] * coil_size),  # replaced next
        hr=ModbusSequentialDataBlock(0, [0] * hr_size),  # replaced next
        ir=ir_block,
    )

    device.store["c"] = HookedCoilBlock(0, [0] * coil_size, device)
    device.store["h"] = HookedHoldingRegisterBlock(0, [0] * hr_size, device)

    reset_plc(device, "startup initialization")

    # single=True is fine here (one device_id)
    context = ModbusServerContext(devices=device, single=True)

    logging.info(f"[+] Fake PLC Modbus/TCP on {HOST}:{PORT} (device_id={DEVICE_ID})")
    logging.info("Server listening.")

    reset_task = asyncio.create_task(periodic_reset_task(device))
    try:
        await StartAsyncTcpServer(context=context, address=(HOST, PORT))
    finally:
        reset_task.cancel()
        with contextlib.suppress(asyncio.CancelledError):
            await reset_task


if __name__ == "__main__":
    import contextlib

    asyncio.run(main())
