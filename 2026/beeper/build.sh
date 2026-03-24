#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "=== Building documentation ==="
typst compile "$SCRIPT_DIR/src/doc/doc.typ"
echo "Done: src/doc/doc.pdf"

echo ""
echo "=== Building Stage 1 firmware ==="
west build -b stm32h573i_dk "$SCRIPT_DIR/src/stage1" -d "$SCRIPT_DIR/src/stage1/build"
echo "Done: src/stage1/build/zephyr/zephyr.elf"

echo ""
echo "=== Populating dist/ ==="
mkdir -p "$SCRIPT_DIR/dist/stage1" "$SCRIPT_DIR/dist/stage2"
cp "$SCRIPT_DIR/src/doc/doc.pdf" "$SCRIPT_DIR/dist/stage1/"
cp "$SCRIPT_DIR/src/doc/doc.pdf" "$SCRIPT_DIR/dist/stage2/"
arm-none-eabi-strip -s "$SCRIPT_DIR/src/stage1/build/zephyr/zephyr.elf" -o "$SCRIPT_DIR/dist/stage1/firmware.elf"
echo "Done: dist/stage1/doc.pdf + firmware.elf (stripped)"
echo "Done: dist/stage2/doc.pdf"

echo ""
echo "=== Build complete ==="
