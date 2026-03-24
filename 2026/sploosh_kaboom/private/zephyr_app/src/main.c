#include <zephyr/kernel.h>
#include <zephyr/device.h>
#include <zephyr/drivers/display.h>
#include <zephyr/drivers/gpio.h>
#include <zephyr/drivers/uart.h>
#include <zephyr/input/input.h>
#include <zephyr/usb/usb_device.h>
#include <zephyr/random/random.h>
#include <zephyr/shell/shell.h>
#include <zephyr/sys/printk.h>
#include <zephyr/sys/reboot.h>

#include <lvgl.h>

#include <string.h>
#include <stdio.h>
#include <stdlib.h>
#include <ctype.h>

#include "logo.h"
#include "flags.h"
#include "config.h"

static void lvgl_tick_cb(struct k_timer *t){ lv_tick_inc(1); }
K_TIMER_DEFINE(lvgl_tick_timer, lvgl_tick_cb, NULL);

/* ---------- Grid config ---------- */
typedef struct {
    uint16_t step_x;          // pixels between vertical lines
    uint16_t step_y;          // pixels between horizontal lines
    uint8_t  width_px;        // line thickness
    lv_color_t color;         // grid color
    lv_opa_t  opa;            // opacity
    bool      draw_outer_box; // draw an outer frame
} grid_cfg_t;

static lv_obj_t *lv_board;
static lv_obj_t *toast_label;
static lv_obj_t *fire_lbl;
static lv_obj_t *x_field;
static lv_obj_t *y_field;
static lv_obj_t *hex_kb;
static lv_obj_t *fire_btn;
static lv_obj_t *reset_btn;

static lv_indev_t * indev_kp;

static lv_timer_t *toast_tmr;

static const char * hex_kb_map[] = {
    "1", "2", "3", LV_SYMBOL_CLOSE,      "\n",
    "4", "5", "6", LV_SYMBOL_BACKSPACE,  "\n",
    "7", "8", "9", LV_SYMBOL_OK,         "\n",
    "0", NULL
};

static const lv_buttonmatrix_ctrl_t hex_kb_ctrl[] = {
    LV_BUTTONMATRIX_CTRL_NONE, LV_BUTTONMATRIX_CTRL_NONE, LV_BUTTONMATRIX_CTRL_NONE, LV_BUTTONMATRIX_CTRL_CLICK_TRIG,
    LV_BUTTONMATRIX_CTRL_NONE, LV_BUTTONMATRIX_CTRL_NONE, LV_BUTTONMATRIX_CTRL_NONE, LV_BUTTONMATRIX_CTRL_CLICK_TRIG,
    LV_BUTTONMATRIX_CTRL_NONE, LV_BUTTONMATRIX_CTRL_NONE, LV_BUTTONMATRIX_CTRL_NONE, LV_BUTTONMATRIX_CTRL_CLICK_TRIG,
    LV_BUTTONMATRIX_CTRL_NONE,
};

static uint8_t  solved = 0;
static uint8_t  board[MAX_CELLS];
static uint32_t prng_state;
static uint32_t game_id;

static const uint8_t ship_lengths[] = {3, 3, 2, 2, 1};
#define SHIP_COUNT (sizeof(ship_lengths) / sizeof(ship_lengths[0]))

static uint8_t fired_shots = 0;

static const uint32_t prng_ghost[4] = {0x464F4C4C, 0x4F575F54, 0x48455F47, 0x484F5354};
static uint8_t escape_count = 1;

static uint8_t outoftime = 0;

/* k_timer callbacks run in ISR context. Use printk (IRQ-safe) here. */
static void tim(struct k_timer *timer_id)
{
    ARG_UNUSED(timer_id);
    outoftime = 1;
    printk("\n*** Timer elapsed ! The ghost ship escaped ! ***\n");
}

/* Define a one-shot kernel timer */
K_TIMER_DEFINE(thd_tim, tim, NULL);

/* SPLASH  */

static void show_splash(void)
{
    lv_obj_t *scr = lv_scr_act();
    lv_obj_t *img = lv_img_create(scr);
    lv_img_set_src(img, &logo);
    lv_obj_center(img);
    lv_obj_set_style_opa(img, LV_OPA_COVER, 0);
    lv_timer_handler();
    k_sleep(K_MSEC(1000));
    lv_obj_del(img);
}

/* END SPLASH */

/* INPUT */

void indev_init(void)
{
    lv_group_t * g = lv_group_create();
    lv_group_add_obj(g, x_field);
    lv_group_add_obj(g, y_field);
    lv_group_add_obj(g, fire_btn);
    
    indev_kp = lv_indev_create();
    lv_indev_set_type(indev_kp, LV_INDEV_TYPE_KEYPAD);
    lv_indev_set_group(indev_kp, g);
}

