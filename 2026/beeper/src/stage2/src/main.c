/*
 * CC1101 OOK PWM RX with Display
 * Receive commands and display screens
 * SPDX-License-Identifier: Apache-2.0
 */

#define DT_DRV_COMPAT ti_cc1101

#include <string.h>
#include <zephyr/kernel.h>
#include <zephyr/drivers/gpio.h>
#include <zephyr/drivers/spi.h>
#include <zephyr/logging/log.h>

#include "cc1101.h"
#include "gfx.h"

#if DT_NUM_INST_STATUS_OKAY(DT_DRV_COMPAT) == 0
#error "cc1101 is not defined in DTS"
#endif

LOG_MODULE_REGISTER(main);

/* Colors */
#define CROCO_GREEN       0x2E05
#define CROCO_LIGHT_GREEN 0x5F26
#define CROCO_DARK_GREEN  0x1B03

/* ========== DISPLAY FUNCTIONS ========== */

static void display_pico_home(void)
{
    gfx_clear(0x34BF);

    /* Crocodile body */
    gfx_fill_circle(100, 130, 45, CROCO_GREEN);
    gfx_fill_circle(130, 135, 35, CROCO_GREEN);
    gfx_fill_circle(105, 145, 25, CROCO_LIGHT_GREEN);

    /* Tail */
    gfx_fill_rect(20, 115, 50, 25, CROCO_GREEN);
    gfx_fill_rect(5, 120, 25, 15, CROCO_GREEN);
    gfx_fill_rect(15, 108, 8, 10, CROCO_GREEN);
    gfx_fill_rect(30, 105, 8, 12, CROCO_GREEN);
    gfx_fill_rect(45, 108, 8, 10, CROCO_GREEN);

    /* Head & snout */
    gfx_fill_circle(165, 115, 30, CROCO_GREEN);
    gfx_fill_rect(175, 105, 55, 30, CROCO_GREEN);
    gfx_fill_rect(220, 110, 15, 22, CROCO_GREEN);
    gfx_fill_circle(230, 118, 3, GFX_BLACK);
    gfx_fill_rect(180, 122, 50, 3, CROCO_DARK_GREEN);

    /* Teeth */
    gfx_fill_rect(190, 125, 4, 8, GFX_WHITE);
    gfx_fill_rect(200, 125, 4, 8, GFX_WHITE);
    gfx_fill_rect(210, 125, 4, 8, GFX_WHITE);
    gfx_fill_rect(220, 125, 4, 6, GFX_WHITE);

    /* Eye */
    gfx_fill_circle(160, 100, 12, GFX_WHITE);
    gfx_fill_circle(163, 100, 6, GFX_BLACK);
    gfx_fill_rect(165, 97, 3, 3, GFX_WHITE);

    /* Legs */
    gfx_fill_rect(140, 160, 15, 30, CROCO_GREEN);
    gfx_fill_rect(135, 185, 25, 10, CROCO_GREEN);
    gfx_fill_rect(70, 155, 15, 30, CROCO_GREEN);
    gfx_fill_rect(65, 180, 25, 10, CROCO_GREEN);

    /* Pirate stuff */
    gfx_fill_circle(160, 100, 14, GFX_BLACK);
    gfx_fill_rect(145, 85, 40, 4, GFX_BLACK);
    gfx_fill_rect(130, 55, 60, 35, GFX_BLACK);
    gfx_fill_rect(120, 85, 80, 10, GFX_BLACK);
    gfx_fill_circle(160, 72, 8, GFX_WHITE);
    gfx_fill_rect(156, 70, 3, 3, GFX_BLACK);
    gfx_fill_rect(162, 70, 3, 3, GFX_BLACK);
    gfx_fill_rect(145, 75, 12, 3, GFX_WHITE);
    gfx_fill_rect(165, 75, 12, 3, GFX_WHITE);
    gfx_fill_circle(135, 130, 6, GFX_YELLOW);
    gfx_fill_circle(135, 130, 3, CROCO_GREEN);
    gfx_fill_rect(40, 110, 25, 12, GFX_RED);

    gfx_draw_string_centered(215, "PICO BEEPER", GFX_WHITE, GFX_WHITE);
}

/* Hidden flag: "ph0wn{rum_is_pirate_drink}" XOR 0x21 */
static const char encoded_flag[] = "QIVOZSTL~HR~QHS@UD~ESHOJ\\";

