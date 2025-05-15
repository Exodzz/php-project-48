<?php

namespace Gendiff\Formatters\plain;

function formatValue($value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_null($value)) {
        return 'null';
    }
    if (is_array($value) || is_object($value)) {
        return '[complex value]';
    }
    if (is_string($value)) {
        return "'{$value}'";
    }
    return (string) $value;
}

function getPropertyPath(array $path): string
{
    return implode('.', $path);
}

function formatDiff(array $diff, array $path = []): array
{
    $lines = [];
    $processed = [];
    
    foreach ($diff as $item) {
        $currentPath = array_merge($path, [$item['key']]);
        $propertyPath = getPropertyPath($currentPath);
        
        if (isset($item['children'])) {
            $lines = array_merge($lines, formatDiff($item['children'], $currentPath));
            continue;
        }

        if (isset($processed[$propertyPath])) {
            continue;
        }

        $mark = $item['mark'];
        if ($mark === 0) {
            continue;
        }

        if ($mark === -1) {
            $nextItem = null;
            foreach ($diff as $next) {
                if ($next['key'] === $item['key'] && $next['mark'] === 1) {
                    $nextItem = $next;
                    break;
                }
            }

            if ($nextItem !== null) {
                $lines[] = "Property '{$propertyPath}' was updated. From " . 
                    formatValue($item['value']) . " to " . formatValue($nextItem['value']);
                $processed[$propertyPath] = true;
            } else {
                $lines[] = "Property '{$propertyPath}' was removed";
                $processed[$propertyPath] = true;
            }
        } elseif ($mark === 1 && !isset($processed[$propertyPath])) {
            $lines[] = "Property '{$propertyPath}' was added with value: " . formatValue($item['value']);
            $processed[$propertyPath] = true;
        }
    }
    
    return $lines;
}

function plain(array $diff): string
{
    $lines = formatDiff($diff);
    return implode("\n", $lines);
} 