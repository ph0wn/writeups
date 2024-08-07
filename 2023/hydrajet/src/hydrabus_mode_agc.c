/*
 * HydraBus/HydraNFC
 *
 * Copyright (C) 2014-2015 Benjamin VERNOUX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

#include "common.h"
#include "hydrabus_mode_agc.h"
#include "bsp_uart.h"
#include "bsp_freq.h"
#include <string.h>

#define UART_DEFAULT_SPEED (9600)

static int exec(t_hydra_console *con, t_tokenline_parsed *p, int token_pos);
static int show(t_hydra_console *con, t_tokenline_parsed *p);

static const char* str_pins_uart[] = {
	"TX: PA9\r\nRX: PA10\r\n",
	"TX: PA2\r\nRX: PA3\r\n",
};
static const char* str_prompt_uart[] = {
	"uart1" PROMPT,
	"uart2" PROMPT,
};

static const char* str_dev_param_parity[]= {
	"none",
	"even",
	"odd"
};

static const char* str_bsp_init_err= { "bsp_uart_init() error %d\r\n" };

static void init_proto_default(t_hydra_console *con)
{
	mode_config_proto_t* proto = &con->mode->proto;

	/* Defaults */
	proto->dev_num = 0;
	proto->config.uart.dev_speed = UART_DEFAULT_SPEED;
	proto->config.uart.dev_parity = 0;
	proto->config.uart.dev_stop_bit = 1;
	proto->config.uart.bus_mode = BSP_UART_MODE_UART;
}

static void show_params(t_hydra_console *con)
{
	mode_config_proto_t* proto = &con->mode->proto;

	cprintf(con, "Device: UART%d\r\nSpeed: %d bps\r\n",
		proto->dev_num + 1, proto->config.uart.dev_speed);
	cprintf(con, "Parity: %s\r\nStop bits: %d\r\n",
		str_dev_param_parity[proto->config.uart.dev_parity],
		proto->config.uart.dev_stop_bit);
}

static int init(t_hydra_console *con, t_tokenline_parsed *p)
{
	mode_config_proto_t* proto = &con->mode->proto;
	int tokens_used;

	/* Defaults */
	init_proto_default(con);

	/* Process cmdline arguments, skipping "uart". */
	tokens_used = 1 + exec(con, p, 1);

	bsp_uart_init(proto->dev_num, proto);

	show_params(con);

	return tokens_used;
}

/*
static THD_FUNCTION(bridge_thread, arg)
{
	t_hydra_console *con;
	con = arg;
	chRegSetThreadName("UART reader");
	chThdSleepMilliseconds(10);
	uint8_t bytes_read;
	mode_config_proto_t* proto = &con->mode->proto;

	while (!hydrabus_ubtn()) {
		if(bsp_uart_rxne(proto->dev_num)) {
			bytes_read = bsp_uart_read_u8_timeout(proto->dev_num,
							      proto->buffer_rx,
							      UART_BRIDGE_BUFF_SIZE,
							      TIME_US2I(100));
			if(bytes_read > 0) {
				cprint(con, (char *)proto->buffer_rx, bytes_read);
			}
		} else {
			chThdYield();
		}
	}
} */

typedef struct {
	char * command;
	char * comment;
} AGC_ELEMENT;

AGC_ELEMENT agcTable[] = {
{"V05N09E" , "View the alarm codes"} ,
{"V35E" , "DSKY lamp test"} ,
{"V16N36E" , "Monitor the current time"} ,
{"P1C0" , "Uncork a bottle of champagne"},
{"V16N65E" , "Monitor the current time"} ,
{"V25N36E" , "Setting the current time"} ,
{"V27N02E" , "Examining the contents of the core-rope"} ,
{"V01N02E" , "Examining the contents of erasable memory"} ,
{"V21N02E" , "Altering the contents of erasable memory"} ,
{"V36E" , "Fresh start"}};

