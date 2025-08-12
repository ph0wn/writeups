#include "mbed.h"

//------------------------------------
// Hyperterminal configuration
// 9600 bauds, 8-bit data, no parity
//------------------------------------


Serial uart1(PA_9, PB_7);
//Serial uart2(PA_2, PA_3);
Serial uart3(PB_10, PB_11);
Serial uart4(PC_10, PC_11);
Serial uart5(PC_12, PD_2);


//const char flag[] = "ph0wn{should_i_dump_or_should_i_go?}"; 
const char flag[] = "ph0wn{signals_calling_to_the_faraway_towns}"; 
 
DigitalOut myled(LED1);
 
int main() {
  uart1.baud(9600);
  //uart2.baud(19200);
  uart3.baud(38400);
  uart4.baud(57600);
  uart5.baud(115200);
  
  void *ports[5] = {uart1, uart3, uart4, uart5};
  int i;
  Serial *ser;
  while(1) { 
      srand(0);
      for (i = 0; i < strlen(flag) ; i++){
        //pc.printf("%c", flag[i]);
        
        putc( flag[i], (FILE  *) ports[rand()%4]);
        wait(0.001);
      }
     myled = !myled;
     wait(1);

  }
}
