nrfutil device recover --x-family nrf53
nrfutil device program --x-family nrf53 --firmware zephyr-sans-ecran.hex
nrfutil device reset --x-family nrf53 --reset-kind RESET_PIN
#nrfutil device recover --x-family nrf53 --core Network
#nrfutil device write --x-family nrf53 --core Network --address 0x01FF8000 --value 0x00000000
#nrfutil device write --x-family nrf53 --core Application --address 0x00FF8000 --value 0x00000000
#nrfutil device reset --x-family nrf53 --reset-kind RESET_PIN
