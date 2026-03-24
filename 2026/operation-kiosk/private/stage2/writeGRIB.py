import numpy as np
import matplotlib.pyplot as plt
import random
import re
from typing import Iterable





def _validate_timestamp(base_timestamp):
    if len(base_timestamp) != 12 or not base_timestamp.isdigit():
        raise ValueError("base_timestamp must be a 12-digit string YYYYMMDDhhmm")


def _pack_six_shifts(shifts):
    if len(shifts) != 6:
        raise ValueError("need 6 shift values")
    if any(s < 0 or s > 24 for s in shifts):
        raise ValueError("shift values must be between 0 and 24")
    return "".join(f"{s:02d}" for s in shifts)


def _unpack_six_shifts(timestamp):
    if len(timestamp) != 12 or not timestamp.isdigit():
        raise ValueError("each timestamp must be 12 digits")
    shifts = [int(timestamp[i:i + 2]) for i in range(0, 12, 2)]
    if any(s < 0 or s > 24 for s in shifts):
        raise ValueError("shift values must be between 0 and 24")
    return shifts


def encode_ascii_to_timestamps(plain_text, base_timestamp):
    """
    Encode ASCII text as timestamps keeping first 7 digits fixed (YYYYMM?).
    Each timestamp packs 2 ASCII characters using base (60, 60, 10).
    """
    _validate_timestamp(base_timestamp)
    prefix = base_timestamp[:7]
    timestamps = []
    if len(plain_text) % 2 != 0:
        plain_text += "\x00"
    for i in range(0, len(plain_text), 2):
        chunk = plain_text[i:i + 2]
        codes = [ord(ch) for ch in chunk]
        for code, ch in zip(codes, chunk):
            if code > 127:
                raise ValueError(f"non-ASCII character: {ch!r}")
        value = codes[0] * 128 + codes[1]
        shifts = [0] * 3
        shifts[2] = value % 10
        value //= 10
        shifts[1] = value % 60
        value //= 60
        shifts[0] = value % 60
        timestamps.append(f"{prefix}{shifts[0]:02d}{shifts[1]:02d}{shifts[2]}")
    return timestamps

def add_random_shift_for_grib_format(timestamps):
    """
    Add a random shift to the timestamps for the Grib format.
    the timestamp will always be 12 digits long.
    """

    results = []
    for timestamp in timestamps:
        shift = random.randint(0, 24)
        formated_timestamp = int(timestamp) - shift
        results.append((str(shift), str(formated_timestamp).zfill(12)))
    return results


def decode_timestamps_to_ascii(timestamps):
    """
    Decode timestamps created by encode_ascii_to_timestamps back to ASCII text.
    """
    chars = []
    for ts in timestamps:
        ts_str = str(ts).zfill(12)
        suffix = ts_str[7:]
        shifts = [int(suffix[:2]), int(suffix[2:4]), int(suffix[4])]
        if not (0 <= shifts[0] <= 59 and 0 <= shifts[1] <= 59 and 0 <= shifts[2] <= 9):
            raise ValueError("shift values must be in ranges 0-59,0-59,0-9")
        value = (shifts[0] * 60 + shifts[1]) * 10 + shifts[2]
        if value >= 128 ** 2:
            raise ValueError(f"decoded value out of range: {value}")
        c0 = value // 128
        c1 = value % 128
        chars.extend([chr(c0), chr(c1)])
    return "".join(chars).rstrip("\x00")

def _require_eccodes():
    try:
        import eccodes  # type: ignore[import-not-found]
    except ImportError as exc:
        raise ImportError(
            "eccodes is required. Install with `pip install eccodes`."
        ) from exc
    return eccodes


def readGRIB(grib_file):
    """
    Read a GRIB file and return a list of raw message bytes.
    """
    eccodes = _require_eccodes()
    messages = []
    with open(grib_file, "rb") as file_handle:
        while True:
            gid = eccodes.codes_grib_new_from_file(file_handle)
            if gid is None:
                break
            messages.append(eccodes.codes_get_message(gid))
            eccodes.codes_release(gid)
    return messages

def _grib_message_bytes(message):
    if isinstance(message, (bytes, bytearray, memoryview)):
        return bytes(message)
    if isinstance(message, str):
        raise ValueError("string messages are not valid GRIB data")
    if hasattr(message, "tostring"):
        return message.tostring()
    raise ValueError("unsupported message type for GRIB writing")


