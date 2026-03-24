/*
 * aisdump.c — Minimal AIS (AIVDM/AIVDO) decoder and pretty-printer
 *
 * - Reads NMEA AIS sentences from stdin, one per line.
 * - Supports multi-fragment reassembly (common for Type 5, 19, 24, etc.).
 * - Decodes payload and pretty-prints fields for common message types:
 *     1/2/3, 4, 5, 18, 19 (partial but substantial), 24
 * - For unsupported message types, prints header + raw bit dump summary.
 *
 * Build (Debian):
 *   gcc -std=c11 -O2 -Wall -Wextra -pedantic -o aisdump aisdump.c
 *
 * Run:
 *   cat ais.log | ./aisdump
 *
 * Notes:
 * - This is a self-contained decoder (no external libraries).
 * - AIS specs are extensive; this prints “all fields” for the implemented types,
 *   and provides a clear fallback for other/unknown types.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdint.h>
#include <ctype.h>
#include <errno.h>

/* ---------- Utilities ---------- */

static void rstrip_newline(char *s) {
    size_t n = strlen(s);
    while (n && (s[n-1] == '\n' || s[n-1] == '\r')) s[--n] = '\0';
}

static int hexval(int c) {
    if (c >= '0' && c <= '9') return c - '0';
    c = toupper((unsigned char)c);
    if (c >= 'A' && c <= 'F') return 10 + (c - 'A');
    return -1;
}

/* NMEA checksum: XOR of bytes between '!' (excluded) and '*' (excluded) */
static int nmea_checksum_ok(const char *line, int *out_calc, int *out_given) {
    const char *bang = strchr(line, '!');
    if (!bang) bang = line; /* tolerate missing ! */
    const char *star = strchr(line, '*');
    if (!star) {
        if (out_calc) *out_calc = -1;
        if (out_given) *out_given = -1;
        return 1; /* no checksum provided; do not fail */
    }

    int cksum = 0;
    const char *p = (*bang == '!') ? bang + 1 : bang;
    for (; p < star; p++) cksum ^= (unsigned char)*p;

    int h1 = hexval((unsigned char)star[1]);
    int h2 = hexval((unsigned char)star[2]);
    if (h1 < 0 || h2 < 0) {
        if (out_calc) *out_calc = cksum;
        if (out_given) *out_given = -2;
        return 0;
    }
    int given = (h1 << 4) | h2;

    if (out_calc) *out_calc = cksum;
    if (out_given) *out_given = given;
    return (cksum == given);
}

/* ---------- AIS 6-bit decoding ---------- */

static int ais_sixbit_val(char c) {
    /* Standard AIS armoring: see ITU-R M.1371 */
    int v = (int)(unsigned char)c - 48;
    if (v < 0) return -1;
    if (v > 40) v -= 8;
    if (v < 0 || v > 63) return -1;
    return v;
}

/* Bit buffer: store bits as 0/1 bytes for simplicity */
typedef struct {
    uint8_t *b;
    size_t   nbits;
} bitbuf_t;

static void bitbuf_free(bitbuf_t *bb) {
    if (bb && bb->b) free(bb->b);
    if (bb) { bb->b = NULL; bb->nbits = 0; }
}

static int decode_payload_to_bits(const char *payload, int fill_bits, bitbuf_t *out) {
    if (!payload || !out) return -1;
    size_t plen = strlen(payload);
    if (fill_bits < 0 || fill_bits > 5) return -1;

    size_t total_bits = plen * 6;
    if ((size_t)fill_bits > total_bits) return -1;
    size_t used_bits = total_bits - (size_t)fill_bits;

    uint8_t *bits = (uint8_t*)calloc(used_bits ? used_bits : 1, 1);
    if (!bits) return -1;

    size_t bi = 0;
    for (size_t i = 0; i < plen; i++) {
        int v = ais_sixbit_val(payload[i]);
        if (v < 0) { free(bits); return -1; }
        for (int k = 5; k >= 0; k--) {
            if (bi < used_bits) bits[bi++] = (uint8_t)((v >> k) & 1);
        }
    }

    out->b = bits;
    out->nbits = used_bits;
    return 0;
}

