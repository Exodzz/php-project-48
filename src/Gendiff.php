<?php

namespace Differ\Differ;

use Docopt;

use function Gendiff\CompareArrays\compareArrays;
use function Gendiff\Parsers\parse;
use function Gendiff\Formatters\stylish\format as formatStylish;
use function Gendiff\Formatters\plain\format as formatPlain;
use function Gendiff\Formatters\json\format as formatJson;

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
    return (string)$value;
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

function checkFile(string $filePath): bool
{
    if (!file_exists($filePath)) {
        throw new \RuntimeException("File not found: {$filePath}");
    }

    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    if (!in_array($extension, ['json', 'yaml', 'yml'])) {
        throw new \RuntimeException("Unsupported file format: {$extension}");
    }

    return true;
}

function genDiff(string $filePath1, string $filePath2, string $format = 'stylish'): string
{
    checkFile($filePath1);
    checkFile($filePath2);

    $data1 = parse($filePath1);
    $data2 = parse($filePath2);

    $resultArray = compareArrays($data1, $data2);

    return match ($format) {
        'stylish' => formatStylish($resultArray),
        'plain' => formatPlain($resultArray),
        'json' => formatJson($resultArray),
        default => throw new \RuntimeException("Unsupported format: {$format}")
    };
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
  --format <fmt>                Output format [default: stylish]
DOCOPT;

    try {
        $args = Docopt::handle($doc, ['version' => '1.0.0']);
        $format = $args->args['--format'] ?? 'stylish';
        $file1 = $args->args['<firstFile>'];
        $file2 = $args->args['<secondFile>'];

        echo genDiff($file1, $file2, $format);
    } catch (\Exception | \RuntimeException $e) {
        echo $e->getMessage() ;
    }
}
