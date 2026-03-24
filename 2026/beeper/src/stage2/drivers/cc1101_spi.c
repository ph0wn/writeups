#include "cc1101_spi.h"
#include "cc1101_const.h"
#include "cc1101_config.h"

#include <zephyr/logging/log.h>

#include <zephyr/sys/util.h>

LOG_MODULE_REGISTER(cc1101_spi, 4);

/* in master mode, spi_transfer returns 0 if ok, <0 if error */
int cc1101_txrx(const struct device *dev, uint8_t reg, uint8_t *txb, uint8_t txlen, uint8_t *rxb, uint8_t rxlen)
{
    const struct cc1101_config *config = dev->config;
    int err = 0;
    uint8_t rxstatus;

    struct spi_buf tx_bufs[2] = {
        {
            .buf = &reg,
            .len = 1,
        },
        {
            .buf = txb,
            .len = txlen,
        },
    };
    const struct spi_buf_set tx = {
        .buffers = tx_bufs,
        .count = ARRAY_SIZE(tx_bufs),
    };

    if (txlen == 0) {
        tx_bufs[1].buf = rxb;
        tx_bufs[1].len = rxlen;
    }

    struct spi_buf rx_bufs[2] = {
        {
            .buf = &rxstatus,
            .len = 1,
        },
        {
            .buf = rxb,
            .len = rxlen,
        },
    };
    const struct spi_buf_set rx = {
        .buffers = rx_bufs,
        .count = ARRAY_SIZE(rx_bufs),
    };

    if (config->ncs.port != 0) {
        gpio_pin_set_dt(&config->ncs, 0);
    }
    err = spi_transceive_dt(&config->spi, &tx, &rx);
    if (config->ncs.port != 0) {
        gpio_pin_set_dt(&config->ncs, 1);
    }

    if (err < 0)
        LOG_ERR("spi_transceve_dt error %d", err);
    return err;
}

int cc1101_strobe(const struct device *dev, uint8_t reg)
{
    return cc1101_txrx (dev, (reg & CC1101_CMD_MASK), NULL, 0, NULL, 0);
}

int cc1101_set_reg(const struct device *dev, uint8_t reg, uint8_t data)
{
    return cc1101_txrx (dev, (reg & CC1101_CMD_MASK) | CC1101_CMD_BURST | CC1101_CMD_WRITE, &data, 1, NULL, 0);
}

int cc1101_set_regs(const struct device *dev, uint8_t reg, uint8_t *values, uint8_t len)
{
    return cc1101_txrx (dev, (reg & CC1101_CMD_MASK) | CC1101_CMD_BURST | CC1101_CMD_WRITE, values, len, NULL, 0);
}

int cc1101_get_reg(const struct device *dev, uint8_t reg, uint8_t *data)
{
    return cc1101_txrx (dev, (reg & CC1101_CMD_MASK) | CC1101_CMD_BURST | CC1101_CMD_READ, NULL, 0, data, 1);
}

int cc1101_get_regs(const struct device *dev, uint8_t reg, uint8_t *values, uint8_t len)
{
    return cc1101_txrx (dev, (reg & CC1101_CMD_MASK) | CC1101_CMD_BURST | CC1101_CMD_READ, NULL, 0, values, len);
}

int cc1101_set_reg_field (const struct device *dev, uint8_t reg, uint8_t data, uint8_t mask)
{
    uint8_t v;
    int ret;

    ret = cc1101_get_reg(dev, reg, &v);
    if (ret < 0) {
        return ret;
    }
    v = v &(~mask);
    v = v | data;
    ret = cc1101_set_reg(dev, reg, v);
    return ret;
}

int cc1101_get_reg_field (const struct device *dev, uint8_t reg, uint8_t *data, uint8_t mask)
{
    uint8_t v;
    int ret;

    ret = cc1101_get_reg(dev, reg, &v);
    if (ret < 0) {
        return ret;
    }
    *data = v & mask;
    return ret;
}

int _idle(const struct device *dev)
{
    return cc1101_strobe(dev,CC1101_CMD_IDLE);
}
