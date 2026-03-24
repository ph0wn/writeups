/*
 * Time-lock CTF — C version. Code = f(epoch minutes); accept t-1, t, t+1.
 */

#include <ctype.h>
#include <inttypes.h>
#include <stdint.h>
#include <stdio.h>
#include <string.h>
#include <time.h>

#define MOD_14 UINT64_C(100000000000000)

static const uint64_t K1 = UINT64_C(0xC0B41E7ED15EA5E1);
static const uint64_t K2 = UINT64_C(0xBADC0FFEE0DDF00D);
static const uint64_t K3 = UINT64_C(0x1EA57EADBEAD1DEA);

static uint64_t generate_code(uint64_t minutes)
{
    uint64_t x = minutes ^ K1;
    x *= K2;
    x = (x << 5) | (x >> 59);
    x += K3;
    return x % MOD_14;
}

static int is_valid(const char *guess, uint64_t now)
{
    if (!guess || strlen(guess) != 14)
        return 0;
    for (int i = 0; i < 14; i++)
        if (!isdigit((unsigned char)guess[i]))
            return 0;

    char expected[15];
    uint64_t code;

    if (now > 0) {
        code = generate_code(now - 1);
        snprintf(expected, sizeof(expected), "%014" PRIu64, code);
        if (strcmp(guess, expected) == 0) return 1;
    }
    code = generate_code(now);
    snprintf(expected, sizeof(expected), "%014" PRIu64, code);
    if (strcmp(guess, expected) == 0) return 1;
    code = generate_code(now + 1);
    snprintf(expected, sizeof(expected), "%014" PRIu64, code);
    if (strcmp(guess, expected) == 0) return 1;
    return 0;
}

int main(void)
{
    uint64_t now = (uint64_t)time(NULL) / 60;

    printf("Enter 14-digit code: ");
    fflush(stdout);

    char buf[256];
    if (!fgets(buf, sizeof(buf), stdin)) {
        printf("ACCESS DENIED\n");
        return 0;
    }
    size_t len = strlen(buf);
    if (len > 0 && buf[len - 1] == '\n')
        buf[len - 1] = '\0';

    if (is_valid(buf, now))
        printf("ACCESS GRANTED\n");
    else
        printf("ACCESS DENIED\n");
    return 0;
}