static void display_rum(void)
{
    uint16_t bottle_brown = GFX_RGB(101, 67, 33);
    uint16_t liquid_amber = GFX_RGB(255, 191, 0);
    uint16_t cork_tan = GFX_RGB(210, 180, 140);
    uint16_t label_cream = GFX_RGB(255, 248, 220);

    gfx_clear(GFX_RGB(40, 26, 13));

    /* Decode and display flag */
    char flag[27];
    for (int i = 0; i < 26; i++) {
        flag[i] = encoded_flag[i] ^ 0x21;
    }
    flag[26] = '\0';
    gfx_draw_string_centered(5, flag, GFX_GREEN, GFX_GREEN);

    /* Table surface */
    gfx_fill_rect(0, 180, 240, 60, GFX_RGB(80, 50, 20));
    gfx_fill_rect(0, 180, 240, 3, GFX_RGB(60, 35, 10));

    /* Bottle */
    int bx = 90, by = 50;
    gfx_fill_rect(bx + 22, by, 16, 30, bottle_brown);
    gfx_fill_rect(bx + 20, by + 25, 20, 10, bottle_brown);
    gfx_fill_rect(bx + 24, by - 15, 12, 18, cork_tan);
    gfx_fill_rect(bx + 23, by - 12, 14, 2, GFX_RGB(180, 150, 110));
    gfx_fill_rect(bx + 10, by + 35, 40, 100, bottle_brown);
    gfx_fill_rect(bx + 5, by + 40, 50, 90, bottle_brown);
    gfx_fill_rect(bx + 8, by + 125, 44, 10, GFX_RGB(80, 50, 25));
    gfx_fill_rect(bx + 12, by + 50, 36, 70, liquid_amber);
    gfx_fill_rect(bx + 25, by + 30, 10, 20, liquid_amber);
    gfx_fill_rect(bx + 15, by + 55, 5, 40, GFX_RGB(255, 220, 100));
    gfx_fill_rect(bx + 10, by + 65, 40, 35, label_cream);
    gfx_fill_rect(bx + 10, by + 65, 40, 3, GFX_RGB(139, 69, 19));
    gfx_fill_rect(bx + 10, by + 97, 40, 3, GFX_RGB(139, 69, 19));
    gfx_fill_circle(bx + 30, by + 78, 8, GFX_WHITE);
    gfx_fill_rect(bx + 26, by + 76, 3, 3, GFX_BLACK);
    gfx_fill_rect(bx + 32, by + 76, 3, 3, GFX_BLACK);
    gfx_fill_rect(bx + 28, by + 82, 5, 2, GFX_BLACK);
    gfx_draw_string(bx + 17, by + 88, "RUM", GFX_BLACK, label_cream);

    /* Glass */
    int gx = 160, gy = 110;
    gfx_fill_rect(gx, gy, 40, 50, GFX_RGB(200, 200, 220));
    gfx_fill_rect(gx + 5, gy + 5, 30, 40, GFX_RGB(40, 26, 13));
    gfx_fill_rect(gx + 5, gy + 20, 30, 25, liquid_amber);
    gfx_fill_rect(gx + 8, gy + 22, 5, 15, GFX_RGB(255, 220, 100));
    gfx_fill_rect(gx + 10, gy + 50, 20, 5, GFX_RGB(200, 200, 220));
    gfx_fill_rect(gx + 15, gy + 55, 10, 15, GFX_RGB(200, 200, 220));
    gfx_fill_rect(gx + 5, gy + 70, 30, 5, GFX_RGB(200, 200, 220));

    /* Coins */
    gfx_fill_circle(50, 195, 8, GFX_YELLOW);
    gfx_fill_circle(70, 200, 7, GFX_YELLOW);
    gfx_fill_circle(200, 198, 6, GFX_YELLOW);

    gfx_draw_string_centered(215, "YO HO HO!", GFX_YELLOW, GFX_YELLOW);
}