static uint64_t get_uint(const bitbuf_t *bb, size_t start, size_t len) {
    uint64_t v = 0;
    if (!bb || !bb->b) return 0;
    if (len == 0) return 0;
    if (start + len > bb->nbits) return 0;
    for (size_t i = 0; i < len; i++) {
        v = (v << 1) | (uint64_t)bb->b[start + i];
    }
    return v;
}

static int64_t get_int_twos(const bitbuf_t *bb, size_t start, size_t len) {
    /* Two's complement signed integer of bit-length len */
    uint64_t u = get_uint(bb, start, len);
    if (len == 0) return 0;
    uint64_t sign = 1ULL << (len - 1);
    if (u & sign) {
        uint64_t mask = (len == 64) ? ~0ULL : ((1ULL << len) - 1ULL);
        int64_t s = -(int64_t)((~u + 1ULL) & mask);
        return s;
    }
    return (int64_t)u;
}

/* AIS 6-bit text character set (64 chars) */
static const char AIS6[65] =
    "@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_ !\"#$%&'()*+,-./0123456789:;<=>?";

static void get_text6(char *dst, size_t dstsz, const bitbuf_t *bb, size_t start, size_t n6) {
    if (!dst || dstsz == 0) return;
    dst[0] = '\0';
    if (!bb || !bb->b) return;

    size_t outi = 0;
    for (size_t i = 0; i < n6; i++) {
        uint64_t c = get_uint(bb, start + i*6, 6);
        char ch = (c < 64) ? AIS6[c] : '?';
        if (outi + 1 < dstsz) dst[outi++] = ch;
    }
    if (outi < dstsz) dst[outi] = '\0';

    /* Trim trailing @ and spaces */
    while (outi > 0) {
        char t = dst[outi - 1];
        if (t == '@' || t == ' ') { dst[--outi] = '\0'; }
        else break;
    }
    /* Convert remaining @ to space (common convention) */
    for (size_t i = 0; dst[i]; i++) if (dst[i] == '@') dst[i] = ' ';
}

/* ---------- Enumerations / Pretty helpers ---------- */

static const char* msg_type_name(int t) {
    switch (t) {
        case 1: return "Position Report Class A";
        case 2: return "Position Report Class A (Assigned schedule)";
        case 3: return "Position Report Class A (Response to interrogation)";
        case 4: return "Base Station Report";
        case 5: return "Static and Voyage Related Data";
        case 18: return "Standard Class B CS Position Report";
        case 19: return "Extended Class B Equipment Position Report";
        case 21: return "Aid-to-Navigation Report";
        case 24: return "Class B Static Data Report";
        default: return "Unknown/Other";
    }
}

static const char* nav_status_name(uint64_t s) {
    switch (s) {
        case 0: return "Under way using engine";
        case 1: return "At anchor";
        case 2: return "Not under command";
        case 3: return "Restricted manoeuvrability";
        case 4: return "Constrained by her draught";
        case 5: return "Moored";
        case 6: return "Aground";
        case 7: return "Engaged in fishing";
        case 8: return "Under way sailing";
        case 9: return "Reserved for future";
        case 10: return "Reserved for future";
        case 11: return "Reserved for future";
        case 12: return "Reserved for future";
        case 13: return "Reserved for future";
        case 14: return "AIS-SART / MOB-AIS / EPIRB-AIS";
        case 15: return "Not defined";
        default: return "Unknown";
    }
}

static const char* epfd_name(uint64_t e) {
    switch (e) {
        case 0: return "Undefined";
        case 1: return "GPS";
        case 2: return "GLONASS";
        case 3: return "Combined GPS/GLONASS";
        case 4: return "Loran-C";
        case 5: return "Chayka";
        case 6: return "Integrated navigation system";
        case 7: return "Surveyed";
        case 8: return "Galileo";
        default: return "Unknown";
    }
}

/* Not exhaustive; enough for “nice output”. */
static const char* shiptype_name(uint64_t t) {
    switch (t) {
        case 0: return "Not available / no ship type";
        case 20: return "Wing in ground (WIG)";
        case 30: return "Fishing";
        case 31: return "Towing";
        case 32: return "Towing: length exceeds 200m or breadth exceeds 25m";
        case 33: return "Dredging or underwater ops";
        case 34: return "Diving ops";
        case 35: return "Military ops";
        case 36: return "Sailing";
        case 37: return "Pleasure craft";
        case 40: return "High-speed craft (HSC)";
        case 50: return "Pilot vessel";
        case 52: return "Tug";
        case 53: return "Port tender";
        case 54: return "Anti-pollution equipment";
        case 55: return "Law enforcement";
        case 58: return "Medical transport";
        case 59: return "Non-combatant ship";
        case 60: return "Passenger";
        case 70: return "Cargo";
        case 80: return "Tanker";
        case 90: return "Other type";
        default: return "Other / subtype not mapped";
    }
}

static void print_latlon(const char *label, double deg, double na_threshold_abs, const char *fmt_extra) {
    /* na_threshold_abs: if |deg| > threshold => n/a */
    if (deg > na_threshold_abs || deg < -na_threshold_abs) {
        printf("  %-22s: n/a\n", label);
    } else {
        printf("  %-22s: %.6f%s\n", label, deg, (fmt_extra ? fmt_extra : ""));
    }
}

static void print_speed(const char *label, uint64_t sog10) {
    if (sog10 == 1023) printf("  %-22s: n/a\n", label);
    else printf("  %-22s: %.1f kn\n", label, (double)sog10 / 10.0);
}

static void print_course(const char *label, uint64_t cog10) {
    if (cog10 >= 3600) printf("  %-22s: n/a\n", label);
    else printf("  %-22s: %.1f deg\n", label, (double)cog10 / 10.0);
}

static void print_heading(const char *label, uint64_t hdg) {
    if (hdg == 511) printf("  %-22s: n/a\n", label);
    else printf("  %-22s: %u deg\n", label, (unsigned)hdg);
}

/* ---------- NMEA/AIVDM parsing & fragment reassembly ---------- */

typedef struct {
    int in_use;
    char key[64];          /* seq+chan+talker-ish */
    int total;
    int have;
    char payload[2048];    /* concatenated payload */
    int last_fill;
} frag_slot_t;

#define FRAG_SLOTS 16
static frag_slot_t g_slots[FRAG_SLOTS];

static frag_slot_t* frag_get_slot(const char *key) {
    /* Find existing */
    for (int i = 0; i < FRAG_SLOTS; i++) {
        if (g_slots[i].in_use && strcmp(g_slots[i].key, key) == 0) return &g_slots[i];
    }
    /* Allocate new */
    for (int i = 0; i < FRAG_SLOTS; i++) {
        if (!g_slots[i].in_use) {
            memset(&g_slots[i], 0, sizeof(g_slots[i]));
            g_slots[i].in_use = 1;
            strncpy(g_slots[i].key, key, sizeof(g_slots[i].key)-1);
            return &g_slots[i];
        }
    }
    /* Fallback: evict slot 0 */
    memset(&g_slots[0], 0, sizeof(g_slots[0]));
    g_slots[0].in_use = 1;
    strncpy(g_slots[0].key, key, sizeof(g_slots[0].key)-1);
    return &g_slots[0];
}

static void frag_clear(frag_slot_t *s) {
    if (!s) return;
    memset(s, 0, sizeof(*s));
}

typedef struct {
    char sentence[8];  /* AIVDM/AIVDO */
    int total;
    int num;
    char seq[16];
    char chan[4];
    char payload[1024];
    int fill;
    int checksum_ok;
    int checksum_calc;
    int checksum_given;
} nmea_ais_t;

