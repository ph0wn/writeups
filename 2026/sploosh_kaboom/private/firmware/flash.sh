nrfjprog --recover --coprocessor CP_APPLICATION
nrfjprog --recover --coprocessor CP_NETWORK
nrfjprog --family NRF53 --program zephyr.hex --chiperase --verify --reset
nrfjprog --force --memwr 0x00FF8000 --val 0x00 --coprocessor CP_APPLICATION
nrfjprog --force --memwr 0x01FF8000 --val 0x00 --coprocessor CP_NETWORK
nrfjprog --pinreset
