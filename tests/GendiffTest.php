<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;
use function Gendiff\CompareArrays\compareArrays;
use function Differ\Differ\genDiff;
use function Gendiff\Parsers\parse;
use function Gendiff\Formatters\plain\formatValue;
use function Gendiff\Formatters\plain\format as formatPlain;
use function Differ\Differ\checkFile;

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


    public function testGendiffWithNestedStructures(): void
    {
        $expected = <<<DIFF
{
  - common: {
    setting1: Value 1
    setting2: 200
    setting3: true
    setting6: {
        key: value
        doge: {
            wow: 
        }
    }
}
  + common: {
    follow: false
    setting1: Value 1
    setting3: null
    setting4: blah blah
    setting5: {
        key5: value5
    }
    setting6: {
        key: value
        ops: vops
        doge: {
            wow: so much
        }
    }
}
  - follow: false
  - group1: {
    baz: bas
    foo: bar
    nest: {
        key: value
    }
}
  + group1: {
    foo: bar
    baz: bars
    nest: str
}
  - group2: {
    abc: 12345
    deep: {
        id: 45
    }
}
  + group3: {
    deep: {
        id: {
            number: 45
        }
    }
    fee: 100500
}
    host: hexlet.io
  - proxy: 123.234.53.22
  - timeout: 50
  + timeout: 20
  + verbose: true
}
DIFF;

        $actual = genDiff(__DIR__."/fixtures/file1.yaml", __DIR__."/fixtures/file2.yaml");
        $this->assertEquals($expected, $actual);
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

        $expected = <<<DIFF
{
  - follow: false
    host: hexlet.io
  - proxy: 123.234.53.22
  - timeout: 50
  + timeout: 20
  + verbose: true
}
DIFF;
        $actual = genDiff($tempFile1 . '.yaml', $tempFile2 . '.yaml');
        
        $this->assertEquals($expected, $actual);

        unlink($tempFile1 . '.yaml');
        unlink($tempFile2 . '.yaml');
    }

    public function testGendiffWithPlainFormat(): void
    {
        $expected = <<<DIFF
Property 'common.follow' was added with value: false
Property 'common.setting2' was removed
Property 'common.setting3' was updated. From true to null
Property 'common.setting4' was added with value: 'blah blah'
Property 'common.setting5' was added with value: [complex value]
Property 'common.setting6.doge.wow' was updated. From '' to 'so much'
Property 'common.setting6.ops' was added with value: 'vops'
Property 'group1.baz' was updated. From 'bas' to 'bars'
Property 'group1.nest' was updated. From [complex value] to 'str'
Property 'group2' was removed
Property 'group3' was added with value: [complex value]
DIFF;

        $actual = genDiff(__DIR__."/fixtures/file1.json", __DIR__."/fixtures/file2.json", 'plain');
        $this->assertEquals($expected, $actual);
    }

    public function testGendiffWithInvalidFormat(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported format: invalid');
        
        genDiff(__DIR__."/fixtures/file1.json", __DIR__."/fixtures/file2.json", 'invalid');
    }

    public function testFormatValue(): void
    {
        $this->assertEquals('true', formatValue(true));
        $this->assertEquals('false', formatValue(false));
        $this->assertEquals('null', formatValue(null));
        $this->assertEquals("'string'", formatValue('string'));
        $this->assertEquals('123', formatValue(123));
        $this->assertEquals('[complex value]', formatValue(['key' => 'value']));
        $this->assertEquals('[complex value]', formatValue((object)['key' => 'value']));
    }

    public function testFormatResult(): void
    {
        $diff = [
            ['key' => 'key1', 'value' => 'value1', 'mark' => 1],
            ['key' => 'key2', 'value' => 'value2', 'mark' => -1],
            ['key' => 'key3', 'value' => 'value3', 'mark' => 0]
        ];

        $expected = "Property 'key1' was added with value: 'value1'\nProperty 'key2' was removed";
        $this->assertEquals($expected, formatPlain($diff));
    }

    public function testCheckFile(): void
    {
        // Тест на несуществующий файл
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found: nonexistent.txt');
        checkFile('nonexistent.txt');

        // Тест на неподдерживаемый формат
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        rename($tempFile, $tempFile . '.txt');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported file format: txt');
        checkFile($tempFile . '.txt');
        unlink($tempFile . '.txt');
    }


}