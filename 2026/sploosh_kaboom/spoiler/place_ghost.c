void place_ghost(void) {
    extern unsigned char escape_count;
    extern unsigned int prng_state;
    extern unsigned int prng_ghost[];
    
    unsigned int xor_value = prng_ghost[escape_count - 1];
    prng_state ^= xor_value;
    
    unsigned char x, y, direction;
    
    while (1) {
        direction = (rand8(2) == 0) ? 0 : 1;
        x = rand8(12);
        y = rand8(6);
        
        if (collision(x, y, 1, direction) == 0) {
            break;
        }
    }
    
    mark(x, y, 216, 12, 2);
}