static void kb_event_cb(lv_event_t * e)
{
    if(lv_event_get_code(e) != LV_EVENT_VALUE_CHANGED) return;
    
    lv_obj_t * kb = lv_event_get_target(e);
    
    uint32_t btn_id = lv_buttonmatrix_get_selected_button(kb);
    #ifdef LV_BUTTONMATRIX_BUTTON_NONE
    if(btn_id == LV_BUTTONMATRIX_BUTTON_NONE) return;
    #else
    if(btn_id == 0xFFFFu) return;
    #endif
    
    const char * key = lv_buttonmatrix_get_button_text(kb, btn_id);
    if(!key) return;
    
    lv_obj_t * ta = lv_keyboard_get_textarea(kb);
    if(!ta) return;
    
    if(strcmp(key, LV_SYMBOL_BACKSPACE) == 0) {
        lv_textarea_delete_char(ta);
    }
    else if(strcmp(key, LV_SYMBOL_OK) == 0 || strcmp(key, LV_SYMBOL_CLOSE) == 0) {
        lv_obj_add_flag(kb, LV_OBJ_FLAG_HIDDEN);
    }
    else if(key[0] && key[1] == '\0') {
        const char * txt = lv_textarea_get_text(ta);
        if(txt && strlen(txt) < 2) {
            lv_textarea_add_char(ta, key[0]);
            lv_event_stop_processing(e);
        }
        
    }
    update_fire_button_state();
}

lv_obj_t * hex_keyboard_create(lv_obj_t * parent, lv_obj_t * ta)
{
    lv_obj_t * kb = lv_keyboard_create(parent);
    
    lv_keyboard_set_mode(kb, LV_KEYBOARD_MODE_USER_1);
    lv_keyboard_set_map(kb, LV_KEYBOARD_MODE_USER_1, hex_kb_map, hex_kb_ctrl);
    lv_keyboard_set_popovers(kb, false);
    
    /* Important: hook your handler (and stop default processing inside it) */
    lv_obj_add_event_cb(kb, kb_event_cb, LV_EVENT_VALUE_CHANGED | LV_EVENT_PREPROCESS, NULL);
    
    if(ta) {
        lv_keyboard_set_textarea(kb, ta);
    }
    
    return kb;
}

/* END INPUT */

/* UI */

static void reset_btn_event_cb(lv_event_t * e)
{
    if(lv_event_get_code(e) != LV_EVENT_CLICKED) return;
    sys_reboot(SYS_REBOOT_COLD);
}

static void grid_draw_cb(lv_event_t * e)
{
    const grid_cfg_t *cfg = lv_event_get_user_data(e);
    if(!cfg) return;
    if(lv_event_get_code(e) != LV_EVENT_DRAW_MAIN) return;
    
    lv_layer_t * layer = lv_event_get_layer(e);
    if(!layer) return;
    
    lv_obj_t * obj = lv_event_get_target(e);
    lv_area_t a;
    lv_obj_get_content_coords(obj, &a);
    
    lv_coord_t W = lv_area_get_width(&a);
    lv_coord_t H = lv_area_get_height(&a);
    const lv_coord_t grid_w = (lv_coord_t)(NB_CELLS * cfg->step_x);
    const lv_coord_t grid_h = (lv_coord_t)(NB_CELLS * cfg->step_y);
    const lv_coord_t x0 = a.x1 + (W - grid_w) / 2;
    const lv_coord_t y0 = a.y1 + (H - grid_h) / 2;
    const lv_coord_t x1 = x0 + grid_w;
    const lv_coord_t y1 = y0 + grid_h;
    
    lv_draw_line_dsc_t dsc;
    lv_draw_line_dsc_init(&dsc);
    dsc.color = cfg->color;
    dsc.opa   = cfg->opa;
    dsc.width = cfg->width_px;
    
    // Vertical lines
    for (int i = 0; i < NB_CELLS; i++) {
        const lv_coord_t x = x0 + i * cfg->step_x;
        dsc.p1.x = x; dsc.p1.y = y0;
        dsc.p2.x = x; dsc.p2.y = y1;
        lv_draw_line(layer, &dsc);
    }
    
    // horizontal lines
    for (int i = 0; i < NB_CELLS; i++) {
        const lv_coord_t y = y0 + i * cfg->step_y;
        dsc.p1.x = x0; dsc.p1.y = y;
        dsc.p2.x = x1; dsc.p2.y = y;
        lv_draw_line(layer, &dsc);
    }
    
    // Optional outer box (slightly stronger)
    if(cfg->draw_outer_box) {
        lv_draw_rect_dsc_t r;
        lv_draw_rect_dsc_init(&r);
        r.bg_opa = LV_OPA_TRANSP;
        r.border_width = cfg->width_px;
        r.border_opa   = cfg->opa;
        r.border_color = cfg->color;
        
        lv_area_t box = {.x1 = x0, .y1 = y0, .x2 = x1, .y2 = y1};
        lv_draw_rect(layer, &r, &box);
    }
    
    for (int y=0; y<NB_CELLS; y++) {
        for (int x=0; x<NB_CELLS; x++) {
            
            uint8_t v = board[x * NB_CELLS + y];
            
            if(v == 0) continue;  // unknown → draw nothing
            
            lv_draw_rect_dsc_t rd;
            lv_draw_rect_dsc_init(&rd);
            rd.bg_opa = LV_OPA_COVER;
            
            // compute cell pixel region
            lv_area_t ca = {
                .x1 = x0 + x * cfg->step_x + 1,
                .y1 = y0 + y * cfg->step_y + 1,
                .x2 = x0 + (x+1)*cfg->step_x - 1,
                .y2 = y0 + (y+1)*cfg->step_y - 1,
            };
            
            // color selection
            if(v & FLAG_HIT) {
                rd.bg_color = lv_color_hex(0xD12300);
                lv_draw_rect(layer, &rd, &ca);
            } else if(v & FLAG_PROBED){
                rd.bg_color = lv_color_hex(0xFFAC1C);
                lv_draw_rect(layer, &rd, &ca);
            }
            // DEBUG:
            // else if(v & FLAG_SHIP){
            //     rd.bg_color = lv_color_hex(0x00FF00);
            //     lv_draw_rect(layer, &rd, &ca);
            // }
            // else if(v & FLAG_GHOST){
            //     rd.bg_color = lv_color_hex(0xFF00FF);
            //     lv_draw_rect(layer, &rd, &ca);
            // }
        }
    }
}

