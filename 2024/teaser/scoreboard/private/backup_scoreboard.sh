#!/bin/bash

# Directory to store backups
backup_dir="./backup_scoreboard"

# File name for backup
backup_file="success_records_backup_$(date +'%Y%m%d_%H%M%S').tar.gz"

# Perform backup
docker cp scoreboard-scoreboard-1:/app/logs/success_records.csv "$backup_dir/success_records.csv"
tar czf "$backup_dir/$backup_file" "$backup_dir/success_records.csv"
rm "$backup_dir/success_records.csv"

# Cleanup old backups (keep only the last 3)
cd "$backup_dir"
ls -t success_records_backup_* | tail -n +4 | xargs rm -f
