<?php

if (!function_exists('__')) {
   function __(string $message, ?string $domain = null): string {
       return $message;
   }
}