static void title_init(void){
    lv_obj_t * scr = lv_screen_active();
    lv_obj_set_style_bg_color(scr, lv_color_hex(0x482ff), 0);
    
    lv_obj_t *lbl_title = lv_label_create(scr);
    lv_label_set_recolor(lbl_title, true);      
    lv_label_set_text(lbl_title, "#FFAC1C SPLOOSH# #D12300 KABOOM# #A80088 2077#");
    lv_obj_align(lbl_title, LV_ALIGN_TOP_MID, 0, 0);
    
    lv_obj_t *lbl_gameid = lv_label_create(scr);
    lv_label_set_recolor(lbl_gameid, true); 
    char buf_gameid[30];
    snprintf(buf_gameid, sizeof(buf_gameid), "#0000AA GAME ID - %08x #", game_id);
    lv_label_set_text(lbl_gameid, buf_gameid);
    lv_obj_align(lbl_gameid, LV_ALIGN_TOP_MID, 0, 14);
    
    lv_obj_t *lbl_xaxis = lv_label_create(scr);
    char buf_xaxis[(NB_CELLS * 2) + 4];
    snprintf(buf_xaxis, sizeof(buf_xaxis),
    "0%*s%d",
    NB_CELLS * 2, "", NB_CELLS-1);
    lv_label_set_text(lbl_xaxis, buf_xaxis);
    lv_obj_align(lbl_xaxis, LV_ALIGN_TOP_LEFT, 10, 30);
    
    char buf_yaxis[4];
    snprintf(buf_yaxis, sizeof(buf_yaxis), "%d", NB_CELLS-1);
    lv_obj_t *lbl_yaxis = lv_label_create(scr);
    lv_label_set_text(lbl_yaxis, buf_yaxis);
    lv_obj_align(lbl_yaxis, LV_ALIGN_TOP_LEFT, 5, 230);
    
    /* --- Reset button --- */
    reset_btn = lv_btn_create(scr);
    lv_obj_set_size(reset_btn, 90, 20);
    
    lv_obj_align(reset_btn, LV_ALIGN_BOTTOM_MID, 0, -10);
    lv_obj_set_style_bg_color(reset_btn, lv_color_hex(0xff2200), 0);
    lv_obj_add_event_cb(reset_btn, reset_btn_event_cb, LV_EVENT_CLICKED, NULL);
    lv_obj_t * rst_lbl = lv_label_create(reset_btn);
    lv_label_set_text(rst_lbl, "RESET");
    lv_obj_center(rst_lbl);
    
    lv_obj_invalidate(scr);
}

static void board_init(void)
{
    lv_obj_t * scr = lv_screen_active();
    
    lv_board = lv_obj_create(scr);
    lv_obj_set_size(lv_board, 240, 290);
    lv_obj_align(lv_board, LV_ALIGN_TOP_MID, 0, 0);
    lv_obj_set_style_bg_color(lv_board, lv_color_hex(0x482ff), 0);
    lv_obj_set_style_bg_opa(lv_board, LV_OPA_COVER, 0);
    lv_obj_set_style_border_width(lv_board, 0, 0);
    lv_obj_set_style_radius(lv_board, 0, 0);
    lv_obj_set_style_pad_all(lv_board, 0, 0);
    lv_obj_clear_flag(lv_board, LV_OBJ_FLAG_SCROLLABLE);
    lv_obj_add_flag(lv_board, LV_OBJ_FLAG_CLICKABLE);
    
    static const grid_cfg_t cfg = {
        .step_x        = CELLS_PX,
        .step_y        = CELLS_PX,
        .width_px      = 1,
        .opa           = LV_OPA_100,
        .draw_outer_box= true,
    };
    
    lv_obj_add_event_cb(lv_board, grid_draw_cb, LV_EVENT_DRAW_MAIN, (void*)&cfg);
    lv_obj_invalidate(scr);
}

static void toast_init(void)
{
    lv_obj_t *scr = lv_scr_act();
    
    toast_label = lv_label_create(scr);
    lv_label_set_text(toast_label, "");
    lv_label_set_recolor(toast_label, true);
    lv_label_set_long_mode(toast_label, LV_LABEL_LONG_WRAP);
    
    lv_obj_set_style_bg_color(toast_label, lv_color_hex(0xA80088), 0); // purple
    lv_obj_set_style_bg_opa(toast_label, LV_OPA_80, 0);
    lv_obj_set_style_radius(toast_label, 6, 0);
    lv_obj_set_style_pad_all(toast_label, 6, 0);
    
    lv_obj_set_style_text_align(toast_label, LV_TEXT_ALIGN_CENTER, 0);
    lv_obj_align(toast_label, LV_ALIGN_CENTER, 0, 0);
    lv_obj_add_flag(toast_label, LV_OBJ_FLAG_HIDDEN);
    
    toast_tmr = NULL;
}

static void toast_timer_cb(lv_timer_t *t)
{
    lv_obj_add_flag(toast_label, LV_OBJ_FLAG_HIDDEN);
}

static void toast_show(const char * text, uint32_t ms, lv_color_t bg_color)
{
    if(toast_label) {
        lv_obj_del(toast_label);
        toast_label = NULL;
    }
    
    lv_obj_t * scr = lv_screen_active();
    toast_label = lv_label_create(scr);
    lv_label_set_text(toast_label, text);
    
    lv_obj_set_style_bg_opa(toast_label, LV_OPA_80, 0);
    lv_obj_set_style_bg_color(toast_label, bg_color, 0);
    lv_obj_set_style_text_color(toast_label, lv_color_white(), 0);
    lv_obj_set_style_pad_all(toast_label, 8, 0);
    lv_obj_set_style_radius(toast_label, 6, 0);
    
    lv_obj_align(toast_label, LV_ALIGN_CENTER, 0, 0);
    
    toast_tmr = lv_timer_create(toast_timer_cb, ms, NULL);
    lv_timer_set_repeat_count(toast_tmr, 1);
}

static void fire_btn_update(){
    char buf[25] = {0};
    snprintf(buf, sizeof(buf), "FIRE x%d", MAX_SHOTS - fired_shots);
    lv_label_set_text(fire_lbl, buf);
}

static void fire_btn_event(lv_event_t * e)
{
    const char *xt = lv_textarea_get_text(x_field);
    const char *yt = lv_textarea_get_text(y_field);
    
    lv_event_code_t code = lv_event_get_code(e);
    if(code != LV_EVENT_CLICKED) return;
    
    if ((xt[0] == '\0') || (yt[0] == '\0')) return;
    
    uint8_t vx = (uint8_t)strtol(xt, NULL, 10);
    uint8_t vy = (uint8_t)strtol(yt, NULL, 10);
    
    handle_shot_ui(vx,vy);
    
    lv_textarea_set_text(x_field, "");
    lv_textarea_set_text(y_field, "");
    
    lv_obj_add_flag(hex_kb, LV_OBJ_FLAG_HIDDEN);
    
    fire_btn_update();
    
    update_fire_button_state();
    lv_obj_invalidate(lv_board);
}

static void fire_btn_init(void)
{
    char buf[25] = {0};
    lv_obj_t *scr = lv_scr_act();
    
    fire_btn = lv_button_create(scr);
    lv_obj_set_size(fire_btn, 70, 32);
    lv_obj_align(fire_btn, LV_ALIGN_BOTTOM_RIGHT, 0, -40);
    
    fire_lbl = lv_label_create(fire_btn);
    snprintf(buf, sizeof(buf), "FIRE x%d", MAX_SHOTS - fired_shots);
    lv_label_set_text(fire_lbl, buf);
    lv_obj_center(fire_lbl);
    
    lv_obj_add_event_cb(fire_btn, fire_btn_event, LV_EVENT_CLICKED, NULL);
    lv_obj_add_state(fire_btn, LV_STATE_DISABLED);
}

static void update_fire_button_state(void)
{
    const char *xt = lv_textarea_get_text(x_field);
    const char *yt = lv_textarea_get_text(y_field);
    
    if ((xt[0] != '\0') && (yt[0] != '\0')) {
        lv_obj_clear_state(fire_btn, LV_STATE_DISABLED);
        lv_obj_set_style_bg_color(fire_btn, lv_color_hex(FIRE_COLOR_READY), 0);
    } else {
        lv_obj_add_state(fire_btn, LV_STATE_DISABLED);
        lv_obj_set_style_bg_color(fire_btn, lv_color_hex(FIRE_COLOR_DISABLED), 0);
    }
}

static void field_focus_event_cb(lv_event_t * e)
{
    lv_obj_t * ta = lv_event_get_target(e);
    lv_event_code_t code = lv_event_get_code(e);
    
    if(code == LV_EVENT_FOCUSED || code == LV_EVENT_CLICKED) {
        lv_keyboard_set_textarea(hex_kb, ta);
        lv_obj_clear_flag(hex_kb, LV_OBJ_FLAG_HIDDEN);
        lv_obj_move_foreground(hex_kb); 
    }
    else if(code == LV_EVENT_DEFOCUSED) {
        lv_obj_add_flag(hex_kb, LV_OBJ_FLAG_HIDDEN);
        
    }
}

static void clear_initial_focus_cb(lv_timer_t * t)
{
    LV_UNUSED(t);
    lv_obj_clear_state(x_field, LV_STATE_FOCUSED);
    lv_obj_clear_state(y_field, LV_STATE_FOCUSED);
    lv_obj_add_flag(hex_kb, LV_OBJ_FLAG_HIDDEN);
    lv_timer_del(t);
}

static void xy_inputs_init(void)
{
    lv_obj_t *scr = lv_screen_active();
    static const char *digits = "0123456789";
    
    lv_obj_add_state(fire_btn, LV_STATE_DISABLED);
    lv_obj_set_style_bg_color(fire_btn, lv_color_hex(FIRE_COLOR_DISABLED), 0);
    
    /* --- X field --- */
    x_field = lv_textarea_create(scr);
    lv_textarea_set_placeholder_text(x_field, "X");
    lv_textarea_set_one_line(x_field, true);
    lv_textarea_set_max_length(x_field, 2);
    lv_textarea_set_accepted_chars(x_field, digits);
    lv_obj_clear_flag(x_field, LV_OBJ_FLAG_SCROLLABLE);
    lv_obj_set_size(x_field, 60, 32);
    lv_obj_align(x_field, LV_ALIGN_BOTTOM_LEFT, 10, -40);
    
    lv_obj_add_event_cb(x_field, field_focus_event_cb, LV_EVENT_FOCUSED, NULL);
    lv_obj_add_event_cb(x_field, field_focus_event_cb, LV_EVENT_CLICKED, NULL);
    
    /* --- Y field --- */
    y_field = lv_textarea_create(scr);
    lv_textarea_set_placeholder_text(y_field, "Y");
    lv_textarea_set_one_line(y_field, true);
    lv_textarea_set_max_length(y_field, 2);
    lv_textarea_set_accepted_chars(y_field, digits);
    lv_obj_clear_flag(y_field, LV_OBJ_FLAG_SCROLLABLE);
    lv_obj_set_size(y_field, 60, 32);
    lv_obj_align(y_field, LV_ALIGN_BOTTOM_MID, 0, -40);
    
    lv_obj_add_event_cb(y_field, field_focus_event_cb, LV_EVENT_FOCUSED, NULL);
    lv_obj_add_event_cb(y_field, field_focus_event_cb, LV_EVENT_CLICKED, NULL);    
    
    /* --- Create your hex keyboard (hidden initially) --- */
    hex_kb = hex_keyboard_create(scr, NULL);
    lv_obj_set_size(hex_kb, 240, 150);
    lv_obj_align(hex_kb, LV_ALIGN_BOTTOM_RIGHT, 0, -75);
    lv_obj_add_flag(hex_kb, LV_OBJ_FLAG_HIDDEN);
    
    update_fire_button_state();
    lv_timer_create(clear_initial_focus_cb, 1, NULL);
}

/* END UI */

/* GAME  */

static inline uint32_t rand8(uint8_t max)
{
    prng_state ^= (prng_state << 13) & 0xffffffff;
    prng_state ^= (prng_state >> 17) & 0xffffffff;
    prng_state ^= (prng_state << 5)  & 0xffffffff;
    return prng_state % max;
}

