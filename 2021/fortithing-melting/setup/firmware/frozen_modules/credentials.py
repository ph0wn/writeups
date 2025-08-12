import network

def get():
    ssid = 'fortithing-v1'
    pw = 'boardcreatedbythorsten'
    return ssid, pw

def check_ssid():
    ssid, _ = get()
    wlan = network.WLAN(network.STA_IF)
    if not wlan.isconnected():
        return False, "ERR_CONN"
    current_ssid = wlan.config('essid')
    if current_ssid != ssid:
        return False, "ERR_SSID"
    return True