static void display_boom(void)
{
    gfx_clear(0x0010);
    gfx_draw_string(70, 30, "BOOM!", GFX_YELLOW, GFX_YELLOW);

    int ex = 120, ey = 140;

    /* Explosion spikes */
    for (int angle = 0; angle < 360; angle += 30) {
        int dx = (angle % 60 == 0) ? 18 : 12;
        int dy = (angle % 60 == 0) ? 18 : 12;
        uint16_t col = (angle % 60 == 0) ? GFX_YELLOW : GFX_ORANGE;
        int sx = 0, sy = 0;
        switch (angle) {
        case 0:   sx = 1;  sy = 0;  break;
        case 30:  sx = 1;  sy = -1; break;
        case 60:  sx = 1;  sy = -1; break;
        case 90:  sx = 0;  sy = -1; break;
        case 120: sx = -1; sy = -1; break;
        case 150: sx = -1; sy = -1; break;
        case 180: sx = -1; sy = 0;  break;
        case 210: sx = -1; sy = 1;  break;
        case 240: sx = -1; sy = 1;  break;
        case 270: sx = 0;  sy = 1;  break;
        case 300: sx = 1;  sy = 1;  break;
        case 330: sx = 1;  sy = 1;  break;
        }
        for (int i = 0; i < 45; i++) {
            int x = ex + (sx * dx * i) / 10;
            int y = ey + (sy * dy * i) / 10;
            if (x >= 0 && x < 237 && y >= 0 && y < 237) {
                gfx_fill_rect(x, y, 4, 4, col);
            }
        }
    }

    gfx_fill_circle(ex, ey, 50, GFX_DARK_RED);
    gfx_fill_circle(ex, ey, 40, GFX_RED);
    gfx_fill_circle(ex, ey, 30, GFX_ORANGE);
    gfx_fill_circle(ex, ey, 20, GFX_YELLOW);
    gfx_fill_circle(ex, ey, 10, GFX_WHITE);

    gfx_fill_rect(50, 80, 8, 8, GFX_RGB(100, 100, 100));
    gfx_fill_rect(180, 90, 6, 6, GFX_RGB(80, 80, 80));
}

static void display_boatattack(void)
{
    uint16_t sea_dark = GFX_RGB(0, 50, 100);
    uint16_t sea_light = GFX_RGB(0, 100, 150);
    uint16_t wood_brown = GFX_RGB(101, 67, 33);
    uint16_t sail_cream = GFX_RGB(245, 235, 220);
    uint16_t fire_colors[] = {GFX_RED, GFX_ORANGE, GFX_YELLOW};

    gfx_fill_rect(0, 0, 240, 60, GFX_RGB(30, 30, 50));
    gfx_fill_rect(0, 60, 240, 40, GFX_RGB(50, 40, 60));
    gfx_fill_rect(0, 100, 240, 140, sea_dark);

    for (int w = 0; w < 240; w += 30) {
        gfx_fill_rect(w, 105 + (w % 20), 25, 4, sea_light);
        gfx_fill_rect(w + 10, 140 + ((w + 10) % 15), 20, 3, sea_light);
    }

    /* Attacking ship */
    int px = 20, py = 130;
    gfx_fill_rect(px, py, 70, 25, wood_brown);
    gfx_fill_rect(px - 5, py + 10, 80, 20, wood_brown);
    gfx_fill_rect(px + 65, py + 5, 15, 8, wood_brown);
    gfx_fill_rect(px + 30, py - 60, 5, 65, GFX_RGB(80, 50, 20));
    gfx_fill_rect(px + 10, py - 50, 40, 35, sail_cream);
    gfx_fill_rect(px + 28, py - 70, 20, 12, GFX_BLACK);
    gfx_fill_circle(px + 38, py - 65, 3, GFX_WHITE);
    gfx_fill_circle(px + 75, py + 15, 6, GFX_YELLOW);
    gfx_fill_circle(px + 75, py + 15, 4, GFX_WHITE);

    for (int i = 0; i < 8; i++) {
        gfx_fill_circle(px + 80 + i * 10, py + 12 - i, 2, GFX_RGB(50, 50, 50));
    }

    /* Target ship on fire */
    int tx = 150, ty = 120;
    gfx_fill_rect(tx, ty + 10, 70, 25, wood_brown);
    gfx_fill_rect(tx - 5, ty + 20, 80, 20, wood_brown);
    gfx_fill_rect(tx + 30, ty - 30, 5, 45, GFX_RGB(80, 50, 20));
    gfx_fill_rect(tx + 15, ty - 20, 30, 25, GFX_RGB(180, 170, 160));

    for (int f = 0; f < 3; f++) {
        int fx = tx + 20 + f * 15;
        int fy = ty - 10;
        gfx_fill_circle(fx, fy, 12 - f * 2, fire_colors[0]);
        gfx_fill_circle(fx, fy - 8, 10 - f * 2, fire_colors[1]);
        gfx_fill_circle(fx, fy - 14, 6 - f, fire_colors[2]);
    }

    gfx_fill_circle(tx + 10, ty, 8, GFX_RED);
    gfx_fill_circle(tx + 60, ty + 5, 7, GFX_RED);
    gfx_fill_circle(tx + 25, ty - 40, 15, GFX_RGB(80, 80, 80));
    gfx_fill_circle(tx + 40, ty - 50, 18, GFX_RGB(100, 100, 100));
    gfx_fill_circle(200, 30, 15, GFX_RGB(200, 200, 180));
    gfx_fill_circle(205, 28, 12, GFX_RGB(30, 30, 50));

    gfx_draw_string_centered(220, "ATTACK!", GFX_RED, GFX_RED);
}

