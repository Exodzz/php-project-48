<?php

namespace Gendiff\Tests;

use PHPUnit\Framework\TestCase;
use function Gendiff\CompareArrays\compareArrays;
use function Gendiff\Gendiff\genDiff;
use function Gendiff\Parsers\parse;

class GendiffTest extends TestCase
{
    public function testCompareArrays(): void
    {
        $arr1 = [
            "host" => "hexlet.io",
            "timeout" => 50,
            "proxy" => "123.234.53.22",
            "follow" => false
        ];
        $arr2 = [
            "timeout" => 20,
            "verbose" => true,
            "host" => "hexlet.io"
        ];
        $expected = [
            ['key' => 'follow', 'value' => false, 'mark' => -1],
            ['key' => 'host', 'value' => 'hexlet.io', 'mark' => 0],
            ['key' => 'proxy', 'value' => '123.234.53.22', 'mark' => -1],
            ['key' => 'timeout', 'value' => 50, 'mark' => -1],
            ['key' => 'timeout', 'value' => 20, 'mark' => 1],
            ['key' => 'verbose', 'value' => true, 'mark' => 1]
        ];
        $this->assertEquals($expected, compareArrays($arr1, $arr2));
    }

    public function testGendiff(): void
    {
        $diff = "{\n - follow: false\n   host: hexlet.io\n - proxy: 123.234.53.22\n - timeout: 50\n + timeout: 20\n + verbose: true\n}";
        $actual = genDiff(__DIR__."/fixtures/file1.json", __DIR__."/fixtures/file2.json");
        $this->assertEquals($diff, $actual);
    }

    public function testParseYaml(): void
    {
        $yamlContent = <<<YAML
        follow: false
        host: hexlet.io
        proxy: 123.234.53.22
        timeout: 50
        YAML;

        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        rename($tempFile, $tempFile . '.yaml');
        file_put_contents($tempFile . '.yaml', $yamlContent);

        $result = parse($tempFile . '.yaml');
        
        $this->assertEquals([
            'follow' => false,
            'host' => 'hexlet.io',
            'proxy' => '123.234.53.22',
            'timeout' => 50
        ], $result);

        unlink($tempFile . '.yaml');
    }

    public function testParseInvalidYaml(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $invalidYaml = "invalid: yaml: content:";
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        rename($tempFile, $tempFile . '.yaml');
        file_put_contents($tempFile . '.yaml', $invalidYaml);

        parse($tempFile . '.yaml');
        
        unlink($tempFile . '.yaml');
    }

    public function testUnsupportedFormat(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported file format: txt');
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        rename($tempFile, $tempFile . '.txt');
        
        parse($tempFile . '.txt');
        
        unlink($tempFile . '.txt');
    }

    public function testGendiffWithYaml(): void
    {
        $yaml1 = <<<YAML
        follow: false
        host: hexlet.io
        proxy: 123.234.53.22
        timeout: 50
        YAML;

        $yaml2 = <<<YAML
        timeout: 20
        verbose: true
        host: hexlet.io
        YAML;

        $tempFile1 = tempnam(sys_get_temp_dir(), 'test1_');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test2_');
        
        rename($tempFile1, $tempFile1 . '.yaml');
        rename($tempFile2, $tempFile2 . '.yaml');
        
        file_put_contents($tempFile1 . '.yaml', $yaml1);
        file_put_contents($tempFile2 . '.yaml', $yaml2);

        $expected = "{\n - follow: false\n   host: hexlet.io\n - proxy: 123.234.53.22\n - timeout: 50\n + timeout: 20\n + verbose: true\n}";
        $actual = genDiff($tempFile1 . '.yaml', $tempFile2 . '.yaml');
        
        $this->assertEquals($expected, $actual);

        unlink($tempFile1 . '.yaml');
        unlink($tempFile2 . '.yaml');
    }
}