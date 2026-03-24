#ifndef DRIVERS_CC1101_CONFIG_H
#define DRIVERS_CC1101_CONFIG_H

#include "cc1101.h"

#include <zephyr/device.h>
#include <zephyr/kernel.h>

int cc1101_set_output_power(const struct device *dev, int8_t power);
int cc1101_set_bw(const struct device *dev, float rxBw);
int cc1101_set_deviation(const struct device *dev, float freqDev);
int cc1101_set_sync_type(const struct device *dev, enum Cc1101SyncType type);
int cc1101_set_preamble_length(const struct device *dev, enum Cc1101PreambleTypes type);
int cc1101_set_modulation(const struct device *dev, enum Cc1101Modulation modulation);
int cc1101_set_variable_length_packet(const struct device *dev);
int cc1101_set_sync_words(const struct device *dev, uint8_t w1, uint8_t w2);
int cc1101_enable_crc(const struct device *dev);
int cc1101_enable_whitening(const struct device *dev);
int cc1101_set_maximum_packet_length(const struct device *dev,uint8_t max);

/* Async mode for raw PWM/OOK transmission/reception */
int cc1101_enable_async_mode(const struct device *dev);
int cc1101_disable_async_mode(const struct device *dev);
int cc1101_start_tx(const struct device *dev);
int cc1101_stop_tx(const struct device *dev);
int cc1101_start_rx(const struct device *dev);

struct cc1101_data {
    const struct device *dev;
    struct gpio_callback rx_cback;

    struct cc1101_cb callback;

    uint32_t frequency;
    uint32_t bitrate;
    float power;

    uint8_t variable_length;
    uint8_t fixed_packet_length;

    enum Cc1101Modulation modulation;

    struct k_mutex spi_mutex;
    
    /* RX thread */
    K_KERNEL_STACK_MEMBER(rx_stack, CONFIG_CC1101_RX_STACK_SIZE);
    struct k_thread rx_thread;
    struct k_sem rx_lock;
    atomic_t rx;
};

struct cc1101_config {
    const struct spi_dt_spec spi;
    const struct gpio_dt_spec gdo0, gdo2, ncs;
    const struct cc1101_data data;
};

#endif 