<?php

namespace sndsgd\fs\file;

use \org\bovigo\vfs\vfsStream;
use \sndsgd\Str;


class ReverseReaderTest extends \sndsgd\fs\TestCase
{
    private function createTestFile()
    {
        $path = vfsStream::url("root/file.txt");
        $lines = [ Str::random(ReverseReader::BUFFER_SIZE * 5) ];
        for ($i=0, $len=mt_rand(5, 10); $i<$len; $i++) {
            $lines[] = Str::random(mt_rand(10,78));
        }
        file_put_contents($path, implode(PHP_EOL, $lines));
        return [$path, $lines];
    }

    public function test()
    {
        list($path, $lines) = $this->createTestFile();
        $reader = new ReverseReader($path);
        foreach ($reader as $key => $line) {
            $this->assertEquals($line, array_pop($lines));
        }
    }
}