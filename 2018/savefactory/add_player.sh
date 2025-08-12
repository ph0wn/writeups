#!/bin/bash


# Username
ln=$(($RANDOM%$(cat possible_usernames | wc -l))) 
USRNAME=$(awk "NR==$ln" possible_usernames)
if [ -z $USRNAME ]
then
    echo "[-] bad username"
    echo "ln=$ln"
    exit 0
fi
sed -i "$ln d" possible_usernames
useradd -m -s /bin/bash "$USRNAME" 2>/dev/null
echo "[+] username: $USRNAME "

# SSH Password
ln=$(($RANDOM%$(cat possible_passwords | wc -l))) 
PASSWD="$(awk "NR==$ln" possible_passwords)""_""$((($RANDOM%8975)+1024))"
if [ -z $PASSWD ]
then
    echo "[-] bad password"
    echo "ln=$ln"
    exit 0
fi
echo "$USRNAME:$PASSWD" 
echo "$USRNAME:$PASSWD" | chpasswd 

# Updating shadow
n=0
while [ -n "$(grep user$n .shadow)" ]; do
	((n++))
done
USRNB=user$n
echo "$USRNB	$USRNAME:$PASSWD" >> /challenger/.shadow

# Challenge server port
# Random port in [6100..6300]
used_ports=$(cat /challenger/.used_ports | awk '{print $2}' | tr "\n" " ") 
PORT=$((($RANDOM%201)+6100))
while [[ $used_ports =~ (^|[[:space:]])($PORT)($|[[:space:]]) ]]; do
	PORT=$((($RANDOM%201)+6100))
done
echo "[+] Challenge server of $USRNAME will be listening on port $PORT"
echo "$USRNB	$USRNAME:$PORT" >> /challenger/.used_ports


# Create homedir for user
mkdir /home/$USRNAME/opcua-challenge
echo "[*] Copying to homedir..."
#cp -r /challenger/README /challenger/cli* -t /home/$USRNAME/opcua-challenge/
#cp -r /challenger/README /challenger/client* -t /home/$USRNAME/opcua-challenge/
cp -r /challenger/client* -t /home/$USRNAME/opcua-challenge/
sed -r -i "s-(127\.0\.0\.1\:)(.{4})-\1$PORT-" /home/$USRNAME/opcua-challenge/client_example.py
chown -R $USRNAME:$USRNAME /home/$USRNAME/opcua-challenge
chmod -R o=--- /home/$USRNAME
echo "cd /home/$USRNAME/opcua-challenge" >> /home/$USRNAME/.profile 
sed -i 's/01;32m/01;37m/g' /home/$USRNAME/.bashrc
sed -i 's/01;34m/01;30m/g' /home/$USRNAME/.bashrc
sed -i 's/u@\\h/u/g' /home/$USRNAME/.bashrc



# Starting a server instance listening on PORT for the challenge
echo "[program:opcua-challenger-$n]" >> /etc/supervisor/conf.d/supervisord.conf 
echo "command=/usr/bin/python /challenger/challenger.py $PORT" >> /etc/supervisor/conf.d/supervisord.conf 
echo "autorestart=true" >> /etc/supervisor/conf.d/supervisord.conf 

# Updating supervisord (without restarting all the supervised processes)
supervisorctl reread
supervisorctl update

