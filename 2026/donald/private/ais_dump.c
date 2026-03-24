/*
 * ais_dump.c
 *
 * Reads AIS (NMEA 0183) sentences from a dAISy receiver on /dev/ttyACM0
 * and writes them to standard output.
 *
 * Typical AIS NMEA speed is 38400 baud, 8N1.
 *
 * Build:
 *   gcc -O2 -Wall -Wextra -pedantic -std=c11 -o ais_dump ais_dump.c
 *
 * Run:
 *   ./ais_dump
 *
 * Notes:
 * - You may need permission to access /dev/ttyACM0 (e.g., be in the "dialout" group).
 * - If your device uses a different baud rate, change BAUD below.
 */

#define _POSIX_C_SOURCE 200809L

#include <errno.h>
#include <fcntl.h>
#include <signal.h>
#include <stdbool.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <termios.h>
#include <unistd.h>

#define DEVICE_PATH "/dev/ttyACM0"
#define BAUD B38400

static volatile sig_atomic_t g_stop = 0;

static void handle_sigint(int sig) {
    (void)sig;
    g_stop = 1;
}

static int configure_serial(int fd) {
    struct termios tty;
    if (tcgetattr(fd, &tty) != 0) {
        perror("tcgetattr");
        return -1;
    }

    // Raw mode (no line processing, no echo, etc.)
    cfmakeraw(&tty);

    // 8N1
    tty.c_cflag &= ~PARENB;            // no parity
    tty.c_cflag &= ~CSTOPB;            // 1 stop bit
    tty.c_cflag &= ~CSIZE;
    tty.c_cflag |= CS8;                // 8 data bits

    // Enable receiver, ignore modem control lines
    tty.c_cflag |= (CLOCAL | CREAD);

    // No hardware flow control
#ifdef CRTSCTS
    tty.c_cflag &= ~CRTSCTS;
#endif

    // No software flow control
    tty.c_iflag &= ~(IXON | IXOFF | IXANY);

    // Set baud
    if (cfsetispeed(&tty, BAUD) != 0 || cfsetospeed(&tty, BAUD) != 0) {
        perror("cfsetispeed/cfsetospeed");
        return -1;
    }

    // Read settings: return as soon as at least 1 byte is available
    tty.c_cc[VMIN]  = 1;
    tty.c_cc[VTIME] = 0;

    if (tcsetattr(fd, TCSANOW, &tty) != 0) {
        perror("tcsetattr");
        return -1;
    }

    // Flush any pending I/O
    if (tcflush(fd, TCIOFLUSH) != 0) {
        perror("tcflush");
        return -1;
    }

    return 0;
}

int main(void) {
    struct sigaction sa;
    memset(&sa, 0, sizeof(sa));
    sa.sa_handler = handle_sigint;
    sigaction(SIGINT, &sa, NULL);
    sigaction(SIGTERM, &sa, NULL);

    int fd = open(DEVICE_PATH, O_RDONLY | O_NOCTTY);
    if (fd < 0) {
        fprintf(stderr, "Failed to open %s: %s\n", DEVICE_PATH, strerror(errno));
        return 1;
    }

    if (configure_serial(fd) != 0) {
        close(fd);
        return 1;
    }

    // Buffer to assemble full NMEA lines
    char line[4096];
    size_t linelen = 0;

    while (!g_stop) {
        unsigned char buf[512];
        ssize_t n = read(fd, buf, sizeof(buf));
        if (n < 0) {
            if (errno == EINTR) continue;
            fprintf(stderr, "read error: %s\n", strerror(errno));
            break;
        }
        if (n == 0) {
            // Unusual for a serial device; treat as transient.
            continue;
        }

        for (ssize_t i = 0; i < n; i++) {
            unsigned char c = buf[i];

            // NMEA lines typically end with \r\n; print one line per '\n'
            if (c == '\n') {
                // Trim a trailing '\r' if present
                if (linelen > 0 && line[linelen - 1] == '\r') {
                    linelen--;
                }
                line[linelen] = '\0';

                // Output the assembled line
                if (linelen > 0) {
                    fputs(line, stdout);
                    fputc('\n', stdout);
                    fflush(stdout);
                }

                linelen = 0;
            } else {
                if (linelen + 1 < sizeof(line)) {
                    line[linelen++] = (char)c;
                } else {
                    // Line too long; reset to avoid runaway
                    linelen = 0;
                }
            }
        }
    }

    close(fd);
    return 0;
}