/* ========== COMMAND HANDLING ========== */

/* Command definitions */
struct pico_command {
    const char *cmd;
    void (*display_func)(void);
    uint8_t xor;
};

/* XOR key: "hide_cmd" XOR xor_key = "pico_rum" */
static const uint8_t xor_key[] = {0x18, 0x00, 0x07, 0x0A, 0x00, 0x11, 0x18, 0x09};
#define XOR_KEY_LEN sizeof(xor_key)

static const struct pico_command commands[] = {
    { "pico_attack", display_boatattack, 0 },
    { "hide_cmd",    display_rum,        1 },
    { "pico_boom",   display_boom,       0 },
    { "pico_home",   display_pico_home,  0 },
};

#define NUM_COMMANDS (sizeof(commands) / sizeof(commands[0]))

/* PWM Protocol timings (in microseconds) - for BladeRF */
#define PWM_SHORT_US      376
#define PWM_LONG_US       780
#define PWM_SYNC_US       2209
#define PWM_PERIOD_US     1212

/* Timing tolerances */
#define TOLERANCE_US      200
#define MIN_SHORT_US      (PWM_SHORT_US - TOLERANCE_US)
#define MAX_SHORT_US      (PWM_SHORT_US + TOLERANCE_US)
#define MIN_LONG_US       (PWM_LONG_US - TOLERANCE_US)
#define MAX_LONG_US       (PWM_LONG_US + TOLERANCE_US)
#define MIN_SYNC_US       (PWM_SYNC_US - 500)
#define MAX_SYNC_US       (PWM_SYNC_US + 500)

/* Buffer sizes */
#define MAX_MSG_LEN       64
#define NUM_REPETITIONS   2

/* GDO0 pin - PG15 */
#define GDO0_GPIO_PIN     15

static const struct device *gdo0_gpio;
static uint8_t rx_buffer[NUM_REPETITIONS][MAX_MSG_LEN];
static uint8_t rx_len[NUM_REPETITIONS];
static int current_rep = 0;

static uint32_t pulse_start_time;
static uint32_t pulse_end_time;

enum rx_state { STATE_IDLE, STATE_WAIT_SYNC, STATE_RECEIVING };
static enum rx_state state = STATE_IDLE;

static uint8_t current_byte;
static int bit_count;
static int byte_count;

static inline uint32_t get_time_us(void)
{
    return k_cyc_to_us_floor32(k_cycle_get_32());
}

static int classify_pulse(uint32_t width_us)
{
    if (width_us >= MIN_SYNC_US && width_us <= MAX_SYNC_US) {
        return 2;
    } else if (width_us >= MIN_LONG_US && width_us <= MAX_LONG_US) {
        return 0;
    } else if (width_us >= MIN_SHORT_US && width_us <= MAX_SHORT_US) {
        return 1;
    }
    return -1;
}

static void process_bit(int bit)
{
    current_byte = (current_byte << 1) | bit;
    bit_count++;

    if (bit_count == 8) {
        if (byte_count < MAX_MSG_LEN) {
            rx_buffer[current_rep][byte_count] = current_byte;
            byte_count++;
        }
        current_byte = 0;
        bit_count = 0;
    }
}

static void xor_decode(char *buf, int len)
{
    for (int i = 0; i < len; i++) {
        buf[i] ^= xor_key[i % XOR_KEY_LEN];
    }
}

static void handle_command(const uint8_t *msg, int len)
{
    char cmd_str[MAX_MSG_LEN + 1];
    char decoded_cmd[MAX_MSG_LEN + 1];
    int copy_len = (len < MAX_MSG_LEN) ? len : MAX_MSG_LEN;
    memcpy(cmd_str, msg, copy_len);
    cmd_str[copy_len] = '\0';

    for (int i = 0; i < NUM_COMMANDS; i++) {
        const char *expected;

        if (commands[i].xor) {
            int cmd_len = strlen(commands[i].cmd);
            memcpy(decoded_cmd, commands[i].cmd, cmd_len + 1);
            xor_decode(decoded_cmd, cmd_len);
            expected = decoded_cmd;
        } else {
            expected = commands[i].cmd;
        }

        if (strcmp(cmd_str, expected) == 0) {
            printk("\n>>> %s\n", expected);
            if (commands[i].display_func) {
                commands[i].display_func();
            }
            return;
        }
    }

    printk("\n[RX] Unknown: %s\n", cmd_str);
}

