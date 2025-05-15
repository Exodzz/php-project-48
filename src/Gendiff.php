<?php

namespace Gendiff\Gendiff;

use Docopt;
use function Gendiff\CompareArrays\compareArrays;
use function Gendiff\Parsers\parse;

function formatValue($value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_null($value)) {
        return 'null';
    }
    if (is_array($value)) {
        $items = array_map(
            fn($key, $val) => "    {$key}: " . formatValue($val),
            array_keys($value),
            array_values($value)
        );
        return "{\n" . implode("\n", $items) . "\n  }";
    }
    return (string) $value;
}

function formatResult(array $diff): string
{
    $lines = array_map(function ($item) {
        $mark = match ($item['mark']) {
            -1      => '-',
            1       => '+',
            default => ' ',
        };
        return " {$mark} {$item['key']}: " . formatValue($item['value']);
    }, $diff);
    $result = implode("\n", $lines);
    return "{\n{$result}\n}";
}

function genDiff(string $filePath1, string $filePath2): string
{
    $data1 = parse($filePath1);
    $data2 = parse($filePath2);

    $resultArray = compareArrays($data1, $data2);
    return formatResult($resultArray);
}

function launchGenDiff(): void
{
    $doc = <<<'DOCOPT'
    Generate diff

    Usage:
        gendiff (-h|--help)
        gendiff (-v|--version)
        gendiff [--format <fmt>] <firstFile> <secondFile>

    Options:
        -h --help                     Show this screen
        -v --version                  Show version
        --format <fmt>                Report format [default: stylish]
    DOCOPT;

    $args = \Docopt::handle($doc, ['version' => 'Gendiff 1.0']);
    
    $firstFile = $args['<firstFile>'];
    $secondFile = $args['<secondFile>'];
    
    try {
        echo genDiff($firstFile, $secondFile);
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function checkFile(string $file = '')
{
    if (!file_exists($file)) {
        throw new \RuntimeException('Files not found');
    }
}
