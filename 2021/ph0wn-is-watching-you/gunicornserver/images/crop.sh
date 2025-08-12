#!/bin/bash
w=`identify -format '%w' original.jpeg`
h=`identify -format '%h' original.jpeg`
#n=4
#wsize=$((w / n))
#hsize=$((h / n))
wsize=800
hsize=800
echo $wsize
echo $hsize
wmov=$((w / 20))
hmov=$((h / 20))
echo $wmov
echo $hmov
for up in `echo {-10..10}`; do
	for left in `echo {-10..10}`; do
		text=${left}x${up}
		name=`sed s/-/N/g <<< ${text}`
		#echo $name
		offW=$(((up+10)*wmov))
		offH=$(((left+10)*hmov))
		convert original.jpeg -crop ${wsize}x${hsize}+${offW}+${offH} $name &
	done;
done;