static int parse_aivdm_line(const char *line, nmea_ais_t *out) {
    if (!line || !out) return -1;
    memset(out, 0, sizeof(*out));

    out->checksum_ok = nmea_checksum_ok(line, &out->checksum_calc, &out->checksum_given);

    /* Copy up to '*' for CSV parsing */
    char buf[2048];
    strncpy(buf, line, sizeof(buf)-1);
    buf[sizeof(buf)-1] = '\0';
    char *star = strchr(buf, '*');
    if (star) *star = '\0';

    /* Must start with !AIVDM or !AIVDO (tolerate leading whitespace) */
    while (isspace((unsigned char)*buf)) memmove(buf, buf+1, strlen(buf));
    if (buf[0] != '!' && buf[0] != '$') return -1;
    char *p = buf + 1;
    if (strncmp(p, "AIVDM", 5) != 0 && strncmp(p, "AIVDO", 5) != 0) return -1;

    strncpy(out->sentence, p, 5);
    out->sentence[5] = '\0';

    /* Tokenize CSV: AIVDM,total,num,seq,chan,payload,fill */
    /* We already skipped leading '!'/'$'. */
    char *save = NULL;
    char *tok = strtok_r(p, ",", &save); /* AIVDM */
    (void)tok;

    char *t_total   = strtok_r(NULL, ",", &save);
    char *t_num     = strtok_r(NULL, ",", &save);
    char *t_seq     = strtok_r(NULL, ",", &save);
    char *t_chan    = strtok_r(NULL, ",", &save);
    char *t_payload = strtok_r(NULL, ",", &save);
    char *t_fill    = strtok_r(NULL, ",", &save);

    if (!t_total || !t_num || !t_seq || !t_chan || !t_payload || !t_fill) return -1;

    out->total = atoi(t_total);
    out->num   = atoi(t_num);
    strncpy(out->seq, t_seq, sizeof(out->seq)-1);
    strncpy(out->chan, t_chan, sizeof(out->chan)-1);
    strncpy(out->payload, t_payload, sizeof(out->payload)-1);
    out->fill = atoi(t_fill);

    if (out->total < 1 || out->num < 1 || out->num > out->total) return -1;
    if (out->fill < 0 || out->fill > 5) return -1;

    return 0;
}

/* Attempt reassembly; if complete, returns 1 and fills out_payload/out_fill. If not, returns 0. */
static int reassemble_if_needed(const nmea_ais_t *m, char *out_payload, size_t outsz, int *out_fill) {
    if (!m || !out_payload || outsz == 0 || !out_fill) return 0;

    if (m->total == 1) {
        strncpy(out_payload, m->payload, outsz-1);
        out_payload[outsz-1] = '\0';
        *out_fill = m->fill;
        return 1;
    }

    /* Key: sentence + seq + chan + total. seq may be empty. */
    char key[64];
    snprintf(key, sizeof(key), "%s|%s|%s|%d", m->sentence, m->seq, m->chan, m->total);

    frag_slot_t *s = frag_get_slot(key);

    /* If starting new set or mismatch, reset */
    if (m->num == 1) {
        s->total = m->total;
        s->have  = 0;
        s->payload[0] = '\0';
    } else {
        /* If we get a mid-fragment without a start, reset anyway */
        if (s->have == 0) {
            s->total = m->total;
            s->payload[0] = '\0';
        }
    }

    /* Append payload */
    size_t cur = strlen(s->payload);
    size_t add = strlen(m->payload);
    if (cur + add + 1 >= sizeof(s->payload)) {
        frag_clear(s);
        return 0;
    }
    memcpy(s->payload + cur, m->payload, add + 1);
    s->have = m->num;
    s->last_fill = m->fill;

    if (m->num == m->total) {
        strncpy(out_payload, s->payload, outsz-1);
        out_payload[outsz-1] = '\0';
        *out_fill = s->last_fill;
        frag_clear(s);
        return 1;
    }
    return 0;
}

/* ---------- Decoders for message types ---------- */

static void print_common_header(const bitbuf_t *bb) {
    uint64_t type   = get_uint(bb, 0, 6);
    uint64_t repeat = get_uint(bb, 6, 2);
    uint64_t mmsi   = get_uint(bb, 8, 30);

    printf("AIS Message\n");
    printf("  %-22s: %u (%s)\n", "Message Type", (unsigned)type, msg_type_name((int)type));
    printf("  %-22s: %u\n", "Repeat Indicator", (unsigned)repeat);
    printf("  %-22s: %09u\n", "MMSI", (unsigned)mmsi);
}

