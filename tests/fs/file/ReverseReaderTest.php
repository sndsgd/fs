<?php

namespace sndsgd\fs\file;

use \org\bovigo\vfs\vfsStream;
use \sndsgd\Str;

/**
 * @coversDefaultClass \sndsgd\fs\file\ReverseReader
 */
class ReverseReaderTest extends \sndsgd\fs\TestCase
{
    private function createTestFile($newline, $bytesPerRead)
    {
        $path = vfsStream::url("root/file.txt");
        for ($i=0, $len=mt_rand(5, 20); $i<$len; $i++) {
            $lines[] = Str::random(mt_rand(10,78));
            $multiplier = floatval(mt_rand(2,5).".".mt_rand(1, 9));
            $longLineLen = intval($bytesPerRead * $multiplier);
            $lines[] = Str::random($longLineLen);
        }
        file_put_contents($path, implode($newline, $lines));
        return [$path, $lines];
    }

    /**
     * @dataProvider providerEverything
     */
    public function testEverything($newline, $bytesPerRead)
    {
        list($path, $lines) = $this->createTestFile($newline, $bytesPerRead);
        $reader = new ReverseReader($path, $newline, $bytesPerRead);
        foreach ($reader as $key => $line) {
            $this->assertEquals($line, array_pop($lines));
        }
    }

    public function providerEverything()
    {
        return [
            ["\n", 10],
            ["\r\n", 10],
            ["---", 10],
            ["\n", 256],
            ["\n", 4096],
            ["\n", 8192],
        ];
    }
}
