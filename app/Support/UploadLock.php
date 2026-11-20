<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/** Puts a script-blocking .htaccess into an upload folder of the public disk (Apache; harmless elsewhere). */
final class UploadLock
{
    public static function ensure(string $dir): void
    {
        $disk = Storage::disk('public');
        $file = trim($dir, '/').'/.htaccess';
        if ($disk->exists($file)) {
            return;
        }
        $disk->put($file, "# Uploaded media only: nothing in this folder may ever run as a script.\n"
            ."<IfModule mod_php.c>\n    php_flag engine off\n</IfModule>\n"
            ."<IfModule mod_php8.c>\n    php_flag engine off\n</IfModule>\n"
            ."RemoveHandler .php .phtml .phar .pl .py .cgi\n"
            ."RemoveType .php .phtml .phar\n"
            ."Options -ExecCGI -Indexes\n"
            ."<FilesMatch \"(?i)\.(php\d*|phtml|phar|pl|py|cgi|sh|html?|svg|js)$\">\n    Require all denied\n</FilesMatch>\n");
    }
}
