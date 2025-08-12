FROM ubuntu:latest
MAINTAINER Hamza JAAFAR <hjaafar@fortinet.com>
LABEL Description="OPC-UA Challenge"

ENV USER user
ENV ROOT root
ENV SSH_PASSWORD "weWantSometh1ngSecure"

RUN apt-get update && apt-get install -yqq supervisor openssh-server git vim nano emacs python-pip ipython python3 tree feh
#cron curl wget

## To run gui opcua-client
#RUN apt-get -yqq install libgl1 libfontconfig1 libxi6 libxrender1 
#RUN apt-get install -yqq python3-pip && pip3 install pyqt5 pyqtgraph opcua-client


# SSH server
RUN mkdir /var/run/sshd
RUN echo "$ROOT:$SSH_PASSWORD" | chpasswd
RUN echo "X11Forwarding yes" >> /etc/ssh/sshd_config
RUN echo "X11DisplayOffset 10" >> /etc/ssh/sshd_config
RUN echo "X11UseLocalhost no" >> /etc/ssh/sshd_config
RUN echo "Match user $ROOT" >> /etc/ssh/sshd_config
RUN echo "PermitRootLogin yes" >> /etc/ssh/sshd_config
#RUN cat "/var/run/sshd.pid" | xargs kill -1


## Configuring supervisord (to start challengers and ssh server)
RUN echo "[unix_http_server]" >> /etc/supervisor/supervisord.conf
RUN echo "file=/var/run/supervisor.sock" >> /etc/supervisor/supervisord.conf

RUN echo "[supervisord]" >> /etc/supervisor/supervisord.conf
RUN echo "nodaemon=true" >> /etc/supervisor/supervisord.conf

RUN echo "[rpcinterface:supervisor]" >> /etc/supervisor/supervisord.conf
RUN echo "supervisor.rpcinterface_factory=supervisor.rpcinterface:make_main_rpcinterface" >> /etc/supervisor/supervisord.conf

RUN echo "[supervisorctl]" >> /etc/supervisor/supervisord.conf
RUN echo "serverurl=unix:///var/run/supervisor.sock" >> /etc/supervisor/supervisord.conf

RUN echo "[program:sshd]" >> /etc/supervisor/supervisord.conf
RUN echo "command=/usr/sbin/sshd -D" >> /etc/supervisor/supervisord.conf



# Tragen module
RUN mkdir /challenger
RUN cd /challenger/ && git clone https://github.com/meukh/tragen
RUN cd /challenger/tragen && sed -i 's/sudo //g' Makefile
RUN cd /challenger/tragen && make install

# Adding challenge files and scripts
ADD add_player.sh /challenger
ADD challenger.py /challenger
ADD client_example.py /challenger
ADD possible_passwords /challenger
ADD possible_usernames /challenger
COPY veryRand0mFl1nam3.tgz /challenger
RUN chmod u+x /challenger/add_player.sh
RUN chmod -R o=--- /challenger/


# Removing useless files.
RUN rm -rf /var/lib/apt/lists/*
RUN apt autoremove -yqq
RUN apt autoclean

# SSH PORT
EXPOSE 6666


#CMD [ "/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/supervisord.conf" ]
CMD [ "/usr/bin/supervisord", "-c",  "/etc/supervisor/supervisord.conf" ]
