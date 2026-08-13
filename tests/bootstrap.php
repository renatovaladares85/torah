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

if (!class_exists('Config')) {
   class Config
   {
      /** @var array<string, array<string, mixed>> */
      private static array $values = [];

      /** @return array<string, mixed> */
      public static function getConfigurationValues(string $context): array {
         return self::$values[$context] ?? [];
      }

      /** @param array<string, mixed> $values */
      public static function setTestConfigurationValues(string $context, array $values): void {
         self::$values[$context] = $values;
      }

      /** @param list<string> $values */
      public static function deleteConfigurationValues(string $context, array $values = []): void {
         foreach ($values as $key) {
            unset(self::$values[$context][$key]);
         }
      }
   }
}
