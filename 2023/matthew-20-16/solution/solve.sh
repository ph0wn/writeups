#!/bin/bash
#
# The index.php script allows the upload of zip files of smalls dimensions.
# Filenames inside the zip file are checked
# Allowed filenames are only those with letters (no dots or numbers)
# This was uploading a .php file is not allowed.
#
# The code is using the $zip->numFiles attribute to iterate and check filenames
# This is an hardcoded value inside the zip file
# One can change it with an hex editor to fool the loop and only check the first file
# If the check passes, the exec unzip will anyhow extract all files (with also the php files)
#
# One can then upload a php file to read /FLAG
#
#
zip upload.zip ./firstfile ./secondfile.php 
#Patch the file with a hex editor to put the numFile as to skip the php file
curl http://127.0.0.1:1234/index.php -F zip=@upload.zip

