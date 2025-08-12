#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <stdint.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <errno.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <unistd.h>
#include <assert.h>

#define PORT 7777
#define MAXPENDING 10
#define TIMEOUT 30 /* seconds */

#if defined(DEBUG) && DEBUG > 0
 #define DEBUG_PRINT(fmt, args...) fprintf(stderr, "DEBUG: %s:%d:%s(): " fmt, \
    __FILE__, __LINE__, __func__, ##args)
#else
 #define DEBUG_PRINT(fmt, args...) /* Don't do anything in release builds */
#endif

struct therapy {
  char id[48+1];
};

struct fp {
  char *(*fp)(char *, int);
};

/*
  Helper function: transforms a hexstring to an int
  - input is expected to have 2 characters only
  e.g. "10" --> 0x10=16
*/
uint32_t hex2int(char *hex) {
    uint32_t val = 0;
    int i;
    
    assert(hex != NULL);
    assert(hex + 1 != NULL);
    
    for (i=0; i<2; i++) {
        // get current character then increment
        uint8_t byte = *hex++;
	
        // transform hex character to the 4bit equivalent number, using the ascii table indexes
        if (byte >= '0' && byte <= '9') byte = byte - '0';
        else if (byte >= 'a' && byte <='f') byte = byte - 'a' + 10;
        else if (byte >= 'A' && byte <='F') byte = byte - 'A' + 10;    
        val = (val << 4) | (byte & 0xF);
    }
    return val;
}

/*
  Generates a list of drugs with dosage to supply to a patient depending on a therapy_id
  A therapy_id is a hexstring + { and } characters.
  Each 2 characters of the therapy hexstring correspond to a given drug and the dosage is random
  Returns an allocated string which contains the list of drugs & dosage
*/
char *config_therapy(char *therapy_id, int len){
  const int LEN = 20;
  const unsigned int upper = 50;
  const unsigned int lower = 1;
  char *drug_list[20] = {
			"Aspirin",
			"Bromocriptine",
			"Codeine",
			"Desloratadine",
			"Estradiol",
			"Fluoxetine",
			"Glycerin",
			"Heparin",
			"Insulin",
			"Lidocaine",
			"Methadone",
			"Nasonex",
			"0mega",
			"Paracetamol",
			"Relafen",
			"Singulair",
			"Topamax",
			"Vicodin",
			"Wynzora",
			"+Solupred"
  };
  int i;
  int volume;
  int answer_id = 0;
  char begin_therapy[] ="----- INFUSION PUMP THERAPY CONFIG ----\n";
  char end_therapy[]   = "----------------------------------------\n";
  char *answer = malloc(1024 * sizeof(char));

  assert(answer != NULL);
  memset(answer, 0, 1024);

  DEBUG_PRINT("therapy_id=%s len=%d\n", therapy_id, len);

  /* header */
  sprintf(&answer[answer_id], "%s", begin_therapy);
  answer_id += strlen(begin_therapy);

  for (i =0 ; i<len; i+=2) {
    /* skip parenthesis */
    while (therapy_id[i] == '{' || therapy_id[i] == '}' ) {
      sprintf(&answer[answer_id], "%c\n", therapy_id[i]);
      i++;
      answer_id += 2;
    }

    /* get drug: "CC" -> int value -> position in drug_list table (modulo table length) */
    if (i < len) {
      int value = (int) hex2int(&therapy_id[i]);
      volume = (rand() % (upper - lower + 1)) + lower;
      sprintf(&answer[answer_id], "- %20s: %02d mL\n", drug_list[value % LEN], volume);
      answer_id += 20 /* drug name */ + 2 /* - and space */ + 8 /* volume=2 + mL=2 + : + 2 spaces + \n */;
    }
    
    /*DEBUG_PRINT("i=%d answer_id=%d answer=%s\n", i, answer_id, answer);*/
  }
  
  /* footer */
  sprintf(&answer[answer_id], "%s", end_therapy);
  answer_id += strlen(end_therapy);
  DEBUG_PRINT("Returning: %s\n", answer);
  return answer;
}

/* 
   this is the secret function CTF players should try and get into.
   this should not be possible without exploiting the program 
*/
char *secret(char *not_used, int reserved) {
  /* this therapy_id is very close to the flag and should be kept secret */
  char solution_therapy_id[] = "id{this_is_a_fake_therapy_id____absolutely_fake}";
  char tag[] = "This is the Secret Therapy ID: ";
  int answer_len = strlen(solution_therapy_id) + strlen(tag) + 1;
  char *answer = malloc( sizeof(char) * answer_len );

  assert(answer != NULL);
  
  /* concatenate */
  memset(answer, 0, answer_len);
  strcat(answer, tag);
  strcat(answer, solution_therapy_id);

  return answer;
}

/* 
   placeholder function called instead of secret 
   its arguments are not used
 */
char *disabled(char *not_used, int reserved) {
  char info [] = "'secret' is for authorized personel only :)\n";
  char *answer = malloc(sizeof(char)*(strlen(info) + 1));
  assert(answer != NULL);
   
  memcpy(answer, info, strlen(info));
  memset(answer + strlen(info), 0, 1);
  return answer;
}

/*
  displays usage for the PACS server
  there are 3 commands:
  1. therapy --> go to config_therapy
  2. secret --> should redirect to disabled, unless exploited
  3. help --> shows this usage
*/
char *help(char *not_used, int reserved) {
  char info [] = "--= Ph0wn Drug Server 1.1 =--\nThis server provides the adequate drug list and dosage for a given therapy.\nAvailable commands for Drug Server:\n\ttherapy xxxxxxxxxxxx : create therapy corresponding to therapy ID (48 bytes)\n\tsecret : for authorized personel only\n\thelp : this help\n----------------------\n";
  char *answer = malloc(sizeof(char) * (strlen(info) + 1));
  assert(answer != NULL);
   
  memcpy(answer, info, strlen(info));
  memset(answer + strlen(info), 0, 1);
  return answer;
}

/*
  this handler executes in a separate thread for each socket connection
  the socket descriptor contains the sockfd for communication
*/
void connection_handler(int sockfd) {
  int n = 0;
  char recv_buf[256];
  struct therapy *t = malloc(sizeof(struct therapy));
  struct fp *f = malloc(sizeof(struct fp));

  assert(t != NULL);
  assert(f != NULL);
  assert(sockfd != 0);

  while ((n=read(sockfd, recv_buf, sizeof(recv_buf)-1) ) > 0) {
    char *token = NULL;
    char send_buf[256];
    char delim[] = " ";

    recv_buf[n] = 0;
    memset(send_buf, 0, sizeof(send_buf));
    f->fp = help; /* default command */
    memset(t->id, 0, sizeof(struct therapy));

    DEBUG_PRINT("[connfd=%d] Received: %s (len=%d)\n", sockfd, recv_buf, n);
    
    token = strtok(recv_buf, delim);
    if (token != NULL) {
      // secret may be followed by no delimitor 
      if (strncmp(token, "secret", 6) == 0) {
	// participant needs to exploit to go to secret 
	f->fp = disabled;
	DEBUG_PRINT("secret command detected -> fp=disabled\n");
      }

      if (strcmp(token, "therapy")==0){
	f->fp = config_therapy;
	
	token = strtok(NULL, delim);
	DEBUG_PRINT("therapy command detected -> fp=config_therapy token_len=%d\n", strlen(token));
	if (token != NULL) {
	  // this string copy is vulnerable: token is 256 bytes, therapy_id is 50 
	  strcpy(t->id, token);
	  t->id[sizeof(struct therapy)-1] = 0;
	  DEBUG_PRINT("therapy->id=%s len=%d\n", t->id, strlen(t->id));
	}
      }

      sprintf(send_buf, "therapy is at %p, f is at %p (diff=%ld) - heading to fp=%p\n", t, f, (long int)f - (long int) t, f->fp);
      DEBUG_PRINT("[connfd=%d] Sending: %s\n", sockfd, send_buf);
      write(sockfd, send_buf, strlen(send_buf));


      if (f->fp != NULL) {
	DEBUG_PRINT("f->fp=%p secret=%p\n", f->fp, &secret);
	char *answer = f->fp(t->id, strlen(t->id));
	assert(answer != NULL);
	DEBUG_PRINT("[connfd=%d] Sending: %s\n", sockfd, answer);
	write(sockfd, answer, strlen(answer));
	free(answer);
	} 
    }
  }

  DEBUG_PRINT("Closing connection connfd=%d\n", sockfd);
  close(sockfd);
}

/* 
   the server listens on PORT for incoming TCP connections
   each one creates a new thread
   the program never exits
*/
void server(){
  int listenfd = 0;
  struct sockaddr_in serv_addr;
  pthread_t thread_id;
  struct timeval tv;
  
  if ((listenfd = socket(PF_INET, SOCK_STREAM, IPPROTO_TCP))<0) {
    perror("socket");
    exit(EXIT_FAILURE);
  }

  memset(&serv_addr, 0, sizeof(serv_addr));
  serv_addr.sin_family = AF_INET;
  serv_addr.sin_addr.s_addr = INADDR_ANY;
  serv_addr.sin_port =htons(PORT);
  tv.tv_sec = TIMEOUT;
  tv.tv_usec = 0;
  
  if (bind(listenfd, (struct sockaddr*) &serv_addr, sizeof(serv_addr))<0){
    perror("bind");
    exit(EXIT_FAILURE);
  }
  
  if (listen(listenfd, MAXPENDING) < 0){
    perror("listen");
    exit(EXIT_FAILURE);
  }
  DEBUG_PRINT("Listening on %d...\n", PORT);

  while (1) {
    int connfd = accept(listenfd, (struct sockaddr*) NULL, NULL);
    if (connfd >= 0) {
      DEBUG_PRINT("Received incoming connection: connfd=%d\n", connfd);
      if (setsockopt(connfd, SOL_SOCKET, SO_RCVTIMEO, (const char*)&tv, sizeof tv) < 0) {
	perror("setsockopt");
      }

      pid_t pid = fork();
      if (pid == 0) {
	DEBUG_PRINT("Child process: closing listenfd=%d\n", listenfd);
	close(listenfd);
	DEBUG_PRINT("Child process: handling connfd=%d\n", connfd);
	connection_handler( connfd );
	DEBUG_PRINT("Child process: closing connfd=%d\n", connfd);
	close(connfd);
	DEBUG_PRINT("Child process: exiting\n");
	exit(EXIT_SUCCESS);
      } else {
	DEBUG_PRINT("Parent process: child pid=%d - closing connfd=%d\n", pid, connfd);
	close(connfd);
	sleep(1);
      }
    }
  }
  close(listenfd);
}



#ifdef DEBUG
void test_hex2int(char *hexstring, int expected_decimal) {
  int val = hex2int(hexstring);
  if (val != expected_decimal) {
    fprintf(stderr, "FAILED: 0x%s expecting %d, but hex2int returns %d\n", hexstring, expected_decimal, val);
    exit(EXIT_FAILURE);
  } else {
    printf("Hex2int test 0x%s = %d OK\n", hexstring, val);
  }
}

void test_solution() {
  printf("test_solution()...\n");
  char solution_therapy_id[] = "id{this_is_a_fake_therapy_id____absolutely_fake}";
  char *msg = config_therapy(solution_therapy_id, strlen(solution_therapy_id));
  printf("config_therapy() returned: %s\n", msg);
  free(msg);
}

void test_secret() {
  printf("test_secret()...\n");
  char *msg = secret(NULL, 0);
  printf("secret() returned: %s\n", msg);
  free(msg);
}

void test_disabled() {
  printf("test_disabled()...\n");
  char *msg = disabled(NULL, 0);
  printf("disabled() returned: %s\n", msg);
  free(msg);
}

void test(){
  test_hex2int("10", 16);
  test_hex2int("02", 2);
  test_hex2int("0c", 12);
  test_hex2int("ab", 171);
  test_solution();
  test_secret();
  test_disabled();
}

#endif



int main(int argc, char **argv) {
  srand(PORT);
  server();

  exit(EXIT_SUCCESS);
}
