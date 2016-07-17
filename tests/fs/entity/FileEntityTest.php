<?php

namespace sndsgd\fs\entity;

use \org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \sndsgd\fs\entity\FileEntity
 */
class FileEntityTest extends \sndsgd\fs\TestCase
{
    /**
     * @coversNothing
     */
    private function createTestFile()
    {
        $rand = \sndsgd\Str::random(10);
        $path = vfsStream::url("root/$rand.txt");
        $fp = fopen($path, "w");
        $bytes = 0;
        $len = rand(100, 200);
        for ($i=0; $i<$len; $i++) {
            $bytes += fwrite($fp, \sndsgd\Str::random(rand(5000, 10000)).PHP_EOL);
        }
        fclose($fp);
        return [$path, $bytes, $len];
    }

    /**
     * @covers ::test
     * @covers \sndsgd\fs\entity\EntityAbstract::test
     * @dataProvider providerTest
     */
    public function testTest($path, $test, $expect)
    {
        $file = new FileEntity(vfsStream::url($path));
        $this->assertSame($expect, $file->test($test));
    }

    public function providerTest()
    {
        return [
            ["root/does/not/exist.txt", \sndsgd\Fs::EXISTS, false],
            ["root/file.rw-", \sndsgd\Fs::READABLE, true],
            ["root/file.-w-", \sndsgd\Fs::READABLE, false],
            ["root/dir.rwx", \sndsgd\Fs::READABLE, false],
            ["root/file.---", \sndsgd\Fs::READABLE, false],
            ["root/file.---", \sndsgd\Fs::WRITABLE, false],
            ["root/file.---", \sndsgd\Fs::EXECUTABLE, false],
        ];
    }

    /**
     * @covers ::prepareWrite
     * @dataProvider providerPrepareWrite
     */
    public function testPrepareWrite($path, $expect)
    {
        $file = new FileEntity(vfsStream::url($path));
        $this->assertSame($expect, $file->prepareWrite());
    }

    public function providerPrepareWrite()
    {
        return [
            ["root/file.rw-", true],
            ["root/dir.rwx/file.txt", true],
            ["root/dir.--x/file.txt", false],
        ];
    }

    /**
     * @covers ::getDir
     * @covers ::getParent
     */
    public function testGetDir()
    {
        $file = new FileEntity("/test/dir/file.txt");
        $dir = $file->getDir();
        $this->assertInstanceOf(\sndsgd\fs\entity\DirEntity::class, $dir);
        $this->assertEquals("/test/dir", $dir->getPath());
    }

    /**
     * @covers ::getByteSize
     * @dataProvider providerGetByteSize
     */
    public function testGetByteSize($path, $size, $testResult, $expect)
    {
        $path = vfsStream::url($path);
        $mock = $this->getMockBuilder(\sndsgd\fs\entity\FileEntity::class)
            ->setConstructorArgs([$path])
            ->setMethods(["test"])
            ->getMock();

        $mock->method("test")->willReturn($testResult);

        # vfsstream under php7 seems is a little weird
        # is_writable wasn't working properly here
        if ($size > 0) {
            file_put_contents($path, \sndsgd\Str::random($size));
        }
        $this->assertSame($expect, $mock->getByteSize());
    }

    public function providerGetByteSize()
    {
        return [
            ["root/file.txt", 123, true, 123],
            ["root/file.---", 0, false, -1],

            # force filesize read failure
            ["root/does/not/exist.txt", 0, true, -1],
        ];
    }

    /**
     * @covers ::getSize
     * @dataProvider providerGetSize
     */
    public function testGetSize($byteSize, $precision, $decimal, $sep, $expect)
    {
        $mock = $this->getMockBuilder("sndsgd\\fs\\File")
            ->disableOriginalConstructor()
            ->setMethods(["getByteSize"])
            ->getMock();

        $mock->method("getByteSize")->willReturn($byteSize);

        $this->assertSame($expect, $mock->getSize($precision, $decimal, $sep));
    }

    public function providerGetSize()
    {
        $ret = [];

        # bytesize read failure results in an empty string
        $ret[] = [-1, 1, ".", ",", ""];

        $vals = [1023, 3, ".", ","];
        $expect = call_user_func_array("sndsgd\\Fs::formatSize", $vals);
        $ret[] = array_merge($vals, [$expect]);

        $vals = [intval(512.789 * pow(1024, 3)), 1, ".", ","];
        $expect = call_user_func_array("sndsgd\\Fs::formatSize", $vals);
        $ret[] = array_merge($vals, [$expect]);

        return $ret;
    }



