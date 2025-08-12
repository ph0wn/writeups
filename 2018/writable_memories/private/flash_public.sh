if [ "$#" -ne 1 ]; then
    echo "usage: $0 /dev/XXX"
    exit
fi
sudo mount $1 /mnt
sudo cp ./writeable_memories_public.hex /mnt
sudo umount $1
