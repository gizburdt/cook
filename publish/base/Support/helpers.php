<?php

use Illuminate\Contracts\Auth\Authenticatable;

if (! function_exists('user')) {
    function user(): ?Authenticatable
    {
        if (auth()->check()) {
            return auth()->user();
        }

        if (auth('sanctum')->check()) {
            return auth('sanctum')->user();
        }

        return null;
    }
}

if (! function_exists('custom_hash')) {
    function custom_hash(...$values): string
    {
        return hash('sha512', '@pp-'.implode('-', $values));
    }
}

if (! function_exists('small_hash')) {
    function small_hash($input): string
    {
        return hash('crc32', $input);
    }
}
