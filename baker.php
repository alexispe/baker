#!/usr/bin/php
<?php

try {
    echo "Get settings from baker.yaml" . PHP_EOL;
    $settings = yaml_parse_file('baker.yaml');
    $siteUrl = $settings['site_url'];
    $slackWebhookUrl = $settings['slack_webhook_url'];
    $sitePath = $settings['site_path'];
    $dbName = $settings['database']['name'];
    $dbHost = $settings['database']['host'];
    $dbUser = $settings['database']['user'];
    $dbPass = $settings['database']['pass'];
    $dbPort = $settings['database']['port'];
    $ftpHost = $settings['ftp']['host'];
    $ftpUser = $settings['ftp']['user'];
    $ftpPass = $settings['ftp']['pass'];

    $archiveName = 'backup-' . $siteUrl . '-' . date('Y-m-d-H-i-s') . '.tar.gz';
    $tempDir = 'tmp-backup-' . $siteUrl . '-' . date('Y-m-d-H-i-s');

    echo "Creating temporary directory $tempDir" . PHP_EOL;
    mkdir($tempDir);

    echo "Compressing site files" . PHP_EOL;
    exec("tar -czf $tempDir/site.tar.gz $sitePath");

    echo "Creating database dump" . PHP_EOL;
    exec("mysqldump --host=$dbHost --user=$dbUser --password=$dbPass --port=$dbPort $dbName --no-tablespaces > $tempDir/db.sql");

    echo "Creating archive $archiveName" . PHP_EOL;
    exec("tar -czf $archiveName $tempDir");

    $backupSize = filesize($archiveName);

    echo "Removing temporary directory $tempDir" . PHP_EOL;
    exec("rm -rf $tempDir");

    echo "Uploading archive to FTP" . PHP_EOL;
    $ftpConnection = ftp_connect($ftpHost);
    ftp_login($ftpConnection, $ftpUser, $ftpPass);
    ftp_pasv($ftpConnection, true);
    ftp_put($ftpConnection, $archiveName, $archiveName, FTP_BINARY);
    ftp_close($ftpConnection);

    echo "Removing archive $archiveName" . PHP_EOL;
    exec("rm -rf $archiveName");

    echo "Removing backups older than 7 days" . PHP_EOL;
    $backupRemovedCount = 0;
    $ftpConnection = ftp_connect($ftpHost);
    ftp_login($ftpConnection, $ftpUser, $ftpPass);
    ftp_pasv($ftpConnection, true);
    $files = ftp_nlist($ftpConnection, '.');
    foreach ($files as $file) {
        if (strpos($file, 'backup-' . $siteUrl) !== false) {
            $fileTime = ftp_mdtm($ftpConnection, $file);
            if ($fileTime < strtotime('-7 days')) {
                ftp_delete($ftpConnection, $file);
                $backupRemovedCount++;
            }
        }
    }
    ftp_close($ftpConnection);

    echo "Sending notification to Slack" . PHP_EOL;
    curl_setopt_array($curl = curl_init(), [
        CURLOPT_URL => $slackWebhookUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'text' => "Backup of $siteUrl has been created",
            'attachments' => [
                [
                    'color' => '#36a64f',
                    'fields' => [
                        [
                            'title' => 'Site URL',
                            'value' => $siteUrl,
                            'short' => true,
                        ],
                        [
                            'title' => 'Date',
                            'value' => date('Y-m-d H:i:s'),
                            'short' => true,
                        ],
                        [
                            'title' => 'Size',
                            'value' => humanFilesize($backupSize),
                            'short' => true,
                        ],
                        [
                            'title' => 'Backups removed',
                            'value' => $backupRemovedCount,
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
    ]);
    curl_exec($curl);
    curl_close($curl);
}
catch (Exception $e) {
    echo "Sending error notification to Slack" . PHP_EOL;
    curl_setopt_array($curl = curl_init(), [
        CURLOPT_URL => $slackWebhookUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'text' => "Backup of $siteUrl has failed",
            'attachments' => [
                [
                    'color' => '#ff0000',
                    'fields' => [
                        [
                            'title' => 'Site URL',
                            'value' => $siteUrl,
                            'short' => true,
                        ],
                        [
                            'title' => 'Date',
                            'value' => date('Y-m-d H:i:s'),
                            'short' => true,
                        ],
                        [
                            'title' => 'Error',
                            'value' => $e->getMessage(),
                            'short' => false,
                        ],
                    ],
                ],
            ],
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
    ]);
    curl_exec($curl);
    curl_close($curl);
}


function humanFilesize($bytes, $dec = 2)
{
    $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}