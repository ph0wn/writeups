/*
 * This code is based on RadioLib by Jan Gromeš
 * https://github.com/jgromes/RadioLib
 */

#ifndef DRIVERS_CC1101_H
#define DRIVERS_CC1101_H

#include <stdint.h>
#include <zephyr/drivers/spi.h>
#include <zephyr/drivers/gpio.h>

#include "cc1101_const.h"

enum Cc1101SyncType {
    NoSync = 0, Sync15_16, Sync16_16, Sync30_32,
    CarrierSense, CarrierSense15_16, CarrierSense16_16, CarrierSense30_32
};

enum Cc1101PreambleTypes {
    Bytes2 = 0, Bytes3, Bytes4, Bytes6, Bytes8, bytes12, Bytes16, Bytes28
};

enum Cc1101Modulation {
    FSK2, GFSK, ASK_OOK, FSK4, MFSK
};

enum Cc1101SignalDirection {
    Change = 1, Falling = 1, Rising = 2
};


int cc1101_get_reg(const struct device *dev, uint8_t reg, uint8_t *data);

int cc1101_find_chip(const struct device *dev);
int cc1101_set_frequency(const struct device *dev, uint32_t freq);
int cc1101_set_bitrate(const struct device *dev, uint32_t br);
int cc1101_set_modulation(const struct device *dev, enum Cc1101Modulation modulation);
int cc1101_set_output_power(const struct device *dev, int8_t power);

int cc1101_tx (const struct device *dev, uint8_t *packet, int packetlen);

/* Async mode for raw PWM/OOK transmission/reception */
int cc1101_enable_async_mode(const struct device *dev);
int cc1101_disable_async_mode(const struct device *dev);
int cc1101_start_tx(const struct device *dev);
int cc1101_stop_tx(const struct device *dev);
int cc1101_start_rx(const struct device *dev);

struct cc1101_event {
    uint8_t event;
    uint8_t *rx;
    uint8_t len;
};

typedef void (*cc1101_callback_t)(const struct device *, struct cc1101_event *, void *);

int cc1101_add_cb (const struct device *dev, cc1101_callback_t callback, void *user_data);

struct cc1101_cb {
    cc1101_callback_t callback;
    void *user_data;
};

#endif //DRIVERS_CC1101_H
