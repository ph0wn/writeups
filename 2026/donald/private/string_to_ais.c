/*
 * ais_type14_from_text.c
 *
 * Build an AIS Message Type 14 (Safety-Related Broadcast Message) from input text
 * and wrap it into one or more NMEA 0183 !AIVDM sentences.
 *
 * Compile:
 *   gcc -std=c11 -O2 -Wall -Wextra -o ais14 ais_type14_from_text.c
 *
 * Run:
 *   echo "HELLO AIS" | ./ais14
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

#define MAX_INPUT_TEXT 512
#define MAX_BITS       8192
#define MAX_PAYLOAD    4096

/* AIS 6-bit character set (ITU-R M.1371 armoring text set) */
static const char AIS6_TABLE[] =
"@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_ !\"#$%&'()*+,-./0123456789:;<=>?";

/* Find AIS6 value (0..63) for a given ASCII char, or -1 if unsupported */
static int ascii_to_ais6(char c) {
    /* Common practice: upper-case input for best coverage */
    unsigned char uc = (unsigned char)c;
    if (isalpha(uc)) c = (char)toupper(uc);

    const char *p = strchr(AIS6_TABLE, c);
    if (!p) return -1;
    return (int)(p - AIS6_TABLE);
}

/* AIS "six-bit armoring": map 0..63 to payload characters */
static char armor_6bit(int v) {
    v &= 0x3F;
    int ch = v + 48;
    if (ch > 87) ch += 8;
    return (char)ch;
}

/* Append 'width' bits of 'value' (MSB first) into bit buffer */
static void append_bits(unsigned char *bits, int *bitlen, unsigned int value, int width) {
    for (int i = width - 1; i >= 0; --i) {
        int bit = (value >> i) & 1U;
        if (*bitlen >= MAX_BITS) {
            fprintf(stderr, "Bit buffer overflow.\n");
            exit(1);
        }
        bits[(*bitlen)++] = (unsigned char)bit;
    }
}

/* Convert bit buffer (0/1) into armored AIS payload string; returns fill bits */
static int bits_to_payload(const unsigned char *bits, int bitlen, char *out, size_t out_sz) {
    int fill = (6 - (bitlen % 6)) % 6;
    int total = bitlen + fill;

    int out_len = 0;
    for (int i = 0; i < total; i += 6) {
        int v = 0;
        for (int j = 0; j < 6; ++j) {
            int idx = i + j;
            int b = (idx < bitlen) ? bits[idx] : 0; /* pad with zeros */
            v = (v << 1) | (b & 1);
        }
        if ((size_t)(out_len + 2) > out_sz) {
            fprintf(stderr, "Payload buffer overflow.\n");
            exit(1);
        }
        out[out_len++] = armor_6bit(v);
    }
    out[out_len] = '\0';
    return fill;
}

/* Compute NMEA 0183 checksum: XOR of chars between '!' and '*' (exclusive) */
static unsigned char nmea_checksum(const char *sentence_no_bang_no_star) {
    unsigned char csum = 0;
    for (const unsigned char *p = (const unsigned char *)sentence_no_bang_no_star; *p; ++p) {
        csum ^= *p;
    }
    return csum;
}

/* Print one !AIVDM sentence with checksum */
static void print_aivdm_sentence(int total, int num, const char *seq, char channel,
                                const char *payload_chunk, int fill) {
    char body[256];
    /* Format: AIVDM,<total>,<num>,<seq>,<channel>,<payload>,<fill> */
    snprintf(body, sizeof(body), "AIVDM,%d,%d,%s,%c,%s,%d",
             total, num, seq, channel, payload_chunk, fill);

    unsigned char csum = nmea_checksum(body);
    printf("!%s*%02X\n", body, csum);
}

int main(void) {
    /* Read a line of text from stdin */
    char input[MAX_INPUT_TEXT];
    if (!fgets(input, sizeof(input), stdin)) {
        fprintf(stderr, "No input.\n");
        return 1;
    }

    /* Strip trailing newline(s) */
    size_t n = strlen(input);
    while (n > 0 && (input[n - 1] == '\n' || input[n - 1] == '\r')) {
        input[--n] = '\0';
    }

    /* Build AIS Message Type 14 bits */
    unsigned char bits[MAX_BITS];
    int bitlen = 0;

    /* Hard-coded MMSI for demo purposes. Replace with a valid MMSI for your use-case. */
    const unsigned int MMSI = 123456789;

    /* AIS Message 14 structure:
       - message type: 6 bits (14)
       - repeat: 2 bits
       - MMSI: 30 bits
       - spare: 2 bits
       - text: 6 bits per character (AIS 6-bit alphabet)
    */
    append_bits(bits, &bitlen, 14, 6);          /* message type */
    append_bits(bits, &bitlen, 0, 2);           /* repeat indicator */
    append_bits(bits, &bitlen, MMSI, 30);       /* MMSI */
    append_bits(bits, &bitlen, 0, 2);           /* spare */

    /* Encode text */
    for (size_t i = 0; i < n; ++i) {
        int v = ascii_to_ais6(input[i]);
        if (v < 0) {
            /* Replace unsupported chars with space */
            v = ascii_to_ais6(' ');
        }
        append_bits(bits, &bitlen, (unsigned int)v, 6);
    }

    /* Convert to AIS armored payload */
    char payload[MAX_PAYLOAD];
    int fill = bits_to_payload(bits, bitlen, payload, sizeof(payload));
    int payload_len = (int)strlen(payload);

    /*
     * Split into multiple !AIVDM sentences if needed.
     * For simplicity, we chunk payload into a conservative size that stays under NMEA 82 char limits.
     * This is not an optimized splitter, but works well for typical usage.
     */
    const int CHUNK = 60; /* conservative chunk length for payload field */
    int total = (payload_len + CHUNK - 1) / CHUNK;
    if (total < 1) total = 1;

    const char *seq = (total > 1) ? "0" : ""; /* sequence ID recommended for multi-fragment; empty for single */
    char channel = 'A'; /* channel A */

    for (int i = 0; i < total; ++i) {
        int start = i * CHUNK;
        int len = payload_len - start;
        if (len > CHUNK) len = CHUNK;

        char chunk[CHUNK + 1];
        memcpy(chunk, payload + start, (size_t)len);
        chunk[len] = '\0';

        /* Fill bits must be applied only on the last fragment; other fragments use 0 */
        int frag_fill = (i == total - 1) ? fill : 0;

        print_aivdm_sentence(total, i + 1, seq, channel, chunk, frag_fill);
    }

    return 0;
}
