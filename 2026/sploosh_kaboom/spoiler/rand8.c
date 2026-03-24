#include <stdint.h>

uint32_t prng_state;

uint32_t rand8(uint8_t max) {
    uint32_t state = prng_state;
    state ^= (state << 13);
    state ^= (state >> 17);
    state ^= (state << 5);
    prng_state = state;
    uint32_t quotient = state / max;
    return state - (quotient * max);
}
