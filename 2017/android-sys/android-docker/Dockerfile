FROM ubuntu:16.04

MAINTAINER Axelle Apvrille <aapvrille@fortinet.com>
ENV REFRESHED_AT 2017-10-02

ARG DEBIAN_FRONTEND=noninteractive
ENV ANDROID_SDK_VERSION "r25.2.5"
ENV ANDROID_BUILD_VERSION "25.0.3"
ENV SSH_PASSWORD "rootpass"
ENV VNC_PASSWORD "rootpass"
ENV USER root
ENV TERM xterm

# System install ------------------------------
RUN dpkg --add-architecture i386
RUN apt-get update && \
    apt-get install -yqq \
    default-jdk \
    software-properties-common \
    unzip \
    zip \
    wget \
    build-essential \
    emacs \
    iputils-ping \
    python-protobuf \
    python-pip \
    protobuf-compiler \
    apt-transport-https \
    openssh-server \
    ssh  \
    python3 \
    libc6-i686:i386 \ 
    libexpat1:i386 \
    libffi6:i386 \
    libfontconfig1:i386 \
    libfreetype6:i386 \		
    libgcc1:i386 \
    libglib2.0-0:i386 \ 
    libice6:i386 \
    libpcre3:i386 \	
    libpng12-0:i386 \
    libsm6:i386 \
    libstdc++6:i386 \ 
    libuuid1:i386 \
    libx11-6:i386 \
    libxau6:i386 \ 
    libxcb1:i386 \
    libxdmcp6:i386 \
    libxext6:i386 \
    libxrender1:i386 \
    zlib1g:i386 \
    libx11-xcb1:i386 \
    libdbus-1-3:i386 \
    libxi6:i386 \
    libsm6:i386 \
    libcurl3:i386 \
    xvfb \
    x11vnc \
    xfce4 \
    xfce4-terminal \
    supervisor \
    && rm -rf /var/lib/apt/lists/*		   

# We need supervisor to launch ssh and vnc
RUN mkdir -p /var/log/supervisor

# Install SSH access
RUN mkdir /var/run/sshd
RUN echo "root:$SSH_PASSWORD" | chpasswd
RUN sed -i 's/PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config
RUN echo "X11UseLocalhost no" >> /etc/ssh/sshd_config
RUN sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd
# SSH login fix. Otherwise user is kicked off after login


# Install VNC server - we need GLX support for Android emulator
RUN mkdir ~/.vnc
RUN  x11vnc -storepasswd $VNC_PASSWORD ~/.vnc/passwd
RUN echo '#!/bin/bash' >> /root/startXvfb.sh
RUN echo "Xvfb :1 +extension GLX +render -noreset -screen 0 1280x1024x24& DISPLAY=:1 /usr/bin/xfce4-session >> /root/xsession.log 2>&1 &"  >> /root/startXvfb.sh
RUN echo "x11vnc -loop -usepw -display :1"  >> /root/startXvfb.sh
RUN echo "exit 0"  >> /root/startXvfb.sh

# Configure supervisor
RUN echo "[supervisord]" >> /etc/supervisor/conf.d/supervisord.conf
RUN echo "nodaemon=true" >> /etc/supervisor/conf.d/supervisord.conf
RUN echo "[program:sshd]" >> /etc/supervisor/conf.d/supervisord.conf
RUN echo "command=/usr/sbin/sshd -D" >> /etc/supervisor/conf.d/supervisord.conf
RUN echo "[program:startxvfb]">> /etc/supervisor/conf.d/supervisord.conf
RUN echo "command=/bin/sh /root/startXvfb.sh">> /etc/supervisor/conf.d/supervisord.conf
#RUN echo "[program:vnc]" >> /etc/supervisor/conf.d/supervisord.conf
#RUN echo "command=/root/vnc.sh" >> /etc/supervisor/conf.d/supervisord.conf

# Android Reverse Engineering tools -------------
RUN mkdir -p /opt


# Android emulator
RUN wget -q -O "/opt/tools-linux.zip" https://dl.google.com/android/repository/tools_$ANDROID_SDK_VERSION-linux.zip
RUN unzip /opt/tools-linux.zip -d /opt/android-sdk-linux
RUN rm -f /opt/tools-linux.zip
ENV ANDROID_HOME /opt/android-sdk-linux
ENV PATH $PATH:/opt:$ANDROID_HOME/tools:$ANDROID_HOME/platform-tools
RUN echo y | android update sdk --filter tools --no-ui --force -a
RUN echo y | android update sdk --filter platform-tools --no-ui --force -a
RUN echo y | android update sdk --filter build-tools-$ANDROID_BUILD_VERSION --no-ui --force -a
RUN echo y | android update adb
RUN echo y | android update sdk --filter android-22 --no-ui --force -a
RUN echo y | android update sdk --filter sys-img-armeabi-v7a-android-22 --no-ui --force -a
RUN echo n | android create avd --force --name "Arm51" --target android-22 --abi "default/armeabi-v7a"
RUN mkdir ${ANDROID_HOME}/tools/keymaps && touch ${ANDROID_HOME}/tools/keymaps/en-us
ENV LD_LIBRARY_PATH $LD_LIBRARY_PATH:${ANDROID_HOME}/tools/lib64/qt/lib:${ANDROID_HOME}/tools/lib64

# Cleaning up and final setup -------------------------
RUN apt-get autoremove -yqq
RUN apt-get clean

RUN echo "export PATH=$PATH" >> /etc/profile
RUN echo "export LD_LIBRARY_PATH=$LD_LIBRARY_PATH" >> /etc/profile
RUN echo "alias emulator='/opt/android-sdk-linux/tools/emulator64-arm -avd Arm51 -no-boot-anim'" >> /root/.bashrc
RUN echo "export LC_ALL=C" >> /root/.bashrc

ADD app-release.apk /root/app-release.apk

CMD [ "/usr/bin/supervisord", "-c",  "/etc/supervisor/conf.d/supervisord.conf" ]

EXPOSE 5554
EXPOSE 5555
EXPOSE 5900
EXPOSE 5037
EXPOSE 22