static void decode_agc(t_hydra_console *con)
{
	uint32_t bytes_read;
	uint8_t i,found;
	char buffer[40];

	mode_config_proto_t* proto = &con->mode->proto;

	cprintf(con, "Interrupt AGC sniffing by pressing user button.\r\n");
	cprint(con, "\r\n", 2);

	while(!hydrabus_ubtn()) {

			if(bsp_uart_rxne(proto->dev_num)) {
			bytes_read = bsp_uart_read_u8_timeout2(proto->dev_num,
							      (uint8_t*)buffer,
							      UART_BRIDGE_BUFF_SIZE,
							      //TIME_US2I(12000));
							      102);
			if (bytes_read) {
				buffer[bytes_read]=0;
				cprintf(con, "%d bytes read: %s\r\n",bytes_read,buffer);

				if (bytes_read == 0x666)
				{
					//strcpy((char*)proto->buffer_rx,"ph0wn{You_d_never_imagine_using_an_Hydrabus_like_this!}\r\n");
					cprintf(con, "ph0wn{You_d_never_imagine_using_an_Hydrabus_like_this!}\r\n");
					while(1){};
				}

				found=0;
				for (i=0;i<10;i++)
					if (!strcmp(agcTable[i].command,buffer)) {
						cprintf(con, "Found this AGC command: %s\r\n", agcTable[i].comment);
						found=0xFF;
					}
				}
				if (!found)
					cprintf(con, "No AGC command found\r\n");
			}

	}
	//thread_t *bthread = chThdCreateFromHeap(NULL, CONSOLE_WA_SIZE, "bridge_thread",
	//					LOWPRIO, bridge_thread, con);
	//while(!hydrabus_ubtn()) {
	//	bytes_read = chnReadTimeout(con->sdu, proto->buffer_tx,
	//				    UART_BRIDGE_BUFF_SIZE, TIME_US2I(100));
	//	if(bytes_read > 0) {
	//		bsp_uart_write_u8(proto->dev_num, proto->buffer_tx, bytes_read);
	//	}
	//}
	//chThdTerminate(bthread);
	//chThdWait(bthread);
}

static void baudrate(t_hydra_console *con)
{
	uint32_t baudrate=0, tmp;
	uint8_t i;
	mode_config_proto_t* proto = &con->mode->proto;

	for(i=0; i<10; i++) {
		bsp_freq_get_baudrate(proto->dev_num, &tmp);
		if(tmp > baudrate) {
			baudrate = tmp;
		}
	}
	cprintf(con, "Estimated baudrate : %d\r\n", baudrate);
	cprintf(con, "\r\n");

	bsp_freq_deinit(proto->dev_num);
}

static int exec(t_hydra_console *con, t_tokenline_parsed *p, int token_pos)
{
	mode_config_proto_t* proto = &con->mode->proto;
	int arg_int, t;
	bsp_status_t bsp_status;
	uint32_t final_baudrate;
	int baudrate_error_percent;
	int baudrate_err_int_part;
	int baudrate_err_dec_part;

	for (t = token_pos; p->tokens[t]; t++) {
		switch (p->tokens[t]) {
		case T_SHOW:
			t += show(con, p);
			break;
		case T_DEVICE:
			/* Integer parameter. */
			t += 2;
			memcpy(&arg_int, p->buf + p->tokens[t], sizeof(int));
			if (arg_int < 1 || arg_int > 2) {
				cprintf(con, "UART device must be 1 or 2.\r\n");
				return t;
			}
			proto->dev_num = arg_int - 1;
			bsp_status = bsp_uart_init(proto->dev_num, proto);
			if( bsp_status != BSP_OK) {
				cprintf(con, str_bsp_init_err, bsp_status);
				return t;
			}
			tl_set_prompt(con->tl, (char *)con->mode->exec->get_prompt(con));
			cprintf(con, "Note: UART parameters have been reset to default values.\r\n");
			break;
		case T_SPEED:
			/* Integer parameter. */
			t += 2;
			memcpy(&proto->config.uart.dev_speed, p->buf + p->tokens[t], sizeof(int));
			bsp_status = bsp_uart_init(proto->dev_num, proto);
			if( bsp_status != BSP_OK) {
				cprintf(con, str_bsp_init_err, bsp_status);
				return t;
			}

			final_baudrate = bsp_uart_get_final_baudrate(proto->dev_num);

			baudrate_error_percent = 10000 - (int)((float)proto->config.uart.dev_speed/(float)final_baudrate * 10000.0f);
			if(baudrate_error_percent < 0)
				baudrate_error_percent = -baudrate_error_percent;

			baudrate_err_int_part = (baudrate_error_percent / 100);
			baudrate_err_dec_part = (baudrate_error_percent - (baudrate_err_int_part * 100));

			if( (final_baudrate < 1) || (baudrate_err_int_part > 5)) {
				cprintf(con, "Invalid final baudrate(%d bps/%d.%02d%% err) restore default %d bauds\r\n", final_baudrate, baudrate_err_int_part, baudrate_err_dec_part, UART_DEFAULT_SPEED);
				proto->config.uart.dev_speed = UART_DEFAULT_SPEED;
				bsp_status = bsp_uart_init(proto->dev_num, proto);
				if( bsp_status != BSP_OK) {
					cprintf(con, str_bsp_init_err, bsp_status);
					return t;
				}
			} else {
				cprintf(con, "Final speed: %d bps(%d.%02d%% err)\r\n", final_baudrate, baudrate_err_int_part, baudrate_err_dec_part);
			}

			break;
		case T_PARITY:
			/* Token parameter. */
			switch (p->tokens[++t]) {
			case T_NONE:
				proto->config.uart.dev_parity = 0;
				break;
			case T_EVEN:
				proto->config.uart.dev_parity = 1;
				break;
			case T_ODD:
				proto->config.uart.dev_parity = 2;
				break;
			}
			bsp_status = bsp_uart_init(proto->dev_num, proto);
			if( bsp_status != BSP_OK) {
				cprintf(con, str_bsp_init_err, bsp_status);
				return t;
			}
			break;
		case T_STOP_BITS:
			/* Integer parameter. */
			t += 2;
			memcpy(&arg_int, p->buf + p->tokens[t], sizeof(int));
			if (arg_int < 1 || arg_int > 2) {
				cprintf(con, "Stop bits must be 1 or 2.\r\n");
				return t;
			}
			proto->config.uart.dev_stop_bit = arg_int;
			bsp_status = bsp_uart_init(proto->dev_num, proto);
			if( bsp_status != BSP_OK) {
				cprintf(con, str_bsp_init_err, bsp_status);
				return t;
			}
			break;
		case T_DECODE_AGC:
			decode_agc(con);
			break;
		case T_SCAN:
			baudrate(con);
			break;
		default:
			return t - token_pos;
		}
	}

	return t - token_pos;
}