def writeGRIB(grib_file, data: Iterable):
    """
    Write a GRIB file from a list of GRIB messages.
    Uses raw GRIB message bytes (from eccodes handles).
    """
    with open(grib_file, "wb") as file_handle:
        for message in data:
            file_handle.write(_grib_message_bytes(message))


def _load_uv_templates(grib_file):
    eccodes = _require_eccodes()
    u_template = None
    v_template = None
    with open(grib_file, "rb") as file_handle:
        while True:
            gid = eccodes.codes_grib_new_from_file(file_handle)
            if gid is None:
                break
            short_name = eccodes.codes_get_string(gid, "shortName")
            level = eccodes.codes_get(gid, "level")
            if short_name == "10u" and level == 10 and u_template is None:
                u_template = eccodes.codes_get_message(gid)
            elif short_name == "10v" and level == 10 and v_template is None:
                v_template = eccodes.codes_get_message(gid)
            eccodes.codes_release(gid)
            if u_template and v_template:
                break
    if not u_template or not v_template:
        raise ValueError("could not find 10u/10v templates in GRIB file")
    return u_template, v_template

def recompute_grib_layer_index(data, additionalIndex:int):
    """
    Recompute the layer index for the Grib data.
    eg: 21:10 metre U wind component:m s**-1 (instant):regular_ll:heightAboveGround:level 10 m:fcst time 0 hrs:from 202601241800
    """
    updatedData = []
    for layer in data:
        matcher = re.match(r"^(\d+):", layer)
        if not matcher:
            updatedData.append(layer)
            continue
        layer_index = int(matcher.group(1)) + additionalIndex
        updatedData.append(f"{layer_index}:{layer[matcher.end():]}")
    return updatedData
    

def appendRandomDataBasedOnTimestamp(data, timestamps: list[tuple[str, str]], grib_file):
    """
    Append random grib data for example:
    data will have a U and V component with a timestamp like the following:
    ------------------------------------------------------------
    1:10 metre U wind component:m s**-1 (instant):regular_ll:heightAboveGround:level 10 m:fcst time 0 hrs:from 202601241800, 2:10 metre V wind component:m s**-1 (instant):regular_ll:heightAboveGround:level 10 m:fcst time 0 hrs:from 202601241800
    ------------------------------------------------------------
    """ 
    eccodes = _require_eccodes()
    u_template, v_template = _load_uv_templates(grib_file)
    for hourShift, timestamp in timestamps:
        timestamp_str = str(timestamp).zfill(12)
        data_date_str = timestamp_str[:8]
        data_time_str = timestamp_str[8:12]
        data_date = int(data_date_str)
        data_time = int(data_time_str)
        forecast_time = int(hourShift)
        for template in (u_template, v_template):
            gid = eccodes.codes_new_from_message(template)
            eccodes.codes_set(gid, "dataDate", data_date)
            eccodes.codes_set(gid, "dataTime", data_time)
            eccodes.codes_set(gid, "forecastTime", forecast_time)
            eccodes.codes_set(gid, "step", forecast_time)
            point_count = eccodes.codes_get(gid, "numberOfPoints")
            values = np.random.rand(point_count).astype("float64")
            eccodes.codes_set_values(gid, values)
            data.append(eccodes.codes_get_message(gid))
            eccodes.codes_release(gid)
    return data


def transformPlainTextToGripChunk(plain_text, base_timestamp):
    """
    Transform a plain text to a GRIB chunk.
    """
    return encode_ascii_to_timestamps(plain_text, base_timestamp)

def main():

    grib_file = 'mod_arome.grib2'
    data = readGRIB(grib_file)
    print(len(data))
   
    msg = "ph0wn{this_M@rine_Grib_stuff_is_N0_JOKE?}"
    base_timestamp = "202603051800"
    timestamps = transformPlainTextToGripChunk(msg, base_timestamp)
    print(timestamps)
    results = add_random_shift_for_grib_format(timestamps)
    print(results)
    print(decode_timestamps_to_ascii(timestamps))
    data = appendRandomDataBasedOnTimestamp(data, results, grib_file)
    output_filename = "updated_mod_arome.grib2"
    writeGRIB(output_filename, data)

main()