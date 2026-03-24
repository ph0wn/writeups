/* Bit flags for each board cell */
#define FLAG_SHIP   0x01
#define FLAG_GHOST  0x02
#define FLAG_PROBED 0x04
#define FLAG_HIT    0x08

#define MAX_SHOTS   16
#define GH_ESCAPE   4

/* CELL definition*/
#define NB_CELLS 24
#define CELLS_PX 8
#define MAX_CELLS   (NB_CELLS * NB_CELLS)

/* COLOR for FIRE button */
#define FIRE_COLOR_DISABLED 0x303030
#define FIRE_COLOR_READY    0xFF0000

#define INFO    0x4ef542
#define ERROR   0xFFAC1C
#define OPTION  0xFFAC1C
#define WARNING 0xD12300

#define COL_SPLOOSH 0xFFAC1C
#define COL_KABOOM 0xff2200
#define COL_INFO 0xA80088

/* forward declarations */
static void update_fire_button_state(void);
static void field_focus_event_cb(lv_event_t *e);
static void handle_shot(uint8_t row, uint8_t col, const struct shell *sh);
static void handle_shot_ui(uint8_t row, uint8_t col);