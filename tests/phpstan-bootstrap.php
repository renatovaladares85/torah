<?php

if (!function_exists('__')) {
   function __(string $message, ?string $domain = null): string {
       return $message;
   }
}

if (!defined('PLUGIN_TORAH_VERSION')) {
   define('PLUGIN_TORAH_VERSION', '0.4.0');
}

class Config
{
   public static function getConfigurationValues(string $context): array {
      return [];
   }

   public static function setConfigurationValues(string $context, array $values): void {
   }
}

class Dropdown
{
   public static function getDropdownName(string $table, int $id): string {
      return '';
   }
}

class Entity
{
   public static function dropdown(array $options): string {
      return '';
   }
}

class Html
{
   public static function script(string $path, array $options = []): string {
      return '';
   }
}

class Plugin
{
   public static function doHook(string $hook, array $params): void {
   }

   public static function getWebDir(string $plugin): string {
      return '';
   }
}

class Profile
{
   public static function dropdown(array $options): string {
      return '';
   }

   public function getFromDB(int $id): bool {
      return true;
   }
}

class Session
{
   public static function haveAccessToEntity(int $entityId, bool $recursive = false): bool {
      return true;
   }
}

class Ticket
{
   /** @var array<string, mixed> */
   public array $fields = [];

   public function isNewItem(): bool {
      return false;
   }
}

final class TemplateRendererPhpstanStub
{
   public static function getInstance(): self {
      return new self();
   }

   public function display(string $template, array $parameters): void {
   }
}

class_alias(TemplateRendererPhpstanStub::class, 'Glpi\\Application\\View\\TemplateRenderer');
