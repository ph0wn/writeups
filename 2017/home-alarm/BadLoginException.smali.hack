.class public Lph0wn/ctf/alarm/b;
.super Ljava/lang/Exception;


# direct methods
.method public constructor <init>(Ljava/lang/String;)V
    .registers 2
    .param p1, "msg"    # Ljava/lang/String;

    .prologue
    .line 5
    invoke-direct {p0, p1}, Ljava/lang/Exception;-><init>(Ljava/lang/String;)V

    .line 6
    return-void
.end method


.method sherlock(I)I
    .registers 3
    .param p1, "v"    # I

    .prologue
    .line 26
    packed-switch p1, :pswitch_data_a

    .line 34
    const/4 v0, 0x3

    .line 37
    .local v0, "hints":I
    :goto_4
    return v0

    .line 28
    .end local v0    # "hints":I
    :pswitch_5
    const/4 v0, 0x1

    .line 29
    .restart local v0    # "hints":I
    goto :goto_4

    # Hack where we insert code of passXor
    # we replace cond_15 by goto_4 (return v0)
    const/16 v2, 0x10

    new-array v1, v2, [B

    fill-array-data v1, :array_16

    .local v1, "s":[B
    const/4 v0, 0x0

    .local v0, "i":I
    :goto_8
    array-length v2, v1

    if-ge v0, v2, :goto_4

    aget-byte v2, v1, v0

    xor-int/lit8 v2, v2, 0x21

    int-to-byte v2, v2

    aput-byte v2, v1, v0

    add-int/lit8 v0, v0, 0x1

    goto :goto_8
    
    :array_16
    .array-data 1
        0x6ft
        0x4et
        0x63t
        0x54t
        0x53t
        0x46t
        0x4dt
        0x40t
        0x53t
        0x52t
        0x60t
        0x55t
        0x69t
        0x4et
        0x4ct
        0x44t
    .end array-data

    # End of insert

    .line 31
    .end local v0    # "hints":I
    :pswitch_7
    const/4 v0, 0x2

    .line 32
    .restart local v0    # "hints":I
    goto :goto_4

    .line 26
    nop

    :pswitch_data_a
    .packed-switch 0x0
        :pswitch_5
        :pswitch_7
    .end packed-switch
.end method
