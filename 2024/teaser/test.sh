#!/bin/bash

# Define the ANSI colors
RED="\033[0;31m"
GREEN="\033[0;32m"
# Define the ANSI color code to reset color
NC="\033[0m" # No Color

SCOREBOARD_IP=<INSERT IP ADDRESS>
SCOREBOARD_URL=teaser.ph0wn.org
TENDA_IP=<INSERT IP ADDRESS>

# Function to download a file and verify its SHA256 checksum
download_and_verify() {
    local url=$1
    local expected_sha256=$2

    # Temporary file to store the downloaded file
    local temp_file=$(mktemp)

    # Download the file using curl
    echo "Downloading file from $url..."
    curl -k -s -o "$temp_file" "$url"

    # Check if the download was successful
    if [ $? -ne 0 ]; then
        echo "Download failed."
        rm "$temp_file"
        return 1
    fi

    # Calculate the SHA256 checksum of the downloaded file
    local calculated_sha256=$(sha256sum "$temp_file" | awk '{ print $1 }')

    # Verify the checksum
    if [ "$calculated_sha256" == "$expected_sha256" ]; then
        echo "[+] SHA256 checksum verification passed."
        rm -f "$temp_file"
        return 0
    else
        echo -e "${RED}[-] SHA256 checksum verification failed.${NC}"
        echo "Expected: $expected_sha256"
        echo "Got:      $calculated_sha256"
        # Remove the temporary file
        rm -f "$temp_file"
        return 1
    fi
}

# Function to download a file and verify its SHA256 checksum
download_and_gunzip() {
    local url=$1
    local httpd_sha256=$2
    local pcap_sha256=$3

    # Temporary file to store the downloaded file
    local temp_file=$(mktemp)

    # Download the file using curl
    echo "Downloading file from $url..."
    curl -k -s -o "$temp_file" "$url"

    # Check if the download was successful
    if [ $? -ne 0 ]; then
        echo "Download failed."
        rm "$temp_file"
        return 1
    fi

    # Check if the file is a tar.gz file
    if ! file "$temp_file" | grep -q 'gzip compressed data'; then
	echo "Error: File '$temp_file' is not a tar.gz file!"
	rm "$temp_file"
	return 1
    fi

    # Create a temporary directory for extraction
    tempdir=$(mktemp -d)

    # Extract the tar.gz file
    tar -xzf "$temp_file" -C "$tempdir"
    if [ $? -ne 0 ]; then
	echo "Error: Failed to extract '$temp_file'"
	rm "$temp_file"
	return 1
    fi

    # Count the number of files in the extracted directory
    file_count=$(find "$tempdir" -type f | wc -l)
    if [ "$file_count" -ne 2 ]; then
	echo "Error: The tar.gz file does not contain exactly 2 files"
	rm "$temp_file"
	rm -r "$tempdir"
	return 1
    fi

    # Get the extracted files
    files=($(find "$tempdir" -type f))

    # Compute the SHA256 hashes of the extracted files
    sha256_extracted_file1=$(sha256sum "${files[0]}" | awk '{print $1}')
    sha256_extracted_file2=$(sha256sum "${files[1]}" | awk '{print $1}')

    # Check if the computed SHA256 matches the provided SHA256
    if [[ "$sha256_extracted_file1" == "$httpd_sha256" ]]; then
	echo "[+] router.pcapng SHA256 OK"
    else
	echo -e "${RED}[-] SHA256 checksum verification failed for router.pcapng: $sha256_extracted_file1 ${NC}"
    fi

    if [[ "$sha256_extracted_file2" == "$pcap_sha256" ]]; then
	echo "[+] httpd SHA256 OK"
    else
	echo -e "${RED}[-] SHA256 checksum verification failed for httpd: $sha256_extracted_file2 ${NC}"
    fi

    rm -r $tempdir
    rm $temp_file
    return 0
}

