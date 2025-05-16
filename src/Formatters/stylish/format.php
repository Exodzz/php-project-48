<?php

namespace Gendiff\Formatters\stylish;

function formatValue($value, int $depth = 0): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_null($value)) {
        return 'null';
    }
    if (is_object($value)) {
        $value = (array) $value;
    }
    if (is_array($value)) {
        $indent = str_repeat('    ', $depth);
        $items = array_map(
            fn($key, $val) => "{$indent}    {$key}: " . formatValue($val, $depth + 1),
            array_keys($value),
            array_values($value)
        );
        return "{\n" . implode("\n", $items) . "\n{$indent}}";
    }
    return (string) $value;
}

function format(array $diff, int $depth = 0): string
{
    $indent = str_repeat('    ', $depth);
    $lines = array_map(function ($item) use ($indent, $depth) {
        $mark = match ($item['mark']) {
            -1      => '-',
            1       => '+',
            default => ' ',
        };
        
        if (isset($item['children'])) {
            $value = format($item['children'], $depth + 1);
        } else {
            $value = formatValue($item['value'], $depth);
        }
        
        return "{$indent}  {$mark} {$item['key']}: {$value}";
    }, $diff);
    
    $result = implode("\n", $lines);
    return "{\n{$result}\n{$indent}}";
}
