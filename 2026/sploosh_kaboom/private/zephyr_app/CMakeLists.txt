cmake_minimum_required(VERSION 3.20.0)
find_package(Zephyr REQUIRED HINTS $ENV{ZEPHYR_BASE})
project(sploosh_kaboom)

zephyr_include_directories(include)
target_sources(app PRIVATE src/logo.c src/main.c)