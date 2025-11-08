<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

// Forward all requests to public/index.php
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
chdir(__DIR__ . '/public');

require __DIR__ . '/public/index.php';
