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
        $this->assertInstanceOf(\sndsgd\fs\entity\DirEntity::class, $dir);
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
    public function testCreateFile($tmpdir, $name, $beginsWith, $endsWith)
    {
        Temp::setDir($tmpdir);
        $file = Temp::createFile($name);
        $this->assertTrue(Str::beginsWith($file, $beginsWith));
        if ($endsWith) {
            $this->assertTrue(Str::endsWith($file, $endsWith));
        }
    }

    public function providerCreateFile()
    {
        $tmpdir = vfsStream::url("root/dir.rwx");
        $rand = Str::random(10);

        return [
            [$tmpdir, $rand, "$tmpdir/$rand-", ""],
            [$tmpdir, "$rand.ext", "$tmpdir/$rand-", ".ext"],
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
        $rc = new \ReflectionClass(Temp::class);
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
        $rc = new \ReflectionClass(Temp::class);
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
        $rc = new \ReflectionClass(Temp::class);
        $method = $rc->getMethod("registerEntity");
        $method->setAccessible(true);

        foreach ($entities as $entity) {
            $method->invoke(null, $entity);
        }

        $this->assertSame($expect, Temp::cleanup());
    }

    public function providerCleanup()
    {
        $dir = \sndsgd\fs\entity\DirEntity::class;
        $file = \sndsgd\fs\entity\FileEntity::class;
        return [
            [
                [
                    $this->createRemoveMock($dir, true),
                ],
                true
            ],
            [
                [
                    $this->createRemoveMock($file, true),
                ],
                true
            ],
            [
                [
                    $this->createRemoveMock($dir, false),
                ],
                false
            ],
            [
                [
                    $this->createRemoveMock($file, false),
                ],
                false
            ],

            [
                [
                    $this->createRemoveMock($dir, true),
                    $this->createRemoveMock($file, true),
                ],
                true
            ],

            [
                [
                    $this->createRemoveMock($dir, true),
                    $this->createRemoveMock($dir, true),
                    $this->createRemoveMock($dir, false),
                    $this->createRemoveMock($file, true),
                ],
                false
            ],
        ];
    }

    private function createRemoveMock($class, $removeResult)
    {
        $path = "/tmp/".Str::random(10)."/".Str::random(10)."/".Str::random(10);

        $ret = $this->getMockBuilder($class)
            ->setConstructorArgs([$path])
            ->setMethods(["remove"])
            ->getMock();

        $ret->method("remove")->willReturn($removeResult);
        return $ret;
    }
}
