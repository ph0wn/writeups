#ifndef DRIVERS_CC1101_TXRX_H
#define DRIVERS_CC1101_TXRX_H

#include "cc1101.h"

int cc1101_tx (const struct device *dev, uint8_t *packet, int packetlen);

void _cc1101_rx_handler(const struct device *dev, struct gpio_callback *cb, uint32_t pins);

void cc1101_trigger_rx(const struct device *dev);
void cc1101_enable_intr_rx(const struct device *dev);

void _cc1101_rx_thread(void *arg);

#endif
