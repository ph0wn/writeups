#include <stdio.h>
#include <stdlib.h>
#include "pico/stdlib.h"
#include "challenge.h"
#include "hardware/pio.h"
#include "hardware/clocks.h"
#include "ws2812.pio.h"

#define VERSION "0.15"
#define WS2812_PIN 5


typedef struct challenge {
  char name[20];
  void (*function)();
} challenge_t;

challenge_t challenges[] = {
  {"Hardware", picopcb},
  {"Car", rp2040}
};

#define NUM_CHALLENGES (sizeof(challenges) / sizeof(challenges[0]))

PIO pio = pio0;

static inline uint32_t urgb_u32(uint8_t r, uint8_t g, uint8_t b) {
    return ((uint32_t) (r) << 8) |
      ((uint32_t) (g) << 16) |
      (uint32_t) (b);
}

static inline void put_pixel(uint32_t pixel_grb) {
    pio_sm_put_blocking(pio0, 0, pixel_grb << 8u);
}

void display_menu() {
  for (int i = 0; i < NUM_CHALLENGES; i++) {
    printf("Stage %d: %s\n", i+1, challenges[i].name);
  }
  printf("Select challenge: ");
}

void stage2_indicator(){
  printf("STAGE 2 unlock! This message indicates you have reached a milestone where you have all you need to look into stage 2 if you wish. This is not a flag (neither for stage 1, nor stage 2), just an unlock\n");
}

void select_challenge() {
  display_menu();
  int selection = -1;
  int input = getchar_timeout_us(10000000);  // 10-second timeout for user input
  if (input != PICO_ERROR_TIMEOUT && input != '\n') {
    selection = input - '0';  // Convert char to number
  }
  if (selection > 0 && selection <= NUM_CHALLENGES) {
    printf("\nStarting challenge: %s\n", challenges[selection-1].name);
    if (selection-1 == 0) {
      put_pixel(urgb_u32(0, 0xff, 0)); // green
      put_pixel(urgb_u32(0, 0xff, 0)); // green
    } else {
      put_pixel(urgb_u32(0xff, 0, 0)); // red
      put_pixel(urgb_u32(0xff, 0, 0)); // red
    }
    challenges[selection-1].function();  // Call the selected function
  } else {
    printf("Invalid selection. Please try again.\n");
    if (selection == 0x7069636f) {
      stage2_indicator();
    }
  }
  
}

void ws2812_init() {
  int sm = 0;
  
  uint offset = pio_add_program(pio, &ws2812_program);
  ws2812_program_init(pio, sm, offset, WS2812_PIN, 800000, false);

}

void pattern_sparkle() {
  for (int j=0; j < 500; j++) {
    for (uint i = 0; i < 2; ++i)
      put_pixel(rand() % 16 ? 0 : 0xffffffff);
    sleep_ms(10);
  }
}

void menu_colors() {
  put_pixel(urgb_u32(0xff, 0x71, 0)); // orange
  put_pixel(urgb_u32(0xff, 0x71, 0));
  sleep_ms(30);
}

int main() {
  stdio_init_all();
  ws2812_init();

  while (!stdio_usb_connected()) {
    pattern_sparkle();
    sleep_ms(80);
  }

  while(true) {
    menu_colors();    
    printf("Pico PCB Loader v%s...\n", VERSION);
    sleep_ms(20);
    printf("-----------------------------\n");
    printf("Welcome to the Pico PCB Board\n");
    select_challenge();
    printf("\n");
    sleep_ms(500);
  }

  return 0;
}
