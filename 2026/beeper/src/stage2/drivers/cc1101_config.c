#include "cc1101_config.h"
#include "cc1101_const.h"
#include "cc1101_spi.h"

#include <zephyr/logging/log.h>

#include <zephyr/sys/util.h>

LOG_MODULE_REGISTER(cc1101_config);

/* this code has been taken from RadioLib. */
static void _get_expmant(float target, uint16_t mantOffset, uint8_t divExp, uint8_t expMax, uint8_t *exp, uint8_t *mant)
{
    // get table origin point (exp = 0, mant = 0)
    float origin = (mantOffset * CC1101_CRYSTAL_FREQ * 1000000.0) / ((uint32_t) 1 << divExp);

    // iterate over possible exponent values
    for (int8_t e = expMax; e >= 0; e--) {
        // get table column start value (exp = e, mant = 0);
        float intervalStart = ((uint32_t) 1 << e) * origin;

        // check if target value is in this column
        if (target >= intervalStart) {
            // save exponent value
            *exp = e;

            // calculate size of step between table rows
            float stepSize = intervalStart / (float) mantOffset;

            // get target point position (exp = e, mant = m)
            *mant = ((target - intervalStart) / stepSize);

            // we only need the first match, terminate
            return;
        }
    }
}


int cc1101_set_output_power(const struct device *dev, int8_t power)
{
    const struct cc1101_config *config = dev->config;
    struct cc1101_data *data = dev->data;

    // round to the known frequency settings
    uint8_t f;
    if (data->frequency < 374.0) {
        // 315 MHz
        f = 0;
    } else if (data->frequency < 650.5) {
        // 434 MHz
        f = 1;
    } else if (data->frequency < 891.5) {
        // 868 MHz
        f = 2;
    } else {
        // 915 MHz
        f = 3;
    }

    // get raw power setting
    static uint8_t paTable[8][4] = {{0x12, 0x12, 0x03, 0x03},
                                    {0x0D, 0x0E, 0x0F, 0x0E},
                                    {0x1C, 0x1D, 0x1E, 0x1E},
                                    {0x34, 0x34, 0x27, 0x27},
                                    {0x51, 0x60, 0x50, 0x8E},
                                    {0x85, 0x84, 0x81, 0xCD},
                                    {0xCB, 0xC8, 0xCB, 0xC7},
                                    {0xC2, 0xC0, 0xC2, 0xC0}};

    uint8_t powerRaw;
    switch (power) {
        case -30:
            powerRaw = paTable[0][f];
            break;
        case -20:
            powerRaw = paTable[1][f];
            break;
        case -15:
            powerRaw = paTable[2][f];
            break;
        case -10:
            powerRaw = paTable[3][f];
            break;
        case 0:
            powerRaw = paTable[4][f];
            break;
        case 5:
            powerRaw = paTable[5][f];
            break;
        case 7:
            powerRaw = paTable[6][f];
            break;
        case 10:
            powerRaw = paTable[7][f];
            break;
        default:
            return -EINVAL;
    }

    data->power = power;

    if (data->modulation == ASK_OOK) {
        // Amplitude modulation:
        // PA_TABLE[0] is the power to be used when transmitting a 0  (no power)
        // PA_TABLE[1] is the power to be used when transmitting a 1  (full power)

        uint8_t paValues[2] = {0x00, powerRaw};
        return cc1101_set_regs(dev, CC1101_REG_PATABLE, paValues, 2);        
    } else {
        return cc1101_set_reg(dev, CC1101_REG_PATABLE, powerRaw);
    }
}


int cc1101_set_frequency(const struct device *dev, uint32_t freq_int)
{
    float freq = (float)freq_int / 1000.0f;

    if (!(((freq > 300.0) && (freq < 348.0)) ||
          ((freq > 387.0) && (freq < 464.0)) ||
          ((freq > 779.0) && (freq < 928.0)))) {
        return -EINVAL;
    }

    // set mode to standby
    _idle(dev);

    //set carrier frequency
    uint32_t base = 1;
    uint32_t FRF = (freq * (base << 16)) / 26.0;
    int err = 0;

    err = cc1101_set_reg(dev, CC1101_REG_FREQ2, (FRF & 0xFF0000) >> 16);
    if (err < 0) return err;
    err = cc1101_set_reg(dev, CC1101_REG_FREQ1, (FRF & 0x00FF00) >> 8);
    if (err < 0) return err;
    err = cc1101_set_reg(dev, CC1101_REG_FREQ0, FRF & 0x0000FF);
    if (err < 0) return err;

    const struct cc1101_config *config = dev->config;
    struct cc1101_data *data = dev->data;

    data->frequency = freq_int;

    return cc1101_set_output_power(dev, data->power);
}

