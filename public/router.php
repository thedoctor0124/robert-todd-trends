<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri === '/_debug') {
    header('Content-Type: text/plain');
    echo '__DIR__: '.__DIR__."\n";
    echo 'DOCUMENT_ROOT: '.$_SERVER['DOCUMENT_ROOT']."\n";
    echo 'Test path: '.__DIR__.'/build/assets/app-B_zd20yd.css'."\n";
    echo 'Exists: '.(is_file(__DIR__.'/build/assets/app-B_zd20yd.css') ? 'YES' : 'NO')."\n";
    exit;
}

if ($uri !== '/' && is_file(__DIR__.$uri)) {
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'mp3' => 'audio/mpeg',
        'pdf' => 'application/pdf',
        'ico' => 'image/x-icon',
    ];
    $ext = pathinfo($uri, PATHINFO_EXTENSION);
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: '.$mimeTypes[$ext]);
    }
    readfile(__DIR__.$uri);
    exit;
}

require_once __DIR__.'/index.php';