static void finalize_message(void)
{
    if (byte_count > 0) {
        rx_len[current_rep] = byte_count;
        current_rep++;

        if (current_rep >= NUM_REPETITIONS) {
            bool match = true;
            if (rx_len[0] != rx_len[1]) {
                match = false;
            } else {
                for (int i = 0; i < rx_len[0]; i++) {
                    if (rx_buffer[0][i] != rx_buffer[1][i]) {
                        match = false;
                        break;
                    }
                }
            }

            if (match && rx_len[0] > 0) {
                handle_command(rx_buffer[0], rx_len[0]);
            }

            current_rep = 0;
        }
    }

    byte_count = 0;
    bit_count = 0;
    current_byte = 0;
}

static struct gpio_callback gdo0_cb_data;

static void gdo0_callback(const struct device *dev, struct gpio_callback *cb, uint32_t pins)
{
    int pin_state = gpio_pin_get(gdo0_gpio, GDO0_GPIO_PIN);
    uint32_t now = get_time_us();

    if (pin_state) {
        pulse_start_time = now;
        uint32_t gap = now - pulse_end_time;
        if (state == STATE_RECEIVING && gap > 5000) {
            finalize_message();
            state = STATE_WAIT_SYNC;
        }
    } else {
        pulse_end_time = now;
        uint32_t pulse_width = now - pulse_start_time;
        int pulse_type = classify_pulse(pulse_width);

        switch (state) {
        case STATE_IDLE:
        case STATE_WAIT_SYNC:
            if (pulse_type == 2) {
                state = STATE_RECEIVING;
                byte_count = 0;
                bit_count = 0;
                current_byte = 0;
            }
            break;
        case STATE_RECEIVING:
            if (pulse_type == 2) {
                finalize_message();
                byte_count = 0;
                bit_count = 0;
                current_byte = 0;
            } else if (pulse_type >= 0) {
                process_bit(pulse_type);
            }
            break;
        }
    }
}

int main(void)
{
    printk("\n=== CC1101 Pico Receiver ===\n");

    int err;
    const struct device *cc1101;
    const struct device *display;

    k_msleep(500);

    /* Init display */
    display = DEVICE_DT_GET(DT_CHOSEN(zephyr_display));
    if (gfx_init(display) < 0) {
        printk("ERROR: Display init failed\n");
    } else {
        printk("Display: OK\n");
        display_pico_home();
    }

    /* Init GPIO */
    gdo0_gpio = DEVICE_DT_GET(DT_NODELABEL(gpiog));
    if (!device_is_ready(gdo0_gpio)) {
        printk("ERROR: GPIOG not ready\n");
        return -1;
    }
    printk("GPIOG: OK\n");

    /* Init CC1101 */
    cc1101 = DEVICE_DT_GET_ANY(ti_cc1101);
    if (!cc1101 || !device_is_ready(cc1101)) {
        printk("ERROR: CC1101 not ready\n");
        return -1;
    }
    printk("CC1101: OK\n");

    uint8_t chipVer = cc1101_find_chip(cc1101);
    if (chipVer > 0) {
        printk("CC1101 version: 0x%02x\n", chipVer);
    }

    err = cc1101_set_frequency(cc1101, 433920);
    err |= cc1101_set_modulation(cc1101, ASK_OOK);
    err |= cc1101_enable_async_mode(cc1101);
    if (err < 0) {
        printk("ERROR: CC1101 config failed\n");
        return -1;
    }

    gpio_pin_configure(gdo0_gpio, GDO0_GPIO_PIN, GPIO_INPUT);
    gpio_init_callback(&gdo0_cb_data, gdo0_callback, BIT(GDO0_GPIO_PIN));
    gpio_add_callback(gdo0_gpio, &gdo0_cb_data);
    gpio_pin_interrupt_configure(gdo0_gpio, GDO0_GPIO_PIN, GPIO_INT_EDGE_BOTH);

    cc1101_start_rx(cc1101);
    printk("RX @ 433.92 MHz\n");
    printk("Commands: pico_attack,pico_boom, pico_home\n\n");

    while (1) {
        k_msleep(1000);
    }

    return 0;
}
