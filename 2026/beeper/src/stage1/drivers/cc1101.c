/*
 * This code is based on RadioLib by Jan Gromeš
 * https://github.com/jgromes/RadioLib
 */

#define DT_DRV_COMPAT ti_cc1101

#include "cc1101.h"
#include "cc1101_const.h"
#include "cc1101_spi.h"
#include "cc1101_config.h"
#include "cc1101_txrx.h"

#include <zephyr/logging/log.h>

#include <zephyr/sys/util.h>

LOG_MODULE_REGISTER(cc1101);

int cc1101_find_chip(const struct device *dev)
{
    uint8_t v;

    int err = cc1101_get_reg(dev, CC1101_REG_VERSION, &v);
    if (err < 0)
        return err;
    return v;
}


static int cc1101_init_intr(const struct device *dev)
{
    const struct cc1101_config *config = dev->config;
    struct cc1101_data *data = dev->data;
    int err;

    if (config->gdo0.port != 0) {
        cc1101_enable_intr_rx(dev);

        err = gpio_pin_interrupt_configure_dt(&config->gdo0, GPIO_INT_EDGE_TO_ACTIVE);
        if (err < 0) {
            LOG_ERR("GDO0 interrupt config failed");
            return err;
        }

        gpio_init_callback(&data->rx_cback, _cc1101_rx_handler, BIT(config->gdo0.pin));
        gpio_add_callback(config->gdo0.port, &data->rx_cback);

        cc1101_trigger_rx(dev);
    }

    return 0;
}

#define DEFAULT_FOR(field, deflt) \
    (field != 0? field : deflt)

static int set_defaults(const struct device *dev)
{
    struct cc1101_data *data = dev->data;

    cc1101_set_frequency(dev, DEFAULT_FOR(data->frequency, 868000));
    cc1101_set_bitrate(dev,DEFAULT_FOR(data->bitrate, 4800));
    
    cc1101_set_deviation(dev,20.63);
    cc1101_set_bw(dev,101.56);
    cc1101_set_output_power(dev,10);

    cc1101_set_modulation(dev,GFSK);
    cc1101_set_sync_type(dev,Sync30_32);
    cc1101_set_preamble_length(dev,Bytes4);
    cc1101_set_sync_words(dev,0x2d, 0xc5);
    cc1101_enable_crc(dev);
    cc1101_enable_whitening(dev);
    cc1101_set_variable_length_packet(dev);

    return 0;
}

static int cc1101_init(const struct device *dev)
{
    const struct cc1101_config *config = dev->config;
    struct cc1101_data *data = dev->data;
    int err;

    atomic_set(&data->rx, 1);
    k_sem_init(&data->rx_lock, 0, 1);
    k_mutex_init (&data->spi_mutex);

    /* Get the SPI device */
    if (!device_is_ready(config->spi.bus)) {
        LOG_ERR("Bus device is not ready");
        return -ENODEV;
    }

    if (config->ncs.port != 0){
        if (!gpio_is_ready_dt(&config->ncs)) {
            LOG_ERR("nCS device is not ready");
            return -ENODEV;
        }
    
        if (gpio_pin_configure_dt(&config->ncs, GPIO_OUTPUT) < 0) {
            LOG_ERR("nCS configuration invalid");
            return -ENODEV;        
        }
    
        gpio_pin_set_dt(&config->ncs, 1);
    }
    
    if (config->gdo0.port != 0) {
        if (!gpio_is_ready_dt(&config->gdo0)) {
            LOG_ERR("GDO0 device is not ready");
            return -ENODEV;
        }
    
        if (gpio_pin_configure_dt(&config->gdo0, GPIO_INPUT) < 0) {
            LOG_ERR("GDO0 configuration invalid");
            return -ENODEV;        
        }
    }

    if (config->gdo2.port != 0) {
        if (!gpio_is_ready_dt(&config->gdo2)) {
            LOG_ERR("GDO2 device is not ready");
            return -ENODEV;
        }

        if (gpio_pin_configure_dt(&config->gdo2, GPIO_INPUT) < 0) {
            LOG_ERR("GDO2 configuration invalid");
            return -ENODEV;        
        }
    }

    err = cc1101_strobe(dev, CC1101_CMD_RESET);

    // configure 

    _idle(dev);

    cc1101_set_reg_field(dev,CC1101_REG_MCSM0, CC1101_FS_AUTOCAL_IDLE_TO_RXTX, 0b00110000);
    cc1101_set_reg_field(dev,CC1101_REG_MCSM1, CC1101_TXOFF_RX | CC1101_RXOFF_RX, 0b00001111);

    cc1101_set_reg_field(dev,CC1101_REG_PKTCTRL1, CC1101_CRC_AUTOFLUSH_OFF | CC1101_APPEND_STATUS_ON | CC1101_ADR_CHK_NONE, 0b00001111);
    cc1101_set_reg_field(dev,CC1101_REG_PKTCTRL0, CC1101_WHITE_DATA_OFF | CC1101_PKT_FORMAT_NORMAL, 0b01110000);
    cc1101_set_reg_field(dev,CC1101_REG_PKTCTRL0, CC1101_CRC_ON | CC1101_LENGTH_CONFIG_VARIABLE, 0b00000111);
    cc1101_set_reg_field(dev,CC1101_REG_FIFOTHR, 0x0d, 0b00001111);

    set_defaults(dev);
    
    cc1101_set_reg_field(dev,CC1101_REG_FSCTRL1, 0x06, 0b00011111);
    cc1101_set_reg(dev,CC1101_REG_FSCTRL0, 0x05);

    cc1101_set_reg_field(dev,CC1101_REG_FSCTRL1, 0x06, 0b00011111);
    cc1101_set_reg(dev,CC1101_REG_FSCTRL0, 0x05);

    _idle(dev);
    cc1101_strobe(dev, CC1101_CMD_FLUSH_TX);
    cc1101_strobe(dev, CC1101_CMD_FLUSH_RX);

    k_thread_create(&data->rx_thread, data->rx_stack,
            CONFIG_CC1101_RX_STACK_SIZE,
            (k_thread_entry_t)_cc1101_rx_thread,
            (void *)dev, NULL, NULL, K_PRIO_COOP(2), 0, K_NO_WAIT);
    k_thread_name_set(&data->rx_thread, "cc1101_rx");

    cc1101_init_intr(dev);

    return 0;
}


#define CC1101_DEFINE(inst)                                             \
    static struct cc1101_data cc1101_data_##inst = {                    \
        .frequency = DT_INST_PROP(inst, frequency),                     \
        .bitrate = DT_INST_PROP(inst, bitrate),                         \
    };                                                                  \
    static struct cc1101_config cc1101_config_##inst = {                \
        .spi = SPI_DT_SPEC_INST_GET(inst, SPI_WORD_SET(8), 150),        \
        .gdo0 = GPIO_DT_SPEC_INST_GET_BY_IDX(inst, int_gpios, 0),       \
        .gdo2 = GPIO_DT_SPEC_INST_GET_BY_IDX(inst, int_gpios, 1),       \
        .ncs = GPIO_DT_SPEC_INST_GET(inst, cs_gpios),                   \
    };                                                                  \
    DEVICE_DT_INST_DEFINE(inst, cc1101_init, NULL,                      \
                  &cc1101_data_##inst, &cc1101_config_##inst, POST_KERNEL,  \
                  99, NULL);


DT_INST_FOREACH_STATUS_OKAY(CC1101_DEFINE)