static void decode_type_123(const bitbuf_t *bb) {
    /* Type 1/2/3: 168 bits */
    uint64_t nav = get_uint(bb, 38, 4);
    int64_t  rot = get_int_twos(bb, 42, 8);
    uint64_t sog = get_uint(bb, 50, 10);
    uint64_t acc = get_uint(bb, 60, 1);
    int64_t  lon_raw = get_int_twos(bb, 61, 28);
    int64_t  lat_raw = get_int_twos(bb, 89, 27);
    uint64_t cog = get_uint(bb, 116, 12);
    uint64_t hdg = get_uint(bb, 128, 9);
    uint64_t ts  = get_uint(bb, 137, 6);
    uint64_t man = get_uint(bb, 143, 2);
    uint64_t raim = get_uint(bb, 148, 1);
    uint64_t radio = get_uint(bb, 149, 19);

    double lon = (double)lon_raw / 600000.0;
    double lat = (double)lat_raw / 600000.0;

    printf("  %-22s: %u (%s)\n", "Navigation Status", (unsigned)nav, nav_status_name(nav));
    printf("  %-22s: %lld (raw)\n", "Rate of Turn", (long long)rot);
    print_speed("Speed Over Ground", sog);
    printf("  %-22s: %s\n", "Position Accuracy", acc ? "High (<10m)" : "Low (>10m)");
    print_latlon("Longitude", lon, 181.0, " deg");
    print_latlon("Latitude",  lat,  91.0, " deg");
    print_course("Course Over Ground", cog);
    print_heading("True Heading", hdg);
    if (ts == 60) printf("  %-22s: n/a\n", "Timestamp (sec)");
    else printf("  %-22s: %u s\n", "Timestamp (sec)", (unsigned)ts);
    printf("  %-22s: %u\n", "Maneuver Indicator", (unsigned)man);
    printf("  %-22s: %s\n", "RAIM", raim ? "In use" : "Not in use");
    printf("  %-22s: 0x%05X (%u)\n", "Radio Status (SOTDMA)", (unsigned)radio, (unsigned)radio);
}

static void decode_type_4(const bitbuf_t *bb) {
    uint64_t year  = get_uint(bb, 38, 14);
    uint64_t month = get_uint(bb, 52, 4);
    uint64_t day   = get_uint(bb, 56, 5);
    uint64_t hour  = get_uint(bb, 61, 5);
    uint64_t min   = get_uint(bb, 66, 6);
    uint64_t sec   = get_uint(bb, 72, 6);
    uint64_t acc   = get_uint(bb, 78, 1);
    int64_t  lon_raw = get_int_twos(bb, 79, 28);
    int64_t  lat_raw = get_int_twos(bb, 107, 27);
    uint64_t epfd  = get_uint(bb, 134, 4);
    uint64_t raim  = get_uint(bb, 147, 1);
    uint64_t radio = get_uint(bb, 148, 19);

    double lon = (double)lon_raw / 600000.0;
    double lat = (double)lat_raw / 600000.0;

    printf("  %-22s: %04u-%02u-%02u %02u:%02u:%02u UTC\n",
           "UTC", (unsigned)year, (unsigned)month, (unsigned)day,
           (unsigned)hour, (unsigned)min, (unsigned)sec);
    printf("  %-22s: %s\n", "Position Accuracy", acc ? "High (<10m)" : "Low (>10m)");
    print_latlon("Longitude", lon, 181.0, " deg");
    print_latlon("Latitude",  lat,  91.0, " deg");
    printf("  %-22s: %u (%s)\n", "EPFD", (unsigned)epfd, epfd_name(epfd));
    printf("  %-22s: %s\n", "RAIM", raim ? "In use" : "Not in use");
    printf("  %-22s: 0x%05X (%u)\n", "Radio Status (SOTDMA)", (unsigned)radio, (unsigned)radio);
}