static bool collision(uint8_t r, uint8_t c, uint8_t len, bool horiz)
{
    for (uint8_t i = 0; i < len; ++i) {
        uint8_t rr = r + (horiz ? 0 : i);
        uint8_t cc = c + (horiz ? i : 0);
        if (board[(cc * NB_CELLS) + rr] & (FLAG_SHIP | FLAG_GHOST)) {
            return true;
        }
    }
    return false;
}

static void mark(uint8_t r, uint8_t c, uint8_t len, bool horiz, uint8_t flag)
{
    for (uint8_t i = 0; i < len; ++i) {
        uint8_t rr = r + (horiz ? 0 : i);
        uint8_t cc = c + (horiz ? i : 0);
        board[cc * NB_CELLS + rr] |= flag;  // [col][row]
    }
}

static void place_ghost(void){
    prng_state ^= prng_ghost[escape_count-1];
    while (true) {
        //printf("\nPRNG = 0x%08x", prng_state);
        bool horiz = rand8(2);
        uint8_t r  = rand8(NB_CELLS/2);
        uint8_t c  = rand8(NB_CELLS/4);
        if (!collision(r, c, 1, horiz)) {
            //printf("\nGHOST SHIP h=%d r=%d c=%d", (uint8_t) horiz, r, c);
            mark(r, c, 1, horiz, FLAG_GHOST);
            break;
        }
    }
}

static void place_fleet(void)
{
    memset(board, 0, sizeof(board));
    for (size_t s = 0; s < SHIP_COUNT; ++s) {
        uint8_t len = ship_lengths[s];
        printk("[I] Placing ship %d (size=%d)\n", s+1, len);
        while (true) {
            bool horiz = rand8(2);
            uint8_t r  = rand8(NB_CELLS);
            uint8_t c  = rand8(NB_CELLS);
            if (horiz && (c + len > NB_CELLS)){
                c = NB_CELLS - len;
            }
            if (!horiz && (r + len > NB_CELLS)){
                r = NB_CELLS - len;
            }
            if (!collision(r, c, len, horiz)) {
                //printf("\nPRNG = 0x%08x", prng_state);
                //printf("\nSHIP=%d h=%d r=%d c=%d", s, (uint8_t) horiz, r, c);
                mark(r, c, len, horiz, FLAG_SHIP);
                break;
            }
        }
    }
}

/* END GAME  */

/* HELPERS */

static int remaining_ships(void)
{
    int count = 0;
    for (int i = 0; i < MAX_CELLS; ++i){
        if (board[i] & FLAG_SHIP && !(board[i] & FLAG_HIT)){
            ++count;
        }
    }
    return count;
}

static void handle_shot(uint8_t col, uint8_t row, const struct shell *sh)
{
    if ((col > NB_CELLS-1) || (row > NB_CELLS-1)) {
        shell_print(sh, "Invalid coordinates\n");
        return;
    }
    uint8_t *cell = &board[col * NB_CELLS + row]; 
    if (fired_shots < MAX_SHOTS) {           
        if (*cell & FLAG_PROBED) {
            shell_print(sh, "Already tried %d, %d!", col, row);
            return;
        }
        *cell |= FLAG_PROBED;
        if (*cell & FLAG_SHIP) {
            *cell |= FLAG_HIT;
            shell_print(sh, "KABOOM !\n Remaining ships: %d\n",remaining_ships());
            if (remaining_ships() == 0) {
                solved = 1;
                shell_print(sh, "Congratz\nAll ships sank!!");
                shell_print(sh, "%s\n", FLAG_1);
            }
        } else if (*cell & FLAG_GHOST) {
            *cell |= FLAG_HIT;
            if (escape_count == GH_ESCAPE && outoftime == 0) {
                solved = 2;
                k_timer_stop(&thd_tim);
                shell_print(sh, "Congratz\nGhost ship sank!!\n");
                shell_print(sh, "%s\n", FLAG_2);
            }
            if (escape_count < GH_ESCAPE) {
                if (outoftime == 1) {
                    shell_print(sh, "Ghost ship escaped !!");
                }
                else {
                    if (escape_count == 1) {
                        shell_print(sh, "HURRY UP\n");
                        k_timer_start(&thd_tim, K_SECONDS(5), K_NO_WAIT);
                    }
                    shell_print(sh, "Ghost ship flees %d/%d\n", escape_count, GH_ESCAPE);
                    escape_count++;
                    place_ghost();
                }
            }
        } else {
            shell_print(sh, "...SPLOOSH...\n");
        }
        fired_shots++;
        shell_print(sh, "\nRemaining shots: %d\n", MAX_SHOTS - fired_shots);
    }
    else {
        shell_print(sh, "No more shots !!\nRESET THE BOARD\n");
    }
    lv_obj_invalidate(lv_board);
}

