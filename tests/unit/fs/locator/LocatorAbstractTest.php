<?php

namespace sndsgd\fs\locator;

class LocatorAbstractTest extends \PHPUnit_Framework_TestCase
{
    private function getMethod($instance, $name)
    {
        $reflection = new \ReflectionClass($instance);
        $method = $reflection->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetIteratorException()
    {
        $mock = $this->getMockForAbstractClass(LocatorAbstract::class);
        $this->getMethod($mock, "getIterator")->invoke($mock, __FILE__);
    }

    /**
     * @dataProvider provideGetIterator
     */
    public function testGetIterator($dir, $recursive, $expect)
    {
        $mock = $this->getMockForAbstractClass(LocatorAbstract::class);
        $this->assertInstanceOf(
            $expect,
            $this->getMethod($mock, "getIterator")->invoke(
                $mock,
                $dir,
                $recursive
            )
        );
    }

    public function provideGetIterator(): array
    {
        return [
            [__DIR__, false, \RecursiveDirectoryIterator::class],
            [__DIR__, true, \RecursiveIteratorIterator::class],
        ];
    }
}
