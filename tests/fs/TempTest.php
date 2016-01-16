<?php

namespace sndsgd\fs;

use \org\bovigo\vfs\vfsStream;
use \sndsgd\Str;

/**
 * @coversDefaultClass \sndsgd\fs\Temp
 */
class TempTest extends TestCase
{
    /**
     * @covers ::setDir
     * @covers ::getDir
     * @dataProvider providerSetGetDir
     */
    public function testSetGetDir($path, $expect)
    {
        Temp::setDir($path);
        $this->assertSame($expect, Temp::getDir());
    }

    public function providerSetGetDir()
    {
        return [
            ["", sys_get_temp_dir()],
            [vfsStream::url("root/dir.rwx"), "vfs://root/dir.rwx"],
            ["", sys_get_temp_dir()],
            [vfsStream::url("root/dir.rwx"), "vfs://root/dir.rwx"],
        ];
    }

    /**
     * @covers ::setDir
     * @dataProvider providerSetDirException
     * @expectedException \InvalidArgumentException
     */
    public function testSetDirException($path)
    {
        Temp::setDir($path);
    }

    public function providerSetDirException()
    {
        return [
            [vfsStream::url("root/file.rw-")],
            [vfsStream::url("root/dir.--x")],
            [vfsStream::url("root/dir.r-x")],
        ];
    }

    /**
     * @covers ::createDir
     * @dataProvider providerCreateDir
     */
    public function testCreateDir($tmpdir, $prefix, $maxAttempts)
    {
        Temp::setDir($tmpdir);
        $dir = Temp::createDir($prefix, $maxAttempts);
        $this->assertInstanceOf("sndsgd\\fs\\Dir", $dir);
        $this->assertSame(0, strpos($dir, "$tmpdir/$prefix-"));
    }

    public function providerCreateDir()
    {
        return [
            [vfsStream::url("root/dir.rwx"), Str::random(10), 1],
        ];
    }

    /**
     * @covers ::createFile
     * @dataProvider providerCreateFile
     */
    public function testCreateFile($tmpdir, $prefix)
    {
        Temp::setDir($tmpdir);
        $file = Temp::createFile($prefix);
        $this->assertInstanceOf("sndsgd\\fs\\File", $file);
        $this->assertSame(0, strpos($file, "$tmpdir/$prefix-"));
    }

    public function providerCreateFile()
    {
        return [
            [vfsStream::url("root/dir.rwx"), Str::random(10), 1],
        ];
    }

    /**
     * @covers ::createDir
     * @covers ::createFile
     * @dataProvider providerCreateException
     * @expectedException \RuntimeException
     */
    public function testCreateException($fn, $tmpdir, $prefix, $maxAttempts)
    {
        $rc = new \ReflectionClass("sndsgd\\fs\\Temp");
        $property = $rc->getProperty("dir");
        $property->setAccessible(true);
        $property->setValue($tmpdir);

        call_user_func($fn, $prefix, $maxAttempts);
    }

    public function providerCreateException()
    {
        $dir = "sndsgd\\fs\\Temp::createDir";
        $file = "sndsgd\\fs\\Temp::createFile";
        return [
            [$dir, vfsStream::url("root/dir.--x"), "test", 1],
            [$dir, vfsStream::url("root/dir.--x"), "test", 5],
            [$file, vfsStream::url("root/dir.--x"), "test", 0],
        ];
    }

    /**
     * @covers ::registerEntity
     * @dataProvider providerRegisterEntity
     */
    public function testRegisterEntity(array $entities)
    {
        $rc = new \ReflectionClass("sndsgd\\fs\\Temp");
        $method = $rc->getMethod("registerEntity");
        $method->setAccessible(true);
        $property = $rc->getProperty("entities");
        $property->setAccessible(true);

        foreach ($entities as $entity) {
            $method->invoke(null, $entity);
        }

        $value = $property->getValue();

        foreach ($entities as $entity) {
            $this->assertArrayHasKey($entity->getPath(), $value);
            $this->assertSame($entity, $value[$entity->getPath()]);
        }
    }

    public function providerRegisterEntity()
    {
        $dir = \sndsgd\Fs::getDir("/path/to/dir");
        $file = \sndsgd\Fs::getFile("/path/to/file.txt");

        return [
            [[$dir]],
            [[$file]],
            [[$dir, $file]],
            [[$dir, $file, $dir, $file]],
        ];
    }

    /**
     * @covers ::cleanup
     * @dataProvider providerCleanup
     */
    public function testCleanup(array $entities, $expect)
    {
        $rc = new \ReflectionClass("sndsgd\\fs\\Temp");
        $method = $rc->getMethod("registerEntity");
        $method->setAccessible(true);

        foreach ($entities as $entity) {
            $method->invoke(null, $entity);
        }

        $this->assertSame($expect, Temp::cleanup());
    }

    public function providerCleanup()
    {
        return [
            [
                [
                    $this->createRemoveMock("Dir", true),
                ],
                true
            ],
            [
                [
                    $this->createRemoveMock("File", true),
                ],
                true
            ],
            [
                [
                    $this->createRemoveMock("Dir", false),
                ],
                false
            ],
            [
                [
                    $this->createRemoveMock("File", false),
                ],
                false
            ],

            [
                [
                    $this->createRemoveMock("Dir", true),
                    $this->createRemoveMock("File", true),
                ],
                true
            ],

            [
                [
                    $this->createRemoveMock("Dir", true),
                    $this->createRemoveMock("Dir", true),
                    $this->createRemoveMock("Dir", false),
                    $this->createRemoveMock("File", true),
                ],
                false
            ],
        ];
    }

    private function createRemoveMock($class, $removeResult)
    {
        $path = "/tmp/".Str::random(10)."/".Str::random(10)."/".Str::random(10);

        $ret = $this->getMockBuilder("sndsgd\\fs\\$class")
            ->setConstructorArgs([$path])
            ->setMethods(["remove"])
            ->getMock();

        $ret->method("remove")->willReturn($removeResult);
        return $ret;
    }
}