static void handle_shot_ui(uint8_t col, uint8_t row)
{
    if ((col > NB_CELLS-1) || (row > NB_CELLS-1)){
        toast_show("Invalide coordinates", 1500, lv_color_hex(ERROR));
        return;
    }
    uint8_t *cell = &board[col * NB_CELLS + row];
    if (fired_shots < MAX_SHOTS) {           
        if (*cell & FLAG_PROBED) {
            toast_show("Already probed !", 1500, lv_color_hex(ERROR));
            return;
        }
        *cell |= FLAG_PROBED;
        if (*cell & FLAG_SHIP) {
            *cell |= FLAG_HIT;
            toast_show("KABOOM!", 1500, lv_color_hex(COL_KABOOM));
            if (remaining_ships() == 0) {
                solved = 1;
                toast_show("Congratz\nAll ships sank!!", 3000, lv_color_hex(COL_KABOOM));
                toast_show(FLAG_1, 5000, lv_color_hex(COL_KABOOM));
            }
        } else if (*cell & FLAG_GHOST) {
            *cell |= FLAG_HIT;
            if (escape_count == GH_ESCAPE && outoftime == 0) {
                solved = 2;
                k_timer_stop(&thd_tim);
                toast_show("Congratz\nGhost ship sank!!", 3000, lv_color_hex(COL_KABOOM));
                toast_show(FLAG_2, 5000, lv_color_hex(COL_KABOOM));
                
            }
            if (escape_count < GH_ESCAPE) {
                if (outoftime == 1) {
                    toast_show("Ghost ship escaped !!", 1500, lv_color_hex(COL_INFO));
                }
                else {
                    if (escape_count == 1) {
                        toast_show("HURRY UP!", 1500, lv_color_hex(COL_INFO));
                        k_timer_start(&thd_tim, K_SECONDS(5), K_NO_WAIT);
                    }
                    toast_show("Ghost ship dodged!", 1500, lv_color_hex(COL_INFO));
                    escape_count++;
                    place_ghost();
                }
            }
        } else {
            toast_show("...SPLOOSH...", 1500, lv_color_hex(COL_SPLOOSH));
        }
        fired_shots++;
    }
    else {
        toast_show("No more shots !!\nreset the board\n\n", 5000, lv_color_hex(COL_INFO));
    }
}

/* END HELPERS */


/* SHELL  */

static bool is_decimal_string(const char *s) {
    if (*s == '\0') return false;
    for (const unsigned char *p = (const unsigned char *)s; *p; ++p) {
        if (!isdigit(*p)) return false;
    }
    return true;
}

static int cmd_fire(const struct shell *sh, size_t argc, char **argv)
{
    if (argc != 3) {
        shell_fprintf(sh, SHELL_INFO, "Usage: fire <x> <y> (0..%d)\n", (unsigned)(NB_CELLS - 1));
        return -EINVAL;
    }
    if (!is_decimal_string(argv[1]) || !is_decimal_string(argv[2])) {
        shell_print(sh, "Decimal only. Example: fire 12 34");
        return -EINVAL;
    }
    errno = 0;
    
    uint8_t x = (uint8_t) strtoul(argv[1], NULL, 10);
    uint8_t y = (uint8_t) strtoul(argv[2], NULL, 10);

    if (errno == ERANGE || x >= NB_CELLS || y >= NB_CELLS) {
        shell_fprintf(sh, SHELL_INFO, "Out of range. Valid: 0..%d\n", (unsigned)(NB_CELLS - 1));
        return -EINVAL;
    }
    handle_shot(x, y, sh);
    return 0;
}

static void shell_print_board(const struct shell *sh)
{
    shell_fprintf(sh, SHELL_NORMAL, "\nCurrent board:\n(MISS = 0, HIT = 1)\n");
    shell_fprintf(sh, SHELL_NORMAL, "    ");
    for (int r = 0; r < NB_CELLS; ++r){
        shell_fprintf(sh, SHELL_NORMAL, "%2d ", r);
    }
    shell_fprintf(sh, SHELL_NORMAL, "\n");
    for (int r = 0; r < NB_CELLS; ++r) {
        shell_fprintf(sh, SHELL_NORMAL, "%2d  ", r);
        for (int c = 0; c < NB_CELLS; ++c) {
            uint8_t cell = board[c * NB_CELLS + r];  // [col][row]
            char ch = '.';
            if (cell & FLAG_HIT) ch = '1';
            else if (cell & FLAG_PROBED) ch = '0';
            //DEBUG:
            //else if (cell & FLAG_SHIP)  ch = 'S';
            //else if (cell & FLAG_GHOST) ch = 'G';
            shell_fprintf(sh, SHELL_NORMAL, " %c ", ch);
        }
        shell_fprintf(sh, SHELL_NORMAL, "\n");
    }
}