    /**
     * @covers ::canWrite
     * @dataProvider providerCanWrite
     */
    public function testCanWrite($path, $expect)
    {
        $file = new FileEntity(vfsStream::url($path));
        $this->assertSame($expect, $file->canWrite());
    }

    public function providerCanWrite()
    {
        return [
            ["root/file.-w-", true],
            ["root/dir.rwx/file.txt", true],
            ["root/newdir/file.txt", true],
            ["root/dir.--x/file.txt", false],
        ];
    }

    /**
     * @covers ::remove
     * @dataProvider providerRemove
     */
    public function testRemove($path, $expect)
    {
        $file = new FileEntity(vfsStream::url($path));
        $this->assertSame($expect, $file->remove());
        if ($expect === true) {
            $this->assertFalse(file_exists($file));
        }
    }

    public function providerRemove()
    {
        return [
            ["root/file.rwx", true],
            ["root/dir.--x/file.txt", false],
        ];
    }

    /**
     * @covers ::getExtension
     * @dataProvider providerGetExtension
     */
    public function testGetExtension($name, $defaultExt, $expect)
    {
        $file = new FileEntity($name);
        if ($defaultExt === null) {
            $result = $file->getExtension();
        }
        else {
            $result = $file->getExtension($defaultExt);
        }
        $this->assertEquals($expect, $result);
    }

    public function providerGetExtension()
    {
        return [
            ["file.txt", null, "txt"],
            ["file.txt", "ext", "txt"],
            ["/some/path/file.txt", null, "txt"],
            ["/some/path/file.txt", "ext", "txt"],
            [".hidden", null, ""],
            [".hidden", "ext", "ext"],
            [".hidden.txt", null, "txt"],
            [".hidden.txt", "ext", "txt"],
        ];
    }

    /**
     * @covers ::splitName
     * @dataProvider providerSplitName
     */
    public function testSplitName($name, $defaultExt, $expectName, $expectExt)
    {
        $file = new FileEntity($name);
        if ($defaultExt === null) {
            $result = $file->splitName();
        }
        else {
            $result = $file->splitName($defaultExt);
        }

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals($expectName, $result[0]);
        $this->assertEquals($expectExt, $result[1]);
    }

    public function providerSplitName()
    {
        return [
            ["file", null, "file", ""],
            ["file", "ext", "file", "ext"],
            ["/some/path/file", null, "file", ""],
            ["/some/path/file", "ext", "file", "ext"],
            ["file.txt", null, "file", "txt"],
            ["file.txt", "ext", "file", "txt"],
            ["/some/path/file.txt", null, "file", "txt"],
            ["/some/path/file.txt", "ext", "file", "txt"],

            ["/dir/.hidden", null, ".hidden", ""],
            ["/dir/.hidden", "ext", ".hidden", "ext"],
            ["/dir/.hidden.txt", null, ".hidden", "txt"],
            ["/dir/.hidden.txt", "ext", ".hidden", "txt"],
        ];
    }

    /**
     * @covers ::write
     * @covers ::writeFile
     */
    public function testWrite()
    {
        $path = vfsStream::url("root/some/new/path/file.txt");
        $contents = \sndsgd\Str::random(rand(100, 1000));
        $this->assertFalse(file_exists($path));
        $file = new FileEntity($path);
        $file->write($contents);
        $this->assertTrue(file_exists($path));
        $this->assertEquals($contents, file_get_contents($path));

        $contents = "✓ ✔ ✕ ✖ ✗ ✘ ✙ ✚ ✛ ✜ ✝ ✞ ✟ ✠ ✡ ✢ ✣ ✤ ✥ ✦ ✧ ✩ ✪ ✫ ✬ ✭";
        $file->write($contents);
        $this->assertEquals($contents, file_get_contents($path));
    }

    /**
     * @covers ::write
     */
    public function testWritePrepareFailure()
    {
        $path = vfsStream::url("root/dir.--x/file.txt");
        $contents = \sndsgd\Str::random(rand(100, 1000));
        $file = new FileEntity($path);
        $this->assertFalse($file->write($contents));
        $this->assertTrue(is_string($file->getError()));
    }

