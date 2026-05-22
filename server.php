<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists($_SERVER['DOCUMENT_ROOT'].$uri)) {
    return false;
}

$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'].'/index.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/index.php';
