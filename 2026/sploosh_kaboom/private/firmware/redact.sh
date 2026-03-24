#!/usr/bin/env bash
set -euo pipefail
shopt -s nullglob

for f in *.bin *.elf; do
    out="${f%.*}_redacted.${f##*.}"

    perl -0777 -pe '
        s/PH0WN\{([^}]*)\}/"PH0WN{" . ("*" x length($1)) . "}"/ge
    ' "$f" > "$out"

    echo "Wrote: $out"
done
