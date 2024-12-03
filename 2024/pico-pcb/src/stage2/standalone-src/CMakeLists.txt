cmake_minimum_required(VERSION 3.13)
include(pico_sdk_import.cmake)
project(test_project C CXX ASM)
set(CMAKE_C_STANDARD 11)
set(CMAKE_CXX_STANDARD 17)
pico_sdk_init()
add_executable(flag
 flag.c
)
# set the optimization level
set(CMAKE_C_FLAGS_RELEASE "-O1")  

# enable printf via USB serial
pico_enable_stdio_usb(flag 1)
# disable UART
pico_enable_stdio_uart(flag 0)

# create map, bin, hex and uf2
pico_add_extra_outputs(flag)
target_link_libraries(flag pico_stdlib)
