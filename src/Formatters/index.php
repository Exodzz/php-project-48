<?php

namespace Gendiff\Formatters;

use function Gendiff\Formatters\stylish\stylish;
use function Gendiff\Formatters\plain\plain;

function format(array $diff, string $format = 'stylish'): string
{
    return match ($format) {
        'stylish' => stylish($diff),
        'plain' => plain($diff),
        default => throw new \RuntimeException("Unsupported format: {$format}")
    };
} 