<?php

namespace sndsgd;

use \org\bovigo\vfs\vfsStream;
use \sndsgd\Str;

/**
 * @coversDefaultClass \sndsgd\Fs
 */
class FsTest extends \sndsgd\fs\TestCase
{
    /**
     * @coversNothing
     */
    public function testBytesPerConstants()
    {
        $this->assertSame(1024, Fs::BYTES_PER_KB);
        $this->assertSame(pow(1024, 2), Fs::BYTES_PER_MB);
        $this->assertSame(pow(1024, 3), Fs::BYTES_PER_GB);
        $this->assertSame(pow(1024, 4), Fs::BYTES_PER_TB);
        $this->assertSame(pow(1024, 5), Fs::BYTES_PER_PB);
        $this->assertSame(pow(1024, 6), Fs::BYTES_PER_EB);
    }

    /**
     * @covers ::formatSize
     * @dataProvider providerFormatSize
     */
    public function testFormatSize($size, $precision, $expect)
    {
        $this->assertSame($expect, Fs::formatSize($size, $precision));
    }

    public function providerFormatSize()
    {
        return [
            [1023, 0, "1,023 bytes"],
            [1001, 5, "1,001 bytes"],
            [intval(123.123 * pow(1024, 2)), 1, "123.1 MB"],
            [intval(123.123 * pow(1024, 3)), 2, "123.12 GB"],
            [intval(123.123 * pow(1024, 4)), 3, "123.123 TB"],
            [intval(123.123 * pow(1024, 5)), 4, "123.1230 PB"],
        ];
    }

    /**
     * @covers ::sanitizeName
     * @dataProvider providerSanitizeName
     */
    public function testSanitizeName($test, $expect)
    {
        $this->assertSame($expect, Fs::sanitizeName($test));
    }

    public function providerSanitizeName()
    {
        $ret = [];

        $test = "~`!@#$%^&*()+=";
        $ret[] = [$test, str_repeat("_", strlen($test))];

        $test = "[]{}\\|;:'\",<>?";
        $ret[] = [$test, str_repeat("_", strlen($test))];

        $test = "œ∑´®†¥¨ˆøπ“‘«åß∂ƒ©˙∆˚¬…æΩ≈ç√∫˜µ≤≥÷";
        $ret[] = [$test, str_repeat("_", strlen($test))];

        return array_merge($ret, [
            ['/some/dir/file~!@.txt', '/some/dir/file___.txt'],
            ['/some/dir/💩.ext', "/some/dir/____.ext"],

            # note that the wonkily named parent directory is untouched
            ['/some/!!!/file.txt', '/some/!!!/file.txt'],
        ]);
    }

    /**
     * @covers ::getDir
     */
    public function testGetDir()
    {
        $test = Fs::getDir("/some/dir");
        $this->assertInstanceOf(\sndsgd\fs\entity\DirEntity::class, $test);
    }

    /**
     * @covers ::getFile
     */
    public function testGetFile()
    {
        $test = Fs::getFile("/some/file.ext");
        $this->assertInstanceOf(\sndsgd\fs\entity\FileEntity::class, $test);
    }

    /**
     * @covers ::createFromSplFileInfo
     * @dataProvider providerCreateFromSplFileInfo
     */
    public function testCreateFromSplFileInfo($test, $expectInstance)
    {
        $result = Fs::createFromSplFileInfo($test);
        $this->assertInstanceOf($expectInstance, $result);
    }

    public function providerCreateFromSplFileInfo()
    {
        return [
            [new \SplFileInfo(__FILE__), \sndsgd\fs\entity\FileEntity::class],
            [new \SplFileInfo(__DIR__), \sndsgd\fs\entity\DirEntity::class],
        ];
    }
}