static void decode_type_5(const bitbuf_t *bb) {
    uint64_t aisver = get_uint(bb, 38, 2);
    uint64_t imo    = get_uint(bb, 40, 30);
    char callsign[8] = {0};
    char name[21] = {0};
    uint64_t shiptype = get_uint(bb, 232, 8);
    uint64_t bow  = get_uint(bb, 240, 9);
    uint64_t stern= get_uint(bb, 249, 9);
    uint64_t port = get_uint(bb, 258, 6);
    uint64_t stbd = get_uint(bb, 264, 6);
    uint64_t epfd = get_uint(bb, 270, 4);
    uint64_t eta_mo = get_uint(bb, 274, 4);
    uint64_t eta_d  = get_uint(bb, 278, 5);
    uint64_t eta_h  = get_uint(bb, 283, 5);
    uint64_t eta_min= get_uint(bb, 288, 6);
    uint64_t draught = get_uint(bb, 294, 8);
    char dest[21] = {0};
    uint64_t dte = get_uint(bb, 422, 1);

    get_text6(callsign, sizeof(callsign), bb, 70, 7);
    get_text6(name, sizeof(name), bb, 112, 20);
    get_text6(dest, sizeof(dest), bb, 302, 20);

    printf("  %-22s: %u\n", "AIS Version", (unsigned)aisver);
    if (imo == 0) printf("  %-22s: n/a\n", "IMO Number");
    else printf("  %-22s: %u\n", "IMO Number", (unsigned)imo);
    printf("  %-22s: %s\n", "Call Sign", callsign[0] ? callsign : "(empty)");
    printf("  %-22s: %s\n", "Vessel Name", name[0] ? name : "(empty)");
    printf("  %-22s: %u (%s)\n", "Ship Type", (unsigned)shiptype, shiptype_name(shiptype));
    printf("  %-22s: bow=%u m, stern=%u m, port=%u m, starboard=%u m\n",
           "Dimensions", (unsigned)bow, (unsigned)stern, (unsigned)port, (unsigned)stbd);
    printf("  %-22s: %u (%s)\n", "EPFD", (unsigned)epfd, epfd_name(epfd));
    if (eta_mo == 0 || eta_d == 0) {
        printf("  %-22s: n/a\n", "ETA (UTC)");
    } else {
        printf("  %-22s: %02u-%02u %02u:%02u (UTC)\n",
               "ETA (UTC)", (unsigned)eta_mo, (unsigned)eta_d, (unsigned)eta_h, (unsigned)eta_min);
    }
    if (draught == 0) printf("  %-22s: 0.0 m\n", "Draught");
    else printf("  %-22s: %.1f m\n", "Draught", (double)draught / 10.0);
    printf("  %-22s: %s\n", "Destination", dest[0] ? dest : "(empty)");
    printf("  %-22s: %s\n", "DTE", dte ? "Data terminal ready" : "Not ready (default)");
}

static void decode_type_18(const bitbuf_t *bb) {
    uint64_t sog = get_uint(bb, 46, 10);
    uint64_t acc = get_uint(bb, 56, 1);
    int64_t lon_raw = get_int_twos(bb, 57, 28);
    int64_t lat_raw = get_int_twos(bb, 85, 27);
    uint64_t cog = get_uint(bb, 112, 12);
    uint64_t hdg = get_uint(bb, 124, 9);
    uint64_t ts  = get_uint(bb, 133, 6);
    uint64_t cs  = get_uint(bb, 141, 1);
    uint64_t disp= get_uint(bb, 142, 1);
    uint64_t dsc = get_uint(bb, 143, 1);
    uint64_t band= get_uint(bb, 144, 1);
    uint64_t m22 = get_uint(bb, 145, 1);
    uint64_t asg = get_uint(bb, 146, 1);
    uint64_t raim= get_uint(bb, 147, 1);
    uint64_t comm= get_uint(bb, 148, 20);

    double lon = (double)lon_raw / 600000.0;
    double lat = (double)lat_raw / 600000.0;

    print_speed("Speed Over Ground", sog);
    printf("  %-22s: %s\n", "Position Accuracy", acc ? "High (<10m)" : "Low (>10m)");
    print_latlon("Longitude", lon, 181.0, " deg");
    print_latlon("Latitude",  lat,  91.0, " deg");
    print_course("Course Over Ground", cog);
    print_heading("True Heading", hdg);
    if (ts == 60) printf("  %-22s: n/a\n", "Timestamp (sec)");
    else printf("  %-22s: %u s\n", "Timestamp (sec)", (unsigned)ts);

    printf("  %-22s: %s\n", "CS Unit", cs ? "Yes" : "No");
    printf("  %-22s: %s\n", "Display", disp ? "Yes" : "No");
    printf("  %-22s: %s\n", "DSC", dsc ? "Yes" : "No");
    printf("  %-22s: %s\n", "Band", band ? "Yes" : "No");
    printf("  %-22s: %s\n", "Msg 22", m22 ? "Yes" : "No");
    printf("  %-22s: %s\n", "Assigned Mode", asg ? "Yes" : "No");
    printf("  %-22s: %s\n", "RAIM", raim ? "In use" : "Not in use");
    printf("  %-22s: 0x%05X (%u)\n", "Comm State (ITDMA/SOTDMA)", (unsigned)comm, (unsigned)comm);
}

