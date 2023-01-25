# Baker - A simple PHP backup script

Baker is a simple and effective php script that allows you to automate the backups of your websites and databases. 
It also sends you a notification on Slack when the backup is complete.

## Prerequisites

Before you can use Baker, you need to have the following items:

- PHP 5.2 or higher
- PHP curl extension
- PECL yaml extension

Usually, these are already installed on your server.

## Installation

To use Baker, follow these steps:

1. Copy the `baker.php` and `baker.yaml` files to your server.
2. Open the `baker.yaml` file and modify the following variables by inserting your own values:
   - `slack_webhook_url`: the URL of the Slack webhook you want to use to receive notifications.
   - `site_url`: the URL of the website you want to back up. This is used to generate the name of the backup file.
   - `site_path`: the path to the website's root directory.
   - `db.name`: the name of the database to be backed up.
   - `db.host`: the host of the database (e.g. localhost).
   - `db.user`: the username of the database.
   - `db.pass`: the password of the database user.
   - `db.port`: the port of the database.
   - `ftp.host`: the host of the FTP server.
   - `ftp.user`: the username of the FTP server.
   - `ftp.pass`: the password of the FTP server user.

## Usage

To run a backup of your website and database with Baker, simply run the script using the `php baker.php` command. 
The backup will be performed and a notification will be sent to Slack when it is complete.

You can also use a task scheduler (such as `cron`) to automate the backup on a regular interval.

## Contributing

If you would like to contribute to Baker, feel free to open a Pull Request or create an Issue.