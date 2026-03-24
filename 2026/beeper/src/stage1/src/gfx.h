/*
 * Graphics Library for Zephyr Display
 * SPDX-License-Identifier: Apache-2.0
 */

#ifndef GFX_H
#define GFX_H

#include <zephyr/device.h>
#include <zephyr/drivers/display.h>
#include <stdint.h>

/* RGB565 Colors */
#define GFX_BLACK      0x0000
#define GFX_WHITE      0xFFFF
#define GFX_RED        0xF800
#define GFX_GREEN      0x07E0
#define GFX_BLUE       0x001F
#define GFX_YELLOW     0xFFE0
#define GFX_CYAN       0x07FF
#define GFX_MAGENTA    0xF81F
#define GFX_ORANGE     0xFD20
#define GFX_DARK_RED   0x8800

/* Convert RGB888 to RGB565 */
#define GFX_RGB(r, g, b) (((r) & 0xF8) << 8 | ((g) & 0xFC) << 3 | ((b) >> 3))

/**
 * @brief Initialize graphics library
 * @param display Display device
 * @return 0 on success, negative on error
 */
int gfx_init(const struct device *display);

/**
 * @brief Fill entire screen with color
 * @param color RGB565 color
 */
void gfx_clear(uint16_t color);

/**
 * @brief Draw a filled rectangle
 * @param x X position
 * @param y Y position
 * @param w Width
 * @param h Height
 * @param color RGB565 color
 */
void gfx_fill_rect(int x, int y, int w, int h, uint16_t color);

/**
 * @brief Draw a rectangle outline
 * @param x X position
 * @param y Y position
 * @param w Width
 * @param h Height
 * @param color RGB565 color
 * @param thickness Line thickness
 */
void gfx_draw_rect(int x, int y, int w, int h, uint16_t color, int thickness);

/**
 * @brief Draw a filled circle
 * @param cx Center X
 * @param cy Center Y
 * @param r Radius
 * @param color RGB565 color
 */
void gfx_fill_circle(int cx, int cy, int r, uint16_t color);

/**
 * @brief Draw a circle outline
 * @param cx Center X
 * @param cy Center Y
 * @param r Radius
 * @param color RGB565 color
 * @param thickness Line thickness
 */
void gfx_draw_circle(int cx, int cy, int r, uint16_t color, int thickness);

/**
 * @brief Draw a horizontal line
 * @param x Start X
 * @param y Y position
 * @param len Length
 * @param color RGB565 color
 */
void gfx_hline(int x, int y, int len, uint16_t color);

/**
 * @brief Draw a vertical line
 * @param x X position
 * @param y Start Y
 * @param len Length
 * @param color RGB565 color
 */
void gfx_vline(int x, int y, int len, uint16_t color);

/**
 * @brief Draw a line between two points
 * @param x0 Start X
 * @param y0 Start Y
 * @param x1 End X
 * @param y1 End Y
 * @param color RGB565 color
 */
void gfx_line(int x0, int y0, int x1, int y1, uint16_t color);

/**
 * @brief Draw a single character (8x16 font)
 * @param x X position
 * @param y Y position
 * @param c Character to draw
 * @param color Text color
 * @param bg Background color (use same as color for transparent)
 * @return Width of character drawn
 */
int gfx_draw_char(int x, int y, char c, uint16_t color, uint16_t bg);

/**
 * @brief Draw a string
 * @param x X position
 * @param y Y position
 * @param str String to draw
 * @param color Text color
 * @param bg Background color
 * @return Width of string drawn
 */
int gfx_draw_string(int x, int y, const char *str, uint16_t color, uint16_t bg);

/**
 * @brief Draw a string centered horizontally
 * @param y Y position
 * @param str String to draw
 * @param color Text color
 * @param bg Background color
 */
void gfx_draw_string_centered(int y, const char *str, uint16_t color, uint16_t bg);

/**
 * @brief Get display width
 * @return Width in pixels
 */
int gfx_width(void);

/**
 * @brief Get display height
 * @return Height in pixels
 */
int gfx_height(void);

#endif /* GFX_H */
