<?php

namespace sndsgd\fs\directory;

/**
 * @coversDefaultClass \sndsgd\fs\directory\Hasher
 */
class HasherTest extends \PHPUnit_Framework_TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @dataProvider providerConstructor
     * @covers ::__construct
     */
    public function testConstructor(string $dir, string $expectedException = "")
    {
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $hasher = new Hasher($dir);
    }

    public function providerConstructor()
    {
        $badDirectory = __DIR__.DIRECTORY_SEPARATOR."does-not-exist";
        return [
            [__DIR__, ""],
            [$badDirectory, \InvalidArgumentException::class],
        ];
    }

    /**
     * @dataProvider providerGetHash
     */
    public function testGetHash(string $dir, string $expect)
    {
        $hasher = new Hasher($dir);
        $this->assertSame($expect, $hasher->getHash());
    }

    public function providerGetHash()
    {
        return [
            [
                __DIR__."/../../../data/hash",
                "06c3a47dc82d048059a8aefbfd2a95db50e434ed",
            ],
        ];
    }

    /**
     * @dataProvider providerGenerateHashes
     */
    public function testGenerateHashes(
        array $files,
        array $expect,
        string $expectedException = ""
    )
    {
        $shaMock = $this->getFunctionMock(__NAMESPACE__, "sha1_file");
        $shaMock->expects($this->any())->willReturn("whatever");

        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $mock = $this->getMockBuilder(Hasher::class)
            ->disableOriginalConstructor()
            ->setMethods(["createIterator"])
            ->getMock();

        $mock->method("createIterator")->willReturn($files);

        $reflection = new \ReflectionClass($mock);
        $method = $reflection->getMethod("generateHashes");
        $method->setAccessible(true);

        $this->assertSame($expect, $method->invoke($mock));
    }

    public function providerGenerateHashes()
    {
        return [
            # alias
            [[$this->getMockFile("/path/to", "name", "/real/path")], [], ""],
            # duplicate files
            [
                [
                    $this->getMockFile("/path", "a.txt", "/path/a.txt"),
                    $this->getMockFile("/path", "a.txt", "/path/a.txt"),
                ],
                [],
                \RuntimeException::class
            ],
        ];
    }

    private function getMockFile($path, $filename, $realPath): \SplFileInfo
    {
        $mock = $this->getMockBuilder(\SplFileInfo::class)
            ->disableOriginalConstructor()
            ->setMethods(["isFile", "getPath", "getFilename", "getRealPath"])
            ->getMock();

        $mock->method("isFile")->willReturn(true);
        $mock->method("getPath")->willReturn($path);
        $mock->method("getFilename")->willReturn($filename);
        $mock->method("getRealPath")->willReturn($realPath);


        return $mock;
    }
}
