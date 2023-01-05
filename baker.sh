#!/bin/bash
set -e

site_url=
slack_webhook_url=
site_path=
db_name=
db_host=
db_user=
db_pass=
db_port=
ftp_host=
ftp_user=
ftp_pass=

. ./baker.config

archive=$site_url-$(date +%Y-%m-%d-%H-%M-%S).tar.gz
tempdir=tmp-backup-$(date +%Y-%m-%d-%H-%M-%S)

date_days_past() { days_past=${1:-0}; if ! date -v-"${days_past}"d +%Y-%m-%d-%H-%M-%S 2>/dev/null; then date --date="-${days_past} day" +%Y-%m-%d-%H-%M-%S; fi }

printf "\n > Create the temporary working directory";
mkdir "$tempdir"

printf "\n > Compress the site_url files";
tar czf "$tempdir"/site_url "$site_path"

printf "\n > Dump the database";
mysqldump --user="$db_user" --password="$db_pass" --host="$db_host" --port="$db_port" "$db_name" --no-tablespaces > "$tempdir"/database.sql

printf "\n > Create the final archive";
tar czf "$archive" "$tempdir"
backupSize=$(du -h "$archive" | cut -f1)

printf "\n > Remove temporary folder";
rm -r "$tempdir"

printf "\n > Send the archive to the remote server";
curl -p --insecure  "ftp://$ftp_host/" --user "$ftp_user:$ftp_pass" -T "$archive"

printf "\n > Remove archive";
rm -r "$archive"

ftpFiles=$(curl -s --list-only --insecure  "ftp://$ftp_host/" --user "$ftp_user:$ftp_pass")
filteredFtpFiles=$(echo "$ftpFiles" | grep "$site_url")
totalBackups=$(echo "$filteredFtpFiles" | wc -l)
backupsRemoved=0

for dir in $filteredFtpFiles
do
  if [ "$dir" \< "$site_url-$(date_days_past 1).tar.gz" ]; then
    echo "> Delete $dir from the remote server"
    curl --silent --quote "-*DELE $dir" --insecure  "ftp://$ftp_host/" --user "$ftp_user:$ftp_pass"
    backupsRemoved=$((backupsRemoved+1))
  fi
done

backupsRemaining=$((totalBackups-backupsRemoved))

printf "\n > Send Slack notification";
curl -X POST -H 'Content-type: application/json' --data "{
  'text':':robot_face: New backup',
  'attachments': [
    {
      'color': '#36a64f',
      'fields': [
        {
          'title': ':date: Date',
          'value': '$(date +%d-%m-%Y)',
          'short': true
        },
        {
          'title': ':truck: Number of backups',
          'value': '$backupsRemaining',
          'short': true
        },
        {
          'title': ':wastebasket: Backup size',
          'value': '$backupSize',
          'short': true
        },
        {
          'title': ':wastebasket: Cleaned backups',
          'value': '$backupsRemoved',
          'short': true
        }
      ]
    }
  ]
}" "$slack_webhook_url"

printf "\n > Done.\n";

exit 1
