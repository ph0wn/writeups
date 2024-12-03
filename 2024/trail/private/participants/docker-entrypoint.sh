#!/bin/bash

set -e

# Check if REMOVEBEFOREFLIGHT exists.
# If it does, then we need to unpack arm64fs.tar.bz2
# which is actually split in multiple arm64fs-tar.a? files
# of 64MB each and then remove REMOVEBEFOREFLIGHT.

ARM64FSPREFIX="arm64fs-tar"
if [ -f /arm64fs/REMOVEBEFOREFLIGHT ] && [ -f /arm64fs/${ARM64FSPREFIX}.aa ]
then
   echo "[+] First Run - need to unpack our stuff. This may take a while ⏳"
   pushd /arm64fs
   pv ${ARM64FSPREFIX}* | sudo tar -jxf -
   sudo rm -f REMOVEBEFOREFLIGHT ${ARM64FSPREFIX}*
   popd
fi

echo [+] Starting tun0
sudo /etc/local.d/10-tun-network.start 2>&1 >/dev/null

echo [+] Starting NFS
sudo rpcbind -w
sudo rpcinfo
#sudo rpc.nfsd --no-nfs-version 2 --no-nfs-version 3 --nfs-version 4 --debug 4
sudo rpc.nfsd --debug 8
sudo rpc.nfsd --debug 8
sudo exportfs -rv
sudo exportfs
#sudo rpc.mountd --debug all --no-nfs-version 2 --no-nfs-version 3 --nfs-version 4
sudo rpc.mountd --debug all

echo "[+] Setting up forwarded ports ${PORTFWD}"

IFS=',' read -ra PORTLIST <<< "${PORTFWD}"
for PORTPAIR in "${PORTLIST[@]}"
do
   SPORT=$(echo ${PORTPAIR} | cut -d':' -f1)
   DPORT=$(echo ${PORTPAIR} | cut -d':' -f2)
   echo "[+] mapping port ${SPORT} -> 192.168.250.2:${DPORT}"
   socat TCP-LISTEN:${SPORT},fork,reuseaddr TCP:192.168.250.2:${DPORT} &
done

exec "/opt/arm64start"