int cc1101_set_bitrate(const struct device *dev, uint32_t br_int)
{
    float br = (float)br_int;

    _idle(dev);

    // calculate exponent and mantissa values
    uint8_t e = 0;
    uint8_t m = 0;
    _get_expmant(br, 256, 28, 14, &e, &m);

    // set bit rate value
    int state = cc1101_set_reg_field(dev, CC1101_REG_MDMCFG4, e, 0b00001111);
    if (state) return state;

    state = cc1101_set_reg(dev, CC1101_REG_MDMCFG3, m);

    const struct cc1101_config *config = dev->config;
    struct cc1101_data *data = dev->data;
    data->bitrate = br_int;

    return state;
}

int cc1101_set_bw(const struct device *dev, float rxBw)
{
    _idle(dev);

    // calculate exponent and mantissa values
    for (int8_t e = 3; e >= 0; e--) {
        for (int8_t m = 3; m >= 0; m--) {
            float point = (CC1101_CRYSTAL_FREQ * 1000000.0) / (8 * (m + 4) * ((uint32_t) 1 << e));


            if (((rxBw * 1000.0) - point) <= 1000) {
                // set Rx channel filter bandwidth
                return (cc1101_set_reg_field(dev, CC1101_REG_MDMCFG4, (e << 6) | (m << 4), 0b11110000));
            }
        }
    }

    return -EINVAL;
}

int cc1101_set_deviation(const struct device *dev, float freqDev)
{
// set frequency deviation to lowest available setting (required for digimodes)
    float newFreqDev = freqDev;
    if (freqDev < 0.0) {
        newFreqDev = 1.587;
    }

    _idle(dev);

    // calculate exponent and mantissa values
    uint8_t e = 0;
    uint8_t m = 0;
    _get_expmant(newFreqDev * 1000.0, 8, 17, 7, &e, &m);

    int err = cc1101_set_reg_field(dev, CC1101_REG_DEVIATN, (e << 4), 0b01110000);
    if (err < 0) return err;

    err = cc1101_set_reg_field(dev, CC1101_REG_DEVIATN, m, 0b00000111);
    return err;
}

int cc1101_set_sync_type(const struct device *dev, enum Cc1101SyncType type)
{
    uint8_t value = (uint8_t)(type);
    return cc1101_set_reg_field(dev, CC1101_REG_MDMCFG2, value, 0b00000111);
}

int cc1101_set_preamble_length(const struct device *dev, enum Cc1101PreambleTypes type)
{
    uint8_t value = (uint8_t)(type) << 4;
    return cc1101_set_reg_field(dev,CC1101_REG_MDMCFG1, value, 0b01110000);
}

int cc1101_set_modulation(const struct device *dev, enum Cc1101Modulation modulation)
{
    switch (modulation) {
        case FSK2:
            cc1101_set_reg_field(dev,CC1101_REG_MDMCFG2, CC1101_MOD_FORMAT_2_FSK,0b01110000);
            cc1101_set_reg_field(dev,CC1101_REG_FREND0, 0, 0b00000111);
            break;
        case GFSK:
            cc1101_set_reg_field(dev,CC1101_REG_MDMCFG2, CC1101_MOD_FORMAT_GFSK, 0b01110000);
            cc1101_set_reg_field(dev,CC1101_REG_FREND0, 0,0b00000111);
            break;
        case ASK_OOK:
            cc1101_set_reg_field(dev,CC1101_REG_MDMCFG2, CC1101_MOD_FORMAT_ASK_OOK, 0b01110000);
            cc1101_set_reg_field(dev,CC1101_REG_FREND0, 1, 0b00000111);
            break;
        case FSK4:
            cc1101_set_reg_field(dev,CC1101_REG_MDMCFG2, CC1101_MOD_FORMAT_4_FSK, 0b01110000);
            cc1101_set_reg_field(dev,CC1101_REG_FREND0, 1, 0b00000111);
            break;
        case MFSK:
            cc1101_set_reg_field(dev,CC1101_REG_MDMCFG2, CC1101_MOD_FORMAT_MFSK, 0b01110000);
            cc1101_set_reg_field(dev,CC1101_REG_FREND0, 1, 0b00000111);
            break;
    }

    const struct cc1101_config *config = dev->config;
    struct cc1101_data *data = dev->data;
    data->modulation = modulation;

    return cc1101_set_output_power(dev, data->power);
}

