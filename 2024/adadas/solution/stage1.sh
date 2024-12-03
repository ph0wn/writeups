#!/bin/bash

input_file="words.txt"  
search_file="rg.aut"

# Initialize an empty result string
result=""

# Read each line from the input file
while IFS= read -r word; do
    # Check if the word is present in the search file
    if grep -q "$word" "$search_file"; then
        result+="T"
    else
        result+="F"
    fi
done < "$input_file"

# Print the results on a single line
echo "ph0wn{$result}"
