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
     */
    public function testNormalize()
    {
        $cwd = getcwd();
        $dir = dirname($cwd);

        $mock = $this->getMockedEntity(".file.txt");
        $this->assertEquals(".file.txt", $mock->normalize());

        $mock = $this->getMockedEntity("/tmp///path/.//file.txt");
        $this->assertEquals("/tmp/path/file.txt", $mock->normalize());

        $mock = $this->getMockedEntity("/tmp/path/../file.txt");
        $this->assertEquals("/tmp/file.txt", $mock->normalize());

        $mock = $this->getMockedEntity("/tmp/test/path/../../file.txt");
        $this->assertEquals("/tmp/file.txt", $mock->normalize());

        $mock = $this->getMockedEntity("test/./path/one/..");
        $this->assertEquals("test/path", $mock->normalize());

        $mock = $this->getMockedEntity(".");
        $this->assertEquals($cwd, $mock->normalize());

        $mock = $this->getMockedEntity("./");
        $this->assertEquals($cwd, $mock->normalize());

        $mock = $this->getMockedEntity("./test");
        $this->assertEquals("$cwd/test", $mock->normalize());

        $mock = $this->getMockedEntity("../test");
        $this->assertEquals("$dir/test", $mock->normalize());

        $mock = $this->getMockedEntity("..");
        $this->assertEquals($dir, $mock->normalize());

        $mock = $this->getMockedEntity("../");
        $this->assertEquals($dir, $mock->normalize());
    }

    /**
     * @covers ::normalizeTo
     */
    public function testNormalizeTo()
    {
        $dir = "/test/path";

        $mock = $this->getMockedEntity("/file.txt");
        $this->assertEquals("/file.txt", $mock->normalizeTo($dir));

        $mock = $this->getMockedEntity("../file.txt");
        $this->assertEquals("/test/file.txt", $mock->normalizeTo($dir));

        $mock = $this->getMockedEntity(".file.txt");
        $this->assertEquals("/test/path/.file.txt", $mock->normalizeTo($dir));
    }

    /**
     * @covers ::getRelativePath
     */
    public function testGetRelativePath()
    {
        $from = "/one/two/file.txt";
        $to = "/one/file.txt";
        $expect = "../file.txt";
        $mock = $this->getMockedEntity($from);
        $this->assertEquals($expect, $mock->getRelativePath($to));

        $from = "/a/b/c";
        $to = "/x/y";
        $expect = "../../x/y";
        $mock = $this->getMockedEntity($from);
        $this->assertEquals($expect, $mock->getRelativePath($to));

        $from = "/la/dee/daa/123-music/";
        $to = "/la/dee/daa/123-some/other/path.txt";
        $expect = "../123-some/other/path.txt";
        $mock = $this->getMockedEntity($from);
        $this->assertEquals($expect, $mock->getRelativePath($to));
    }
}