    /**
     * @covers ::write
     * @covers ::writeFile
     */
    public function testWriteFailure()
    {
        $mock = $this->getMockBuilder("sndsgd\\fs\\File")
            ->setConstructorArgs([vfsStream::url("root/dir.--x/file.txt")])
            ->setMethods(["prepareWrite"])
            ->getMock();

        $mock->method("prepareWrite")->willReturn(true);
        $this->assertFalse($mock->write("testing"));
    }

    private function preparePrependTest($len)
    {
        $path = vfsStream::url("root/ile.txt");
        $contents = \sndsgd\Str::random($len);
        file_put_contents($path, $contents);
        $str = \sndsgd\Str::random(100);
        $expect = $str.$contents;
        return [$path, $contents, $str, $expect];
    }

    /**
     * @covers ::prepend
     */
    public function testPrepend()
    {
        list($path, $contents, $str, $expect) = $this->preparePrependTest(1024);
        $file = new FileEntity($path);
        $this->assertTrue($file->prepend($str));
        $this->assertEquals($expect, file_get_contents($path));
    }

    /**
     * @covers ::prepend
     */
    public function testPrependCannotWrite()
    {
        $file = new FileEntity(vfsStream::url("root/file.---"));
        $this->assertFalse($file->prepend("42"));
        $this->assertTrue(is_string($file->getError()));
    }

    /**
     * @covers ::prepend
     * @covers ::prependFileInPlace
     */
    public function testPrependInPlace()
    {
        list($path, $contents, $str, $expect) = $this->preparePrependTest(1024);
        $file = new FileEntity($path);
        $this->assertTrue($file->prepend($str, 512));
        $this->assertEquals($expect, file_get_contents($path));
    }

    /**
     * @covers ::prepend
     */
    public function testPrependReadFailure()
    {
        $mock = $this->getMockBuilder("sndsgd\\fs\\File")
            ->setConstructorArgs([ vfsStream::url("root/file.-w-") ])
            ->setMethods(["test"])
            ->getMock();
        $mock->method("test")->willReturn(true);
        $this->assertFalse($mock->prepend("42"));
    }

    /**
     * @covers ::prepend
     */
    public function testPrependWriteFailure()
    {
        $mock = $this->getMockBuilder("sndsgd\\fs\\File")
            ->setConstructorArgs([ vfsStream::url("root/file.r--") ])
            ->setMethods(["test"])
            ->getMock();
        $mock->method("test")->willReturn(true);
        $this->assertFalse($mock->prepend("42"));
    }

    /**
     * @covers ::append
     */
    public function testAppend()
    {
        $path = vfsStream::url("root/file.rw-");
        $file = new FileEntity($path);
        $file->write("123");
        $file->append("456789");
        $this->assertEquals("123456789", file_get_contents($path));
    }

    /**
     * @covers ::read
     * @covers ::readFile
     */
    public function testReadFailure()
    {
        $file = new FileEntity(vfsStream::url("root/dir.--x/file.txt"));
        $this->assertFalse($file->read());
        $this->assertTrue(is_string($file->getError()));
    }

    /**
     * @covers ::read
     * @covers ::readFile
     */
    public function testRead()
    {
        # success
        $file = new FileEntity(vfsStream::url("root/file.rwx"));
        $this->assertSame("contents...", $file->read());

        $this->assertSame("contents...", file_get_contents($file));

        # permissions
        $file = new FileEntity(vfsStream::url("root/file.---"));
        $this->assertFalse($file->read());
    }

    /**
     * @covers ::read
     * @covers ::readFile
     */
    public function testReadFileGetContentsFailure()
    {
        $mock = $this->getMockBuilder("sndsgd\\fs\\File")
            ->setConstructorArgs([ vfsStream::url("root/dir.--x") ])
            ->setMethods(["test"])
            ->getMock();

        $mock->method("test")->willReturn(true);
        $this->assertFalse($mock->read());
    }

    /**
     * @covers ::getLineCount
     */
    public function testGetLineCount()
    {
        list($path, $bytes, $lines) = $this->createTestFile();
        $file = new FileEntity($path);
        $this->assertEquals($lines, $file->getLineCount());
    }


}
