<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    throw new \RuntimeException('Run composer install before executing the test suite.');
}

require $autoload;

$glpiRoot = getenv('GLPI_ROOT');
if (is_string($glpiRoot) && is_file($glpiRoot . '/inc/includes.php')) {
    require_once $glpiRoot . '/inc/includes.php';
}

if (!function_exists('__')) {
   function __(string $message, ?string $domain = null): string {
       return $message;
   }
}
