# SPDX-License-Identifier: Apache-2.0

cmake_minimum_required(VERSION 3.20.0)

set(DTS_ROOT ${CMAKE_CURRENT_LIST_DIR})

find_package(Zephyr REQUIRED HINTS $ENV{ZEPHYR_BASE})

project(challenge2)

target_sources(app PRIVATE
    src/main.c
    src/gfx.c
    drivers/cc1101.c
    drivers/cc1101_spi.c
    drivers/cc1101_config.c
    drivers/cc1101_txrx.c
)

target_include_directories(app PRIVATE
    ${CMAKE_CURRENT_LIST_DIR}/drivers
)
