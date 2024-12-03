participants.zip:
	zip -r participants.zip ./participants/*

upload:
	scp participants.zip axelle@chal.ph0wn.org:./fileserver/files/
	scp ./server/run-trail-server ./server/shutdown-trail-server ./server/arm64start ./server/Dockerfile-ph0wn axelle@chal.ph0wn.org:./trail