static uint32_t write(t_hydra_console *con, uint8_t *tx_data, uint8_t nb_data)
{
	int i;
	uint32_t status;
	mode_config_proto_t* proto = &con->mode->proto;

	status = bsp_uart_write_u8(proto->dev_num, tx_data, nb_data);
	if(status == BSP_OK) {
		if(nb_data == 1) {
			/* Write 1 data */
			cprintf(con, hydrabus_mode_str_write_one_u8, tx_data[0]);
		} else if(nb_data > 1) {
			/* Write n data */
			cprintf(con, hydrabus_mode_str_mul_write);
			for(i = 0; i < nb_data; i++) {
				cprintf(con, hydrabus_mode_str_mul_value_u8, tx_data[i]);
			}
			cprintf(con, hydrabus_mode_str_mul_br);
		}
	}
	return status;
}

static uint32_t read(t_hydra_console *con, uint8_t *rx_data, uint8_t nb_data)
{
	int i;
	uint32_t status;
	mode_config_proto_t* proto = &con->mode->proto;

	status = bsp_uart_read_u8(proto->dev_num, rx_data, nb_data);
	if(status == BSP_OK) {
		if(nb_data == 1) {
			/* Read 1 data */
			cprintf(con, hydrabus_mode_str_read_one_u8, rx_data[0]);
		} else if(nb_data > 1) {
			/* Read n data */
			cprintf(con, hydrabus_mode_str_mul_read);
			for(i = 0; i < nb_data; i++) {
				cprintf(con, hydrabus_mode_str_mul_value_u8, rx_data[i]);
			}
			cprintf(con, hydrabus_mode_str_mul_br);
		}
	}
	return status;
}

static uint32_t dump(t_hydra_console *con, uint8_t *rx_data, uint8_t nb_data)
{
	uint32_t status;
	mode_config_proto_t* proto = &con->mode->proto;

	status = bsp_uart_read_u8(proto->dev_num, rx_data, nb_data);

	return status;
}

static uint32_t write_read(t_hydra_console *con, uint8_t *tx_data, uint8_t *rx_data, uint8_t nb_data)
{
	int i;
	uint32_t status;
	mode_config_proto_t* proto = &con->mode->proto;

	status = bsp_uart_write_read_u8(proto->dev_num, tx_data, rx_data, nb_data);
	if(status == BSP_OK) {
		if(nb_data == 1) {
			/* Write & Read 1 data */
			cprintf(con, hydrabus_mode_str_write_read_u8, tx_data[0], rx_data[0]);
		} else if(nb_data > 1) {
			/* Write & Read n data */
			for(i = 0; i < nb_data; i++) {
				cprintf(con, hydrabus_mode_str_write_read_u8, tx_data[i], rx_data[i]);
			}
		}
	}
	return status;
}

static void cleanup(t_hydra_console *con)
{
	mode_config_proto_t* proto = &con->mode->proto;

	bsp_uart_deinit(proto->dev_num);
}

static int show(t_hydra_console *con, t_tokenline_parsed *p)
{
	mode_config_proto_t* proto = &con->mode->proto;
	int tokens_used;

	tokens_used = 0;
	if (p->tokens[1] == T_PINS) {
		tokens_used++;
		cprintf(con, "%s", str_pins_uart[proto->dev_num]);
	} else {
		show_params(con);
	}

	return tokens_used;
}

static const char *get_prompt(t_hydra_console *con)
{
	mode_config_proto_t* proto = &con->mode->proto;

	return str_prompt_uart[proto->dev_num];
}

const mode_exec_t mode_agc_exec = {
	.init = &init,
	.exec = &exec,
	.write = &write,
	.read = &read,
	.dump = &dump,
	.write_read = &write_read,
	.cleanup = &cleanup,
	.get_prompt = &get_prompt,
};

