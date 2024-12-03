#! /bin/bash

# check if bash script has the argument
if [ $# -ne 1 ]; then
    echo "[X] Usage: $0 <userSetup>"
    exit 1
fi

# redirect user bash history to /dev/null
setupBashHistory() {
    echo "[*] Redirecting bash history to /dev/null..."
    echo "HISTFILE=/dev/null" >> /home/adminCroco/.bashrc
    echo "HISTSIZE=0" >> /home/adminCroco/.bashrc
    echo "HISTFILESIZE=0" >> /home/adminCroco/.bashrc
    echo "[*] Bash history redirected."
}


removeUserFromSudoGroup() {
    echo "[*] Removing user 'adminCroco' from sudo group..."
    sudo gpasswd -d username sudo
    echo "[*] User 'adminCroco' removed from sudo group."
}

# function that check if user 'adminCroco' exists 
# if not, create the user
setupUserAccount() {
    if ! id -u adminCroco &>/dev/null; then
        echo "[*] Creating user 'adminCroco'..."
        useradd -m adminCroco
        echo "adminCroco:kuroiCrocodile24#" | chpasswd
        usermod -aG sudo adminCroco
        echo "[*] User 'adminCroco' created."
        removeUserFromSudoGroup
    else
        echo "[*] User 'adminCroco' already exists."
        removeUserFromSudoGroup
    fi
}

# ssh banner message


setupSSHBanner() {
cat << 'EOF' > /etc/update-motd.d/10-uname
#!/bin/bash

export TERM=xterm-256color

CYAN='\033[0;36m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "
${CYAN}
 ░▒▓██████▓▒░░▒▓███████▓▒░░▒▓████████▓▒░▒▓███████▓▒░ ░▒▓██████▓▒░▒▓████████▓▒░▒▓██████▓▒░░▒▓███████▓▒░░▒▓████████▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓███████▓▒░░▒▓██████▓▒░ ░▒▓███████▓▒░░▒▓████████▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓███████▓▒░░▒▓█▓▒░░▒▓█▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░      ░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░      ░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░  ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
 ░▒▓██████▓▒░░▒▓█▓▒░      ░▒▓████████▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░   ░▒▓██████▓▒░░▒▓█▓▒░░▒▓█▓▒░▒▓████████▓▒░                                                                                                                                                                                                                             
${NC}"

echo -e "${RED}There is a creature lurking in the secret shadows of your encrypted SSH network traffic.${NC}"
echo -e "${RED}It is the Kuroi Crocodile. Beware of its presence.${NC}"
echo ""
echo -e "${GREEN}To get the flag, you must look for a suspicious process running in the background,"
echo -e "${GREEN}retrieve it, analyze it, and get the key.${NC}"
echo ""
echo -e "${YELLOW}Once you are done analyzing the curious specimen, tune in carefully to its whispers over the network—"
echo -e "${YELLOW}listening closely will unveil the secret message.${NC}"
echo ""
echo -e "${CYAN}I have a feeling that monitoring recent file changes when an SSH connection is established"
echo -e "${CYAN}will help you in your endeavor.${NC}"
echo ""
echo -e "${RED}Good luck.${NC}"
echo ""
echo -e "${GREEN}ph0wn{stage1_picoAndAPIs_are_not_a_goodmatch?!}${NC}"
EOF
chmod +x /etc/update-motd.d/10-uname

# Init motd message
echo "" > /etc/motd
systemctl restart ssh
echo "[*] SSH banner set."
    
}

# install go
installGo() {
    sudo apt install golang
}
# install tcpdump
installTcpdump() {
    echo "[*] Installing tcpdump..."
    apt-get update
    apt-get install -y tcpdump
    echo "[*] Tcpdump installed."
    echo "[*] Setting up tcpdump..."
    setcap cap_net_raw,cap_net_admin=eip /usr/bin/tcpdump
    echo "[*] Tcpdump set up."
}

#install dig
installDig() {
    echo "[*] Installing dig..."
    apt-get install -y dnsutils
    echo "[*] Dig installed."
}

# install tmux
installTmux() {
    echo "[*] Installing tmux..."
    apt-get install -y tmux
    echo "[*] Tmux installed."
}

# install ncat
installNcat() {
    echo "[*] Installing ncat..."
    apt-get install -y ncat
    echo "[*] Ncat installed."
}

createScheduledTask() {
    if grep -q 'startup.sh' /etc/crontab; then
        echo "[*] Scheduled task already exists."
        return
    else
        echo "[*] Scheduled task does not exist."
        echo "[*] Creating scheduled task..."
        cat << 'EOF' > /home/0xbc/startup.sh
#!/bin/bash

# check if npt is running
if ps aux | grep -v grep | grep -v ntpd | grep -q 'npt'; then
    echo "Network Time protocol is running" >> /var/log/ntp.log
else
    echo "Network Time protocol is not running" >> /var/log/ntp.log
    gcc -shared inject_got.c -ldl -fPIC -o /tmp/payload.so -std=c99
    /bin/npt $(ps aux | grep '/usr/sbin/sshd -D' | grep -v grep | grep listener | awk '{print $2}') &
fi
EOF

        chmod +x /home/0xbc/startup.sh
        echo "* * * * * root /home/0xbc/startup.sh" >> /etc/crontab
        echo "[*] Scheduled task created."
    fi

}





if [ $1 == "userSetup" ]; then
    setupUserAccount
    setupBashHistory
    setupSSHBanner
    installTcpdump
    installNcat
    installGo
    installDig
    installTmux
    createScheduledTask

else
    echo "[X] Usage: $0 <userSetup>"
    exit 1
fi