check_tenda() {
    tenda=$(curl -s "http://${TENDA_IP}/login.html")
    if echo "$tenda" | grep -q "Tenda Web Master"; then
	echo "[+] Tenda router page is responding"
    else
	echo -e "${RED}[-] Tenda router is down${NC}"
	return 1
    fi

    local temp_cookie=$(mktemp)
    login_response=$(curl -s -o /dev/null -w "%{http_code}" -c "$temp_cookie" -X POST -d "username=admin&password=3a2270f887b02c94126dc03b3a738a25" "http://${TENDA_IP}/login/Auth")
    if [ "$login_response" -ne 200 ]; then
	echo -e "${RED}[-] Failed to login Tenda Router${NC}"
	rm $temp_cookie
	return 1
    fi
    echo "[+] Logged in Tenda Router with success"
    
    flag_response=$(curl -s -o /dev/null -w "%{http_code}" -b "$temp_cookie" http://${TENDA_IP}/flag)
    if [ "$flag_response" -ne 200 ]; then
	echo -e "${RED}[-] Failed to access the Flag page after login${NC}"
	rm $temp_cookie
	return 1
    fi
    echo "[+] Retrieved the nearly flag page with success"

    flag2_response=$(curl -s -b "$temp_cookie" "http://${TENDA_IP}/flag_b21abc907ad4742969a9970e36ecc8efa995f1720270090a3c7184abacd65061")
    if echo "$flag2_response" | grep -q "ph0wn{"; then
	theflag=$(echo "$flag2_response" | grep -o 'ph0wn{[[:print:]]*}')
	echo -e "[+] Retrieved the Tenda flag: ${GREEN}${theflag}${NC}"
    else
	echo -e "${RED}[-] Error while getting the real Tenda flag${NC}"
	rm $temp_cookie
	return 1
    fi
    rm $temp_cookie
    return 0
}

check_scoreboard() {
    scoremain=$(curl -s -k "https://${SCOREBOARD_URL}")
    if echo "$scoremain" | grep -q "Hall of Fame"; then
	echo "[+] Scoreboard main page"
    else
	echo -e "${RED}[-] Scoreboard main page${NC}"
    fi

    submit=$(curl -s -k "https://${SCOREBOARD_URL}/hurrayifoundtheflag/submit")
    if echo "$submit" | grep -q "Flag format"; then
	echo "[+] Scoreboard submit flag is responding"
    else
	echo -e "${RED}[-] Scoreboard submit flag is down${NC}"
    fi

    return 0
}

check_picostar() {
    picostar=$(curl -s -k "https://${SCOREBOARD_URL}:9950/we_like_satellite1337")
    if echo "$picostar" | grep -q "ph0wn{"; then
	theflag=$(echo "$picostar" | grep -o 'ph0wn{[[:print:]]*}')
	echo -e "[+] PicoStar gives the flag: ${GREEN}${theflag}${NC}"
    else
	echo -e "${RED}[-] PicoStar does not yield the flag{NC}"
    fi
}

check_scoreboard
check_picostar
check_tenda



# Example usage of the function
download_and_verify "https://${SCOREBOARD_URL}/static/images/m42.jpg" "88a8140b695c6505931a2e04421880b508909f7ce5e5d190d37f9adaa2631f28"
download_and_verify "https://${SCOREBOARD_URL}/static/picostar_edcfccda7e90c553e4485cdfe3fbb6d4815c503e2a7e13d3cea47e4fb5c4bc73.apk" "edcfccda7e90c553e4485cdfe3fbb6d4815c503e2a7e13d3cea47e4fb5c4bc73"
download_and_gunzip "https://${SCOREBOARD_URL}/673d27d84d17ef194b0dbe4ac02d85a40d75d8e12310cdd538551bef0fecc333" "2c989c7116cabd8b57c987e503e85bd625d62421d6e408a6ea839b03d4086b72" "3562fa5c4034ae1fc4219f1e58151cacb5fd9c8b450b28d5e94d5a0782605f06"



