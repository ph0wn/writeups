/*
 * CC1101 OOK PWM RX - Challenge 1
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

/* ========== DISPLAY FUNCTIONS ========== */

static void display_pico_home(void)
{
    /* Empty */
}

static void display_boom(void)
{
    /* Empty */
}

static void display_boatattack(void)
{
    /* Empty */
}

/* Hidden flag XOR 0x21: "ph0wn{the_real_hidden_cmd_is_XXXXXXXX}" */
static const uint8_t encoded_flag[] = {
    0x51, 0x49, 0x11, 0x56, 0x4f, 0x5a, 0x55, 0x49, 0x44, 0x7e,
    0x53, 0x44, 0x40, 0x4d, 0x7e, 0x49, 0x48, 0x45, 0x45, 0x44,
    0x4f, 0x7e, 0x42, 0x4c, 0x45, 0x7e, 0x48, 0x52, 0x7e, 0x79,
    0x79, 0x79, 0x79, 0x79, 0x79, 0x79, 0x79, 0x5c
};

static void display_rum(void)
{
    /* Decode and display flag */
    char flag[39];
    for (int i = 0; i < 38; i++) {
        flag[i] = encoded_flag[i] ^ 0x21;
    }
    flag[38] = '\0';
    printk("%s\n", flag);
}

/* ========== COMMAND HANDLING ========== */

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

/* PWM Protocol timings */
#define PWM_SHORT_US      376
#define PWM_LONG_US       780
#define PWM_SYNC_US       2209

#define TOLERANCE_US      200
#define MIN_SHORT_US      (PWM_SHORT_US - TOLERANCE_US)
#define MAX_SHORT_US      (PWM_SHORT_US + TOLERANCE_US)
#define MIN_LONG_US       (PWM_LONG_US - TOLERANCE_US)
#define MAX_LONG_US       (PWM_LONG_US + TOLERANCE_US)
#define MIN_SYNC_US       (PWM_SYNC_US - 500)
#define MAX_SYNC_US       (PWM_SYNC_US + 500)

#define MAX_MSG_LEN       64
#define NUM_REPETITIONS   2
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
    printk("Commands: pico_attack, pico_boom, pico_home\n\n");

    while (1) {
        k_msleep(1000);
    }

    return 0;
}
