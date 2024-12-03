cmake_minimum_required(VERSION 3.13)
include(pico_sdk_import.cmake)
project(loader C CXX ASM)
set(CMAKE_C_STANDARD 11)
set(CMAKE_CXX_STANDARD 17)
set(PICO_BOARD pico)
set(PICO_BOOT_STAGE2_FILE ${PICO_SDK_PATH}/src/rp2040/boot_stage2/boot2_generic_03h.S)

pico_sdk_init()

add_executable(loader
  loader.c
  ws2812.pio
 ../src/stage1/picopcb.c
 ../src/stage2/rp2040.c
 )
pico_generate_pio_header(loader ${CMAKE_CURRENT_LIST_DIR}/ws2812.pio)

# set the optimization level
set(CMAKE_C_FLAGS_RELEASE "-O1")  

# enable printf via USB serial
pico_enable_stdio_usb(loader 1)
# disable UART
pico_enable_stdio_uart(loader 0)

# create map, bin, hex and uf2
pico_add_extra_outputs(loader)
target_link_libraries(loader pico_stdlib hardware_pio)
