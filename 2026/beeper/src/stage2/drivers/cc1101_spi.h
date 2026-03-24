#ifndef DRIVERS_CC1101_SPI_H
#define DRIVERS_CC1101_SPI_H

#include "cc1101.h"

int cc1101_txrx(const struct device *dev, uint8_t reg, uint8_t *txb, uint8_t txlen, uint8_t *rxb, uint8_t rxlen);
int cc1101_strobe(const struct device *dev, uint8_t reg);
int cc1101_set_reg(const struct device *dev, uint8_t reg, uint8_t data);
int cc1101_set_regs(const struct device *dev, uint8_t reg, uint8_t *values, uint8_t len);
int cc1101_get_reg(const struct device *dev, uint8_t reg, uint8_t *data);
int cc1101_get_regs(const struct device *dev, uint8_t reg, uint8_t *values, uint8_t len);
int cc1101_set_reg_field (const struct device *dev, uint8_t reg, uint8_t data, uint8_t mask);
int cc1101_get_reg_field (const struct device *dev, uint8_t reg, uint8_t *data, uint8_t mask);
int _idle(const struct device *dev);

#endif
