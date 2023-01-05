# Baker

Baker is a simple and effective bash script that allows you to automate the backups of your websites and databases. It also sends you a notification on Slack when the backup is complete.

## Prerequisites

Before you can use Baker, you need to have the following items installed:

- bash
- curl (for sending HTTP requests)

## Installation

To use Baker, follow these steps:

1. Clone this repository to your server.
2. Open the `baker.config` file in your preferred text editor and modify the following variables by inserting your own values:
   - `slack_webhook_url`: the URL of the Slack webhook you want to use to receive notifications.
   - `site_url`: the URL of the website you want to back up. This is used to generate the name of the backup file.
   - `site_path`: the path to the website's root directory.
   - `db_name`: the name of the database to be backed up.
   - `db_host`: the host of the database (e.g. localhost).
   - `db_user`: the username of the database.
   - `db_pass`: the password of the database user.
   - `db_port`: the port of the database.
   - `ftp_host`: the host of the FTP server.
   - `ftp_user`: the username of the FTP server.
   - `ftp_pass`: the password of the FTP server user.

## Usage

To run a backup of your website and database with Baker, simply run the script using the `bash baker.sh` command. 
The backup will be performed and a notification will be sent to Slack when it is complete.

You can also use a task scheduler (such as `cron`) to automate the backup on a regular interval.

## Contributing

If you would like to contribute to Baker, feel free to open a Pull Request or create an Issue.