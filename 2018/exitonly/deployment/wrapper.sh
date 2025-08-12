#!/bin/bash

cd /service/

timeout -k 5 60 qemu-arm /service/exitonly