static void shell_flags(const struct shell *sh, size_t argc, char **argv)
{
    if (argc != 2) {
        shell_print(sh, "Display flag. Usage: flag <1,2>\n");
    } else {
        if (solved == 0) {
            shell_print(sh, "No flag found !");
        } else {
            if (is_decimal_string(argv[1])){
                switch(atoi(argv[1])) {
                    case 1:
                    shell_fprintf(sh, SHELL_NORMAL, "\n%s\n", FLAG_1);
                    break;
                    case 2:
                    shell_fprintf(sh, SHELL_NORMAL, "\n%s\n", FLAG_2);
                    break;
                    default:
                    shell_print(sh, "Level must be 1 or 2");
                }
            } else {
                shell_print(sh, "Level must be 1 or 2");
            }
        }
    }    
    return;
}

static void shell_map(const struct shell *sh, size_t argc, char **argv)
{
    ARG_UNUSED(argc);
    ARG_UNUSED(argv);
    shell_print_board(sh);
    return;
}

static void shell_game_id(const struct shell *sh, size_t argc, char **argv)
{
    ARG_UNUSED(argc);
    ARG_UNUSED(argv);
    shell_fprintf(sh, SHELL_NORMAL, "0x%08x\n", game_id);
    return;
}

static void shell_shots(const struct shell *sh, size_t argc, char **argv)
{
    ARG_UNUSED(argc);
    ARG_UNUSED(argv);
    shell_fprintf(sh, SHELL_NORMAL, "%d\n", MAX_SHOTS - fired_shots);
    return;
}

static int shell_reboot(const struct shell *sh, size_t argc, char **argv)
{
    ARG_UNUSED(argc);
    ARG_UNUSED(argv);
    sys_reboot(SYS_REBOOT_COLD);
    return 0;
}

SHELL_CMD_REGISTER(id, NULL, "Display game_id", shell_game_id);
SHELL_CMD_REGISTER(shots, NULL, "Display remaining shots", shell_shots);
SHELL_CMD_REGISTER(map, NULL, "Display map", shell_map);
SHELL_CMD_REGISTER(reboot, NULL, "Reboot device", shell_reboot);
SHELL_CMD_ARG_REGISTER(flag, NULL, "Display flag (if any). Usage: flag <level>\n", shell_flags, 0, 0);
SHELL_CMD_ARG_REGISTER(fire, NULL, "Fire at coordinates. Usage: fire <x> <y>\n", cmd_fire, 0, 0);

/* END SHELL */

/* MAIN */

int main(void)
{
    /* Backlight init */
    const struct gpio_dt_spec backlight_led = GPIO_DT_SPEC_GET(DT_ALIAS(bl), gpios);
    if (!gpio_is_ready_dt(&backlight_led)) {
        printk("LED GPIO not ready.");
        return -1;
    }
    gpio_pin_configure_dt(&backlight_led, GPIO_OUTPUT_ACTIVE);
    gpio_pin_set_dt(&backlight_led, 1);
    
    /* USB CDC interface init */
    const struct device *cdc = DEVICE_DT_GET_ONE(zephyr_cdc_acm_uart);
    
    if (!device_is_ready(cdc)) {
        printk("CDC ACM device not ready\n");
        return -1;
    }
    
    /* Legacy stack: usb_enable() is the usual entry point */
    int ret = usb_enable(NULL);
    if (ret != 0) {
        printk("usb_enable failed: %d\n", ret);
        return -1;
    }
    
    game_id = sys_rand32_get();
    prng_state = game_id;
    
    /* Display init */
    const struct device *disp = DEVICE_DT_GET(DT_CHOSEN(zephyr_display));
    if (!device_is_ready(disp)) return -1;
    display_blanking_off(disp);
    
    lv_init();
    k_timer_start(&lvgl_tick_timer, K_MSEC(1), K_MSEC(1));
    if (!lv_disp_get_default()) return -1;
    
    show_splash();
    board_init();
    title_init();
    toast_init();
    fire_btn_init();
    xy_inputs_init();
    indev_init();
    
    printk("\n*** Welcome to Sploosh-Kaboom (0x%08x) ***\nPlacing fleet\n", game_id);
    
    toast_show("Placing fleet",1000, lv_color_hex(OPTION));
    
    place_fleet();
    place_ghost();
    
    printk("\nRemaining shots: %d\n", MAX_SHOTS - fired_shots);
    
    while (1) {
        lv_timer_handler();
        fire_btn_update();
        k_sleep(K_MSEC(10));
    }
    return 1;
}

/* END MAIN */