// fwd_server_v2.c
#define _GNU_SOURCE
#include <arpa/inet.h>
#include <errno.h>
#include <netdb.h>
#include <signal.h>
#include <stdbool.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/select.h>
#include <sys/socket.h>
#include <sys/types.h>
#include <time.h>
#include <unistd.h>
#include <fcntl.h>

#ifndef MSG_NOSIGNAL
#define MSG_NOSIGNAL 0
#endif

#define PORT_STR "4321"
#define MAX_CLIENTS 250

#define STDIN_BUF_SIZE 8192

static int set_nonblocking(int fd) {
    int flags = fcntl(fd, F_GETFL, 0);
    if (flags < 0) return -1;
    return fcntl(fd, F_SETFL, flags | O_NONBLOCK);
}

static double now_monotonic_seconds(void) {
    struct timespec ts;
    clock_gettime(CLOCK_MONOTONIC, &ts);
    return (double)ts.tv_sec + (double)ts.tv_nsec / 1e9;
}

static int make_listen_socket(const char *port_str) {
    struct addrinfo hints;
    memset(&hints, 0, sizeof(hints));
    hints.ai_family   = AF_UNSPEC;     // IPv4 or IPv6
    hints.ai_socktype = SOCK_STREAM;
    hints.ai_flags    = AI_PASSIVE;

    struct addrinfo *res = NULL;
    int rc = getaddrinfo(NULL, port_str, &hints, &res);
    if (rc != 0) {
        fprintf(stderr, "getaddrinfo: %s\n", gai_strerror(rc));
        return -1;
    }

    int listen_fd = -1;
    for (struct addrinfo *p = res; p; p = p->ai_next) {
        int fd = socket(p->ai_family, p->ai_socktype, p->ai_protocol);
        if (fd < 0) continue;

        int yes = 1;
        setsockopt(fd, SOL_SOCKET, SO_REUSEADDR, &yes, sizeof(yes));

        if (bind(fd, p->ai_addr, p->ai_addrlen) == 0) {
            if (listen(fd, 64) == 0) {
                listen_fd = fd;
                break;
            }
        }
        close(fd);
    }

    freeaddrinfo(res);

    if (listen_fd < 0) {
        perror("bind/listen");
        return -1;
    }

    if (set_nonblocking(listen_fd) != 0) {
        perror("set_nonblocking(listen_fd)");
        close(listen_fd);
        return -1;
    }

    return listen_fd;
}

static void drop_client(int *clients, int *nclients, int idx) {
    close(clients[idx]);
    clients[idx] = clients[*nclients - 1];
    (*nclients)--;
}

// Best-effort broadcast: tries to send message; if the socket would block, remainder is dropped.
// Disconnects clients on fatal errors.
static void broadcast(int *clients, int *nclients, const char *msg, size_t len) {
    for (int i = 0; i < *nclients; ) {
        int fd = clients[i];
        size_t off = 0;

        while (off < len) {
            ssize_t n = send(fd, msg + off, len - off, MSG_NOSIGNAL);
            if (n > 0) {
                off += (size_t)n;
                continue;
            }
            if (n < 0 && (errno == EAGAIN || errno == EWOULDBLOCK)) {
                // Slow client; drop remainder for simplicity.
                break;
            }
            // Fatal error or disconnect.
            drop_client(clients, nclients, i);
            fd = -1;
            break;
        }

        if (fd != -1) i++;
    }
}

static int randint_inclusive(int lo, int hi) {
    // inclusive uniform-ish (fine for this use)
    if (hi <= lo) return lo;
    int span = hi - lo + 1;
    return lo + (rand() % span);
}

// Reads the next line from a FILE*, cycling to beginning at EOF.
// Returned buffer is owned by caller and should be free()'d.
// Ensures the returned line ends with '\n' (unless file is empty/unreadable).
static char *read_next_cycling_line(FILE *f) {
    if (!f) return NULL;

    char *line = NULL;
    size_t cap = 0;
    ssize_t nread = getline(&line, &cap, f);
    if (nread < 0) {
        // EOF or error: try cycling
        clearerr(f);
        rewind(f);
        nread = getline(&line, &cap, f);
        if (nread < 0) {
            free(line);
            return NULL; // empty file or persistent error
        }
    }

    // Ensure newline termination
    if (nread > 0 && line[nread - 1] != '\n') {
        char *tmp = realloc(line, (size_t)nread + 2);
        if (!tmp) {
            free(line);
            return NULL;
        }
        line = tmp;
        line[nread] = '\n';
        line[nread + 1] = '\0';
    }
    return line;
}