static void decode_type_19(const bitbuf_t *bb) {
    /* Extended Class B: many fields, length typically 312 bits. */
    uint64_t sog = get_uint(bb, 46, 10);
    uint64_t acc = get_uint(bb, 56, 1);
    int64_t lon_raw = get_int_twos(bb, 57, 28);
    int64_t lat_raw = get_int_twos(bb, 85, 27);
    uint64_t cog = get_uint(bb, 112, 12);
    uint64_t hdg = get_uint(bb, 124, 9);
    uint64_t ts  = get_uint(bb, 133, 6);

    char name[21] = {0};
    get_text6(name, sizeof(name), bb, 143, 20); /* 120 bits */

    uint64_t shiptype = get_uint(bb, 263, 8);
    uint64_t bow  = get_uint(bb, 271, 9);
    uint64_t stern= get_uint(bb, 280, 9);
    uint64_t port = get_uint(bb, 289, 6);
    uint64_t stbd = get_uint(bb, 295, 6);
    uint64_t epfd = get_uint(bb, 301, 4);
    uint64_t raim = (bb->nbits >= 307) ? get_uint(bb, 305, 1) : 0;
    uint64_t dte  = (bb->nbits >= 309) ? get_uint(bb, 306, 1) : 0;

    double lon = (double)lon_raw / 600000.0;
    double lat = (double)lat_raw / 600000.0;

    print_speed("Speed Over Ground", sog);
    printf("  %-22s: %s\n", "Position Accuracy", acc ? "High (<10m)" : "Low (>10m)");
    print_latlon("Longitude", lon, 181.0, " deg");
    print_latlon("Latitude",  lat,  91.0, " deg");
    print_course("Course Over Ground", cog);
    print_heading("True Heading", hdg);
    if (ts == 60) printf("  %-22s: n/a\n", "Timestamp (sec)");
    else printf("  %-22s: %u s\n", "Timestamp (sec)", (unsigned)ts);

    printf("  %-22s: %s\n", "Vessel Name", name[0] ? name : "(empty)");
    printf("  %-22s: %u (%s)\n", "Ship Type", (unsigned)shiptype, shiptype_name(shiptype));
    printf("  %-22s: bow=%u m, stern=%u m, port=%u m, starboard=%u m\n",
           "Dimensions", (unsigned)bow, (unsigned)stern, (unsigned)port, (unsigned)stbd);
    printf("  %-22s: %u (%s)\n", "EPFD", (unsigned)epfd, epfd_name(epfd));
    printf("  %-22s: %s\n", "DTE", dte ? "Data terminal ready" : "Not ready (default)");
    printf("  %-22s: %s\n", "RAIM", raim ? "In use" : "Not in use");
}

static void decode_type_24(const bitbuf_t *bb) {
    uint64_t part = get_uint(bb, 38, 2);

    printf("  %-22s: %u\n", "Part Number", (unsigned)part);

    if (part == 0) {
        char name[21] = {0};
        get_text6(name, sizeof(name), bb, 40, 20);
        printf("  %-22s: %s\n", "Vessel Name", name[0] ? name : "(empty)");
    } else if (part == 1) {
        uint64_t shiptype = get_uint(bb, 40, 8);
        char vendor[8] = {0};
        char callsign[8] = {0};
        uint64_t bow  = get_uint(bb, 132, 9);
        uint64_t stern= get_uint(bb, 141, 9);
        uint64_t port = get_uint(bb, 150, 6);
        uint64_t stbd = get_uint(bb, 156, 6);

        get_text6(vendor, sizeof(vendor), bb, 48, 7);
        get_text6(callsign, sizeof(callsign), bb, 90, 7);

        printf("  %-22s: %u (%s)\n", "Ship Type", (unsigned)shiptype, shiptype_name(shiptype));
        printf("  %-22s: %s\n", "Vendor ID", vendor[0] ? vendor : "(empty)");
        printf("  %-22s: %s\n", "Call Sign", callsign[0] ? callsign : "(empty)");
        printf("  %-22s: bow=%u m, stern=%u m, port=%u m, starboard=%u m\n",
               "Dimensions", (unsigned)bow, (unsigned)stern, (unsigned)port, (unsigned)stbd);
    } else {
        printf("  %-22s: (unsupported part)\n", "Static Data");
    }
}

static void decode_and_print(const bitbuf_t *bb) {
    if (!bb || !bb->b || bb->nbits < 38) {
        printf("AIS Message\n  (insufficient bits)\n");
        return;
    }

    uint64_t type = get_uint(bb, 0, 6);
    print_common_header(bb);

    printf("  %-22s: %zu\n", "Bit Length", bb->nbits);

    switch ((int)type) {
        case 1:
        case 2:
        case 3:
            if (bb->nbits < 168) printf("  Warning: expected >= 168 bits for type %u\n", (unsigned)type);
            decode_type_123(bb);
            break;
        case 4:
            if (bb->nbits < 168) printf("  Warning: expected >= 168 bits for type 4\n");
            decode_type_4(bb);
            break;
        case 5:
            if (bb->nbits < 424) printf("  Warning: expected >= 424 bits for type 5\n");
            decode_type_5(bb);
            break;
        case 18:
            if (bb->nbits < 168) printf("  Warning: expected >= 168 bits for type 18\n");
            decode_type_18(bb);
            break;
        case 19:
            if (bb->nbits < 312) printf("  Warning: expected >= 312 bits for type 19\n");
            decode_type_19(bb);
            break;
        case 24:
            if (bb->nbits < 160) printf("  Warning: expected >= 160 bits for type 24\n");
            decode_type_24(bb);
            break;
        default:
            printf("  %-22s: (decoder not implemented for this type)\n", "Details");
            printf("  %-22s: first 96 bits shown as hex groups\n", "Raw");
            /* show some raw content in 6-bit groups */
            {
                size_t groups = bb->nbits / 6;
                size_t show = groups < 16 ? groups : 16;
                printf("    6-bit groups (first %zu):", show);
                for (size_t i = 0; i < show; i++) {
                    uint64_t v = get_uint(bb, i*6, 6);
                    printf(" %02X", (unsigned)v);
                }
                printf("\n");
            }
            break;
    }
}

/* ---------- Main ---------- */

int main(void) {
    char line[4096];

    while (fgets(line, sizeof(line), stdin)) {
        rstrip_newline(line);
        if (line[0] == '\0') continue;

        nmea_ais_t m;
        if (parse_aivdm_line(line, &m) != 0) {
            /* ignore non-AIVDM/AIVDO lines, but be explicit */
            fprintf(stderr, "Skipping unrecognized/invalid line: %s\n", line);
            continue;
        }

        char payload_full[4096];
        int fill_full = 0;
        int complete = reassemble_if_needed(&m, payload_full, sizeof(payload_full), &fill_full);
        if (!complete) {
            /* waiting for more fragments */
            continue;
        }

        bitbuf_t bb = {0};
        if (decode_payload_to_bits(payload_full, fill_full, &bb) != 0) {
            fprintf(stderr, "Failed to decode payload to bits (payload length=%zu)\n", strlen(payload_full));
            continue;
        }

        /* Print per-message with NMEA context */
        printf("================================================================\n");
        printf("NMEA Sentence\n");
        printf("  %-22s: %s\n", "Sentence", m.sentence);
        printf("  %-22s: %d/%d\n", "Fragment", m.num, m.total);
        printf("  %-22s: %s\n", "Sequence ID", (m.seq[0] ? m.seq : "(empty)"));
        printf("  %-22s: %s\n", "Channel", (m.chan[0] ? m.chan : "(empty)"));
        printf("  %-22s: %d\n", "Fill Bits", fill_full);
        printf("  %-22s: %s", "Checksum", m.checksum_ok ? "OK" : "BAD");
        if (m.checksum_given >= 0 && m.checksum_calc >= 0)
            printf(" (given=%02X calc=%02X)", m.checksum_given, m.checksum_calc);
        printf("\n");
        printf("  %-22s: %zu chars\n", "Payload Length", strlen(payload_full));

        decode_and_print(&bb);

        bitbuf_free(&bb);
        fflush(stdout);
    }

    return 0;
}
