/* r2ai with Claude 4.5 */
#include <stdint.h>
#include <stdbool.h>
#include <string.h>

#define GRID_SIZE 24
#define MAX_SHIPS 5
#define MAX_SHOTS 16

int main(int argc, char *argv[]) {
    void *device_gpio = (void *)0x39b60;
    void *device_cdc = (void *)0x39ba0;
    void *device_display = (void *)0x39c20;
    void *gpio_data = (void *)0x20009cc0;
    void *gpio_api = (void *)0x39ef8;
    
    if (!z_impl_device_is_ready(device_gpio)) {
        return -1;
    }
    
    uint32_t *gpio_reg = (uint32_t *)gpio_data;
    void **api = (void **)gpio_api;
    
    *gpio_reg &= ~0x800;
    
    void (*gpio_func1)(void *, uint32_t, uint32_t) = api[0];
    gpio_func1(device_gpio, 0xb, 0xa0000);
    
    uint32_t gpio_val = *gpio_reg;
    gpio_val <<= 0x14;
    
    void (*gpio_func2)(void *, uint32_t) = (gpio_val >= 0) ? api[3] : api[4];
    gpio_func2(device_gpio, 0x800);
    
    if (!z_impl_device_is_ready(device_cdc)) {
        return -1;
    }
    
    int usb_ret = usb_enable(0);
    if (usb_ret != 0) {
        return -1;
    }
    
    uint32_t random_val;
    z_impl_sys_rand_get(&random_val, 4);
    
    uint32_t *game_id_ptr = (uint32_t *)0x20008d78;
    uint32_t *prng_state_ptr = (uint32_t *)0x20008d7c;
    *game_id_ptr = random_val;
    *prng_state_ptr = random_val;
    
    if (!z_impl_device_is_ready(device_display)) {
        return -1;
    }
    
    void **display_api = (void **)(*(uint32_t *)(device_display + 8));
    void (*display_blanking)(void *) = display_api[1];
    if (display_blanking) {
        display_blanking(device_display);
    }
    
    lv_init();
    
    void *lvgl_timer = (void *)0x200002a0;
    k_timer_start(lvgl_timer, 1, 0x21);
    
    void *screen = lv_display_get_default();
    if (!screen) {
        return -1;
    }
    
    screen = lv_screen_active();
    void *logo_img = lv_image_create(screen);
    void *logo_data = (void *)0x3a128;
    lv_image_set_src(logo_img, logo_data);
    lv_obj_center(logo_img);
    lv_obj_set_style_opa(logo_img, 0xff, 0);
    lv_timer_handler();
    k_sleep(0x8000, 0);
    lv_obj_delete(logo_img);
    
    screen = lv_screen_active();
    void *board = lv_obj_create(screen);
    *(void **)0x20008da0 = board;
    lv_obj_set_size(board, 0xf0, 0x122);
    lv_obj_align(board, 2, 0, 0);
    
    uint32_t bg_color = lv_color_hex(0x482ff);
    lv_obj_set_style_bg_color(board, bg_color, 0);
    lv_obj_set_style_bg_opa(board, 0xff, 0);
    lv_obj_set_style_border_width(board, 0, 0);
    lv_obj_set_style_radius(board, 0, 0);
    lv_obj_set_style_pad_all(board, 0);
    lv_obj_remove_flag(board, 0x10);
    lv_obj_add_flag(board, 2);
    lv_obj_add_event_cb(board, (void *)0x799, 0x1d, (void *)0x3b470);
    
    lv_obj_invalidate(screen);
    
    screen = lv_screen_active();
    uint32_t screen_color = lv_color_hex(0x482ff);
    lv_obj_set_style_bg_color(screen, screen_color, 0);
    
    void *title_label = lv_label_create(screen);
    lv_label_set_recolor(title_label, 1);
    lv_label_set_text(title_label, "#FFAC1C SPLOOSH# #D12300 KABOOM# #A80088 2077#");
    lv_obj_align(title_label, 2, 0, 0);
    
    void *game_id_label = lv_label_create(screen);
    lv_label_set_recolor(game_id_label, 1);
    char game_id_buf[30];
    snprintf(game_id_buf, 30, "#0000AA GAME ID - %08x #", *game_id_ptr);
    lv_label_set_text(game_id_label, game_id_buf);
    lv_obj_align(game_id_label, 2, 0, 0xe);
    
    void *score_label = lv_label_create(screen);
    char score_buf[52];
    snprintf(score_buf, 52, "0%*s%d", 0x30, "", 0x17);
    lv_label_set_text(score_label, score_buf);
    lv_obj_align(score_label, 1, 0xa, 0x1e);
    
    char shots_buf[4];
    snprintf(shots_buf, 4, "%d", 0x17);
    void *shots_label = lv_label_create(screen);
    lv_label_set_text(shots_label, shots_buf);
    lv_obj_align(shots_label, 1, 5, 0xe6);
    
    void *reset_btn = lv_button_create(screen);
    *(void **)0x20008d84 = reset_btn;
    lv_obj_set_size(reset_btn, 0x5a, 0x14);
    lv_obj_align(reset_btn, 5, 0, -10);
    uint32_t reset_color = lv_color_hex(0xff2200);
    lv_obj_set_style_bg_color(reset_btn, reset_color, 0);
    lv_obj_add_event_cb(reset_btn, (void *)0x1fa27, 0xa, 0);
    void *reset_label = lv_label_create(reset_btn);
    lv_label_set_text(reset_label, "RESET");
    lv_obj_center(reset_label);
    
    lv_obj_invalidate(screen);
    
    screen = lv_screen_active();
    void *toast_label = lv_label_create(screen);
    *(void **)0x20008d9c = toast_label;
    lv_label_set_text(toast_label, "");
    lv_label_set_recolor(toast_label, 1);
    lv_label_set_long_mode(toast_label, 0);
    uint32_t toast_color = lv_color_hex(0xa80088);
    lv_obj_set_style_bg_color(toast_label, toast_color, 0);
    lv_obj_set_style_bg_opa(toast_label, 0xcc, 0);
    lv_obj_set_style_radius(toast_label, 6, 0);
    lv_obj_set_style_pad_all(toast_label, 6);
    lv_obj_set_style_text_align(toast_label, 2, 0);
    lv_obj_align(toast_label, 9, 0, 0);
    lv_obj_add_flag(toast_label, 1);
    
    memset((void *)0x20008d78, 0, 0x15);
    
    screen = lv_screen_active();
    void *fire_btn = lv_button_create(screen);
    *(void **)0x20008d88 = fire_btn;
    lv_obj_set_size(fire_btn, 0x46, 0x20);
    lv_obj_align(fire_btn, 6, 0, -0x28);
    
    void *fire_label = lv_label_create(fire_btn);
    *(void **)0x20008d98 = fire_label;
    uint8_t *fired_shots_ptr = (uint8_t *)0x2000dc6d;
    char fire_buf[25];
    snprintf(fire_buf, 25, "FIRE x%d", 0x10 - *fired_shots_ptr);
    lv_label_set_text(fire_label, fire_buf);
    lv_obj_center(fire_label);
    lv_obj_add_event_cb(fire_btn, (void *)0xdf5, 0xa, 0);
    lv_obj_add_state(fire_btn, 0x80);
    
    lv_obj_add_state(fire_btn, 0x80);
    
    uint32_t btn_color = lv_color_hex(0x303030);
    lv_obj_set_style_bg_color(fire_btn, btn_color, 0);
    
    void *x_field = lv_textarea_create(screen);
    *(void **)0x20008d94 = x_field;
    lv_textarea_set_placeholder_text(x_field, "X");
    lv_textarea_set_one_line(x_field, 1);
    lv_textarea_set_max_length(x_field, 2);
    lv_textarea_set_accepted_chars(x_field, "0123456789");
    lv_obj_remove_flag(x_field, 0x10);
    lv_obj_set_size(x_field, 0x3c, 0x20);
    lv_obj_align(x_field, 4, 0xa, -0x28);
    lv_obj_add_event_cb(x_field, (void *)0x9dd, 0x13, 0);
    lv_obj_add_event_cb(x_field, (void *)0x9dd, 0xa, 0);
    
    void *y_field = lv_textarea_create(screen);
    *(void **)0x20008d90 = y_field;
    lv_textarea_set_placeholder_text(y_field, "Y");
    lv_textarea_set_one_line(y_field, 1);
    lv_textarea_set_max_length(y_field, 2);
    lv_textarea_set_accepted_chars(y_field, "0123456789");
    lv_obj_remove_flag(y_field, 0x10);
    lv_obj_set_size(y_field, 0x3c, 0x20);
    lv_obj_align(y_field, 5, 0, -0x28);
    lv_obj_add_event_cb(y_field, (void *)0x9dd, 0x13, 0);
    lv_obj_add_event_cb(y_field, (void *)0x9dd, 0xa, 0);
    
    void *hex_kb = hex_keyboard_create(screen, 0);
    *(void **)0x20008d8c = hex_kb;
    lv_obj_set_size(hex_kb, 0xf0, 0x96);
    lv_obj_align(hex_kb, 6, 0, -0x4a);
    lv_obj_add_flag(hex_kb, 1);
    
    update_fire_button_state();
    lv_timer_create((void *)0x9a5, 1, 0);
    
    indev_init();
    
    printk("\n*** Welcome to Sploosh-Kaboom (0x%08x) ***\nPlacing fleet\n", *game_id_ptr);
    
    toast_show("Placing fleet", 0x3e8, lv_color_hex(0xffac1c));
    
    memset((void *)0x2000dc6e, 0, 0x240);
    
    uint8_t *ship_lengths = (uint8_t *)0x613e0;
    uint8_t ship_idx = 0;
    
    for (int i = 0; i < MAX_SHIPS; i++) {
        uint8_t ship_len = ship_lengths[i] + 1;
        ship_idx++;
        printk("[I] Placing ship %d (size=%d)\n", ship_idx, ship_len);
        
        uint8_t max_coord = GRID_SIZE - ship_len;
        bool placed = false;
        
        while (!placed) {
            uint8_t horizontal = rand8(2);
            uint8_t x = rand8(GRID_SIZE);
            uint8_t y = rand8(GRID_SIZE);
            
            if (horizontal) {
                if (x + ship_len < GRID_SIZE + 1) {
                    if (!collision(x, y, ship_len, 1)) {
                        mark(x, y, ship_len, 1, 1);
                        placed = true;
                    }
                }
            } else {
                if (y + ship_len < GRID_SIZE + 1) {
                    if (!collision(x, y, ship_len, 0)) {
                        mark(x, y, ship_len, 0, 1);
                        placed = true;
                    }
                }
            }
        }
    }
    
    place_ghost();
    
    printk("\nRemaining shots: %d\n", MAX_SHOTS - *fired_shots_ptr);
    
    while (1) {
        lv_timer_handler();
        fire_btn_update();
        k_sleep(0x148, 0);
    }
    
    return 0;
}
