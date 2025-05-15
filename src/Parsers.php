<?php

namespace Gendiff\Parsers;

use Symfony\Component\Yaml\Yaml;

function convertToArray($data): array
{
    if (is_object($data)) {
        $data = (array) $data;
    }
    
    if (is_array($data)) {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (count($value) === 1 && isset($value[0])) {
                    $result[$key] = $value[0];
                } else {
                    $result[$key] = convertToArray($value);
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    
    return $data;
}

function parse(string $filePath): array
{
    if (!file_exists($filePath)) {
        throw new \RuntimeException("File not found: {$filePath}");
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        throw new \RuntimeException("Cannot read file: {$filePath}");
    }

    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    
    return match ($extension) {
        'json' => json_decode($content, true),
        'yaml', 'yml' => convertToArray(Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP)),
        default => throw new \RuntimeException("Unsupported file format: {$extension}")
    };
} 