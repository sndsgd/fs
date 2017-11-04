<?php

namespace sndsgd\fs\entity;

use \org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \sndsgd\fs\entity\EntityAbstract
 */
class EntityAbstractTest extends \sndsgd\fs\TestCase
{
    const CLASSNAME = \sndsgd\fs\entity\EntityAbstract::class;

    private function getMockedEntity($path)
    {
        $arguments = [$path];
        return $this->getMockForAbstractClass(self::CLASSNAME, $arguments);
    }

    public function setUp()
    {
        $this->path = "/test/dir/file.txt";
        $this->mock = $this->getMockedEntity($this->path);
        $this->rc = new \ReflectionClass(self::CLASSNAME);
    }

    public function testCoreFuncs()
    {
        $this->assertEquals(basename($this->path), basename($this->mock));
        $this->assertEquals(dirname($this->path), dirname($this->mock));
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $constructor = $this->rc->getConstructor();
        $constructor->invoke($this->mock, $this->path);
        $this->assertEquals($this->path, $this->mock->getPath());
    }

    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $this->assertEquals($this->path, $this->mock->__toString());
    }

    /**
     * @covers ::isDir
     */
    public function testIsDir()
    {
        $test = new FileEntity(__FILE__);
        $this->assertFalse($test->isDir());
        $test = new DirEntity(__DIR__);
        $this->assertTrue($test->isDir());
    }

    /**
     * @covers ::isFile
     */
    public function testIsFile()
    {
        $test = new FileEntity(__FILE__);
        $this->assertTrue($test->isFile());
        $test = new DirEntity(__DIR__);
        $this->assertFalse($test->isFile());
    }

    /**
     * @covers ::setError
     */
    public function testSetError()
    {
        $message = "whoop! error!";


        $method = $this->rc->getMethod("setError");
        $method->setAccessible(true);

        $method->invokeArgs($this->mock, [$message]);
        $this->assertEquals($message, $this->mock->getError());

        # force an error to be created
        @file_put_contents(vfsStream::url("root/dir-no-rw/nope.txt"), "test");
        $message = "file write failure";
        $method->invokeArgs($this->mock, [$message]);
        $begin = "$message; file_put_contents";
        $this->assertEquals(0, strpos($this->mock->getError(), $begin));
    }

    /**
     * @covers ::getError
     */
    public function testGetError()
    {
        $this->assertNull($this->mock->getError());

        $err = "oh noes!";
        $property = $this->rc->getProperty("error");
        $property->setAccessible(true);
        $property->setValue($this->mock, $err);
        $this->assertSame($err, $this->mock->getError());
    }

    public function testGetPath()
    {
        $this->assertSame($this->path, $this->mock->getPath());
    }

    /**
     * @covers ::getDirname
     * @dataProvider providerGetDirname
     */
    public function testGetDirname($path, $expect)
    {
        $mock = $this->getMockedEntity($path);
        $this->assertSame($expect, $mock->getDirname());
    }

    public function providerGetDirname(): array
    {
        return [
            ["/a/b/c/test", "/a/b/c"],
            ["/a", "/"],
            ["/", "/"],
        ];
    }

    /**
     * @covers ::getBasename
     * @dataProvider providerGetBasename
     */
    public function testGetBasename($path, $expect)
    {
        $mock = $this->getMockedEntity($path);
        $this->assertSame($expect, $mock->getBasename());
    }

    public function providerGetBasename(): array
    {
        return [
            ["name", "name"],
            ["/a/b/c", "c"],
            ["/a/b/c/", "c"],
            ["/a/b/c/name", "name"],
            ["/a/b/c/.name", ".name"],
        ];
    }

    /**
     * @covers ::getParent
     */
    public function testGetParent()
    {
        $path = "/test/path/file.txt";
        $mock = $this->getMockedEntity($path);

        $parent = $mock->getParent();
        $this->assertInstanceof(\sndsgd\fs\entity\DirEntity::class, $parent);
        $this->assertEquals("/test/path", $parent->getPath());

        $parent = $parent->getParent();
        $this->assertInstanceof(\sndsgd\fs\entity\DirEntity::class, $parent);
        $this->assertEquals("/test", $parent->getPath());

        $parent = $parent->getParent();
        $this->assertInstanceof(\sndsgd\fs\entity\DirEntity::class, $parent);
        $this->assertEquals("/", $parent->getPath());

        $this->assertNull($parent->getParent());
    }

    /**
     * @covers ::isAbsolute
     */
    public function testIsAbsolute()
    {
        $mock = $this->getMockedEntity("/file.txt");
        $this->assertTrue($mock->isAbsolute());

        $mock = $this->getMockedEntity(".file.txt");
        $this->assertFalse($mock->isAbsolute());

        $mock = $this->getMockedEntity("\file.txt");
        $this->assertFalse($mock->isAbsolute());
    }

    /**
     * @covers ::normalize
     * @covers ::normalizeLeadingDots
     * @dataProvider provideNormalize
     */
    public function testNormalize($path, $expect)
    {
        $mock = $this->getMockedEntity($path);
        $this->assertEquals($expect, $mock->normalize());
    }

    public function provideNormalize(): array
    {
        $cwd = getcwd();
        $dir = dirname($cwd);

        return [
            [".", $cwd],
            ["./", $cwd],
            ["..", $dir],
            ["../", $dir],
            ["./test", "$cwd/test"],
            ["../test", "$dir/test"],
            [".file.txt", "$cwd/.file.txt"],
            ["test", "$cwd/test"],
            ["/tmp///path/.//file.txt", "/tmp/path/file.txt"],
            ["/tmp/path/../file.txt", "/tmp/file.txt"],
            ["/tmp/test/path/../../file.txt", "/tmp/file.txt"],
            ["/test/./path/one/..", "/test/path"],
        ];
    }

    /**
     * @covers ::normalizeTo
     * @dataProvider providerNormalizeTo
     */
    public function testNormalizeTo($path, $dir, $expect)
    {
        $mock = $this->getMockedEntity($path);
        $this->assertEquals($expect, $mock->normalizeTo($dir));
    }

    public function providerNormalizeTo()
    {
        return [
            [
                "/file.txt",
                "/test/path",
                "/file.txt",
            ],
            [
                "../file.txt",
                "/test/path",
                "/test/file.txt",
            ],
            [
                ".file.txt",
                "/test/path",
                "/test/path/.file.txt",
            ],
        ];
    }

    /**
     * @covers ::getRelativePath
     * @dataProvider providerGetRelativePath
     */
    public function testGetRelativePath($from, $to, $expect)
    {
        $mock = $this->getMockedEntity($from);
        $this->assertEquals($expect, $mock->getRelativePath($to));
    }

    public function providerGetRelativePath()
    {
        return [
            [
                "/one/two/file.txt",
                "/one/file.txt",
                "../file.txt",
            ],
            [
                "/a/b/c",
                "/x/y",
                "../../x/y",
            ],
            [
                "/la/dee/daa/123-music/",
                "/la/dee/daa/123-some/other/path.txt",
                "../123-some/other/path.txt",
            ],
            [
                "/a/b/c",
                "/a/b/c/d/e/f",
                "d/e/f",
            ],
        ];
    }
}
