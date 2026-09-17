<?php

namespace AkshitArora\DbLog;

class LogWriter
{
    /** Append one timestamped log line to a file. */
    public static function append(string $folderPath, string $fileName, string $message): void
    {
        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $line = sprintf('[%s] %s', $timestamp, $message);

        file_put_contents(rtrim($folderPath, '/') . '/' . $fileName, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