int main(int argc, char **argv) {
    if (argc != 3) {
        fprintf(stderr, "Usage: %s <boat.log> <random_file>\n", argv[0]);
        return 1;
    }

    // Avoid SIGPIPE killing the process when sending to dead sockets.
    signal(SIGPIPE, SIG_IGN);

    srand((unsigned)time(NULL) ^ (unsigned)getpid());

    FILE *boat_f = fopen(argv[1], "r");
    if (!boat_f) {
        perror("fopen(boat.log)");
        return 1;
    }

    FILE *rnd_f = fopen(argv[2], "r");
    if (!rnd_f) {
        perror("fopen(random_file)");
        fclose(boat_f);
        return 1;
    }

    int listen_fd = make_listen_socket(PORT_STR);
    if (listen_fd < 0) {
        fclose(boat_f);
        fclose(rnd_f);
        return 1;
    }

    // Nonblocking stdin + simple line assembly buffer.
    if (set_nonblocking(STDIN_FILENO) != 0) {
        perror("set_nonblocking(stdin)");
        // Not fatal; server still works, but stdin handling may block on partial data.
    }

    int clients[FD_SETSIZE];
    int nclients = 0;

    char stdin_buf[STDIN_BUF_SIZE];
    size_t stdin_len = 0;

    double last_stdin_line_time = now_monotonic_seconds();

    double next_boat_time = last_stdin_line_time + 7.0;               // inactivity trigger
    double next_rnd_time  = now_monotonic_seconds() + randint_inclusive(30, 90);

    fprintf(stderr, "Server listening on port %s\n", PORT_STR);
    fprintf(stderr, "Max clients: %d\n", MAX_CLIENTS);
    fprintf(stderr, "Broadcast stdin lines immediately.\n");
    fprintf(stderr, "If stdin idle 7s: send next line from %s (cycling).\n", argv[1]);
    fprintf(stderr, "Every 30-90s: send next line from %s (cycling).\n", argv[2]);

    for (;;) {
        fd_set rfds;
        FD_ZERO(&rfds);

        int maxfd = listen_fd;
        FD_SET(listen_fd, &rfds);

        // Only include stdin if it's within FD_SETSIZE
        if (STDIN_FILENO < FD_SETSIZE) {
            FD_SET(STDIN_FILENO, &rfds);
            if (STDIN_FILENO > maxfd) maxfd = STDIN_FILENO;
        }

        for (int i = 0; i < nclients; i++) {
            int fd = clients[i];
            if (fd >= FD_SETSIZE) {
                // Should not happen if we enforce at accept time.
                drop_client(clients, &nclients, i);
                i--;
                continue;
            }
            FD_SET(fd, &rfds);
            if (fd > maxfd) maxfd = fd;
        }

        double now = now_monotonic_seconds();

        // Compute next timer deadline (earliest of the two).
        double next_deadline = next_boat_time;
        if (next_rnd_time < next_deadline) next_deadline = next_rnd_time;

        double dt = next_deadline - now;
        if (dt < 0) dt = 0;

        struct timeval tv;
        tv.tv_sec = (int)dt;
        tv.tv_usec = (int)((dt - (double)tv.tv_sec) * 1e6);

        int rc = select(maxfd + 1, &rfds, NULL, NULL, &tv);
        if (rc < 0) {
            if (errno == EINTR) continue;
            perror("select");
            break;
        }

        now = now_monotonic_seconds();

        // Timer: boat.log line if stdin inactive for 7s.
        if (now >= next_boat_time) {
            // Still inactive? (stdin line time drives the schedule)
            if ((now - last_stdin_line_time) >= 7.0) {
                char *line = read_next_cycling_line(boat_f);
                if (line) {
                    broadcast(clients, &nclients, line, strlen(line));
                    free(line);
                }
                // Schedule next boat send 15s from *now* unless stdin line arrives earlier.
                next_boat_time = now + 7.0;
            } else {
                // stdin activity happened; push deadline accordingly
                next_boat_time = last_stdin_line_time + 7.0;
            }
        }

        // Timer: random file line every 30-90 seconds.
        if (now >= next_rnd_time) {
            char *line = read_next_cycling_line(rnd_f);
            if (line) {
                broadcast(clients, &nclients, line, strlen(line));
                free(line);
            }
            next_rnd_time = now + (double)randint_inclusive(30, 90);
        }

        // Accept new connections
        if (FD_ISSET(listen_fd, &rfds)) {
            for (;;) {
                struct sockaddr_storage ss;
                socklen_t slen = sizeof(ss);
                int cfd = accept(listen_fd, (struct sockaddr *)&ss, &slen);
                if (cfd < 0) {
                    if (errno == EAGAIN || errno == EWOULDBLOCK) break;
                    perror("accept");
                    break;
                }

                // Enforce limits:
                if (nclients >= MAX_CLIENTS) {
                    const char *msg = "Server busy (max clients reached).\n";
                    send(cfd, msg, strlen(msg), MSG_NOSIGNAL);
                    close(cfd);
                    continue;
                }
                if (cfd >= FD_SETSIZE) {
                    const char *msg = "Server busy (fd too large for select).\n";
                    send(cfd, msg, strlen(msg), MSG_NOSIGNAL);
                    close(cfd);
                    continue;
                }

                set_nonblocking(cfd);
                clients[nclients++] = cfd;

                const char *welcome = "Connected. You will receive broadcasts.\n";
                send(cfd, welcome, strlen(welcome), MSG_NOSIGNAL);
            }
        }

        // Read stdin (nonblocking) and broadcast complete lines.
        if (STDIN_FILENO < FD_SETSIZE && FD_ISSET(STDIN_FILENO, &rfds)) {
            for (;;) {
                if (stdin_len >= sizeof(stdin_buf)) {
                    // Buffer full without newline; flush as a line to avoid deadlock.
                    broadcast(clients, &nclients, stdin_buf, stdin_len);
                    broadcast(clients, &nclients, "\n", 1);
                    stdin_len = 0;

                    last_stdin_line_time = now_monotonic_seconds();
                    next_boat_time = last_stdin_line_time + 7.0;
                }

                ssize_t n = read(STDIN_FILENO, stdin_buf + stdin_len, sizeof(stdin_buf) - stdin_len);
                if (n > 0) {
                    stdin_len += (size_t)n;

                    // Extract complete lines
                    size_t start = 0;
                    for (size_t i = 0; i < stdin_len; i++) {
                        if (stdin_buf[i] == '\n') {
                            size_t linelen = i - start + 1;
                            broadcast(clients, &nclients, stdin_buf + start, linelen);

                            last_stdin_line_time = now_monotonic_seconds();
                            next_boat_time = last_stdin_line_time + 7.0;

                            start = i + 1;
                        }
                    }

                    // Shift remaining partial line to beginning
                    if (start > 0) {
                        memmove(stdin_buf, stdin_buf + start, stdin_len - start);
                        stdin_len -= start;
                    }

                    continue; // try to drain stdin
                }

                if (n == 0) {
                    // stdin closed (e.g., pipe ended). Keep server alive; just stop reading.
                    break;
                }

                if (n < 0 && (errno == EAGAIN || errno == EWOULDBLOCK)) {
                    break;
                }

                if (n < 0 && errno == EINTR) {
                    continue;
                }

                // Unexpected error
                perror("read(stdin)");
                break;
            }
        }

        // Client readability: detect disconnects; ignore any client-sent data.
        for (int i = 0; i < nclients; ) {
            int fd = clients[i];
            if (!FD_ISSET(fd, &rfds)) {
                i++;
                continue;
            }

            char tmp[512];
            ssize_t n = recv(fd, tmp, sizeof(tmp), 0);
            if (n == 0) {
                drop_client(clients, &nclients, i);
                continue;
            }
            if (n < 0 && (errno == EAGAIN || errno == EWOULDBLOCK)) {
                i++;
                continue;
            }
            if (n < 0) {
                drop_client(clients, &nclients, i);
                continue;
            }
            // Client sent data; ignore it.
            i++;
        }
    }

    for (int i = 0; i < nclients; i++) close(clients[i]);
    close(listen_fd);
    fclose(boat_f);
    fclose(rnd_f);
    return 0;
}