int cc1101_set_variable_length_packet(const struct device *dev)
{
    cc1101_set_reg_field(dev,CC1101_REG_PKTCTRL0, CC1101_LENGTH_CONFIG_VARIABLE, 0b00000011);
    struct cc1101_data *data = dev->data;
    data->variable_length = 1;
    return 0;
}

int cc1101_set_sync_words(const struct device *dev, uint8_t w1, uint8_t w2)
{
    cc1101_set_reg(dev,CC1101_REG_SYNC1, w1);
    cc1101_set_reg(dev,CC1101_REG_SYNC0, w2);
    return 0;
}

int cc1101_enable_crc(const struct device *dev)
{
    return cc1101_set_reg_field(dev,CC1101_REG_PKTCTRL0, CC1101_CRC_ON, 0b00000100);
}

int cc1101_enable_whitening(const struct device *dev)
{
    return cc1101_set_reg_field(dev,CC1101_REG_PKTCTRL0, CC1101_WHITE_DATA_ON, 0b01000000);
}

int cc1101_set_maximum_packet_length(const struct device *dev,uint8_t max)
{
    return cc1101_set_reg(dev,CC1101_REG_PKTLEN, max);
}

int cc1101_enable_async_mode(const struct device *dev)
{
    int err;

    _idle(dev);

    /* Configure GDO0 for async serial data output (RX) / input (TX) */
    err = cc1101_set_reg(dev, CC1101_REG_IOCFG0, CC1101_GDOX_SERIAL_DATA_ASYNC);
    if (err < 0) return err;

    /* Set asynchronous serial mode (PKTCTRL0[5:4] = 0b11) */
    err = cc1101_set_reg_field(dev, CC1101_REG_PKTCTRL0, CC1101_PKT_FORMAT_ASYNCHRONOUS, 0b00110000);
    if (err < 0) return err;

    /* Disable CRC and whitening for raw mode */
    err = cc1101_set_reg_field(dev, CC1101_REG_PKTCTRL0, CC1101_CRC_OFF, 0b00000100);
    if (err < 0) return err;

    err = cc1101_set_reg_field(dev, CC1101_REG_PKTCTRL0, CC1101_WHITE_DATA_OFF, 0b01000000);
    if (err < 0) return err;

    /* No sync word in async mode */
    err = cc1101_set_reg_field(dev, CC1101_REG_MDMCFG2, CC1101_SYNC_MODE_NONE, 0b00000111);
    if (err < 0) return err;

    /* Minimal preamble */
    err = cc1101_set_reg_field(dev, CC1101_REG_MDMCFG1, CC1101_NUM_PREAMBLE_2, 0b01110000);

    return err;
}

int cc1101_disable_async_mode(const struct device *dev)
{
    int err;

    _idle(dev);

    /* Return to normal FIFO mode */
    err = cc1101_set_reg_field(dev, CC1101_REG_PKTCTRL0, CC1101_PKT_FORMAT_NORMAL, 0b00110000);

    return err;
}

int cc1101_start_tx(const struct device *dev)
{
    return cc1101_strobe(dev, CC1101_CMD_TX);
}

int cc1101_stop_tx(const struct device *dev)
{
    return _idle(dev);
}

int cc1101_start_rx(const struct device *dev)
{
    return cc1101_strobe(dev, CC1101_CMD_RX);
}

