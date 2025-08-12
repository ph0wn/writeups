FROM ubuntu:16.04
RUN apt-get update
RUN apt-get install -y xinetd socat netcat-openbsd net-tools
RUN apt-get install -y qemu-user

RUN mkdir -p /service && chmod 775 /service

COPY exitonly.conf /etc/xinetd.d/exitonly
COPY flag /service/flag
COPY exitonly /service/exitonly
COPY wrapper.sh /service/wrapper.sh

RUN chmod 755 /service/wrapper.sh

EXPOSE 4242
CMD ["/usr/sbin/xinetd", "-filelog", "-", "-dontfork", "-f", "/etc/xinetd.d/exitonly"]
