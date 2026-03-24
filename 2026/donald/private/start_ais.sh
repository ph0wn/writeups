#!/usr/bin/env bash
set -euo pipefail

out_file="/tmp/ais.stdout.log"
err_file="/tmp/ais.stderr.log"

/home/ludovicapvrille/challenge/ais_dump | /home/ludovicapvrille/challenge/ais_server /home/ludovicapvrille/challenge/ais.log /home/ludovicapvrille/challenge/picomsg.log 1>"$out_file" 2>"$err_file"
