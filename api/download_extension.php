<?php
/**
 * LeadForge AI — Download Chrome Extension ZIP
 */

declare(strict_types=1);

$zipPath = __DIR__ . '/../extension/leadforge_extension.zip';

// If zip doesn't exist, dynamically build it using ZipArchive
if (!file_exists($zipPath) && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $files = [
            'manifest.json' => __DIR__ . '/../extension/manifest.json',
            'background.js' => __DIR__ . '/../extension/background.js',
            'content_profile.js' => __DIR__ . '/../extension/content_profile.js',
            'content_comment.js' => __DIR__ . '/../extension/content_comment.js',
            'popup.html' => __DIR__ . '/../extension/popup.html',
            'popup.js' => __DIR__ . '/../extension/popup.js',
        ];
        foreach ($files as $name => $path) {
            if (file_exists($path)) {
                $zip->addFile($path, $name);
            }
        }
        $zip->close();
    }
}

if (file_exists($zipPath)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="leadforge_chrome_extension.zip"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($zipPath);
    exit;
}

http_response_code(404);
echo "Extension ZIP archive not found.";
exit;
