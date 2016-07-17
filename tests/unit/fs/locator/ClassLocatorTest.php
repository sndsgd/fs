<?php

namespace sndsgd\fs\locator;

use \org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \sndsgd\fs\locator\ClassLocator
 */
class ClassLocatorTest extends \PHPUnit_Framework_TestCase
{
    private function createValidFilter()
    {
        return function (\ReflectionClass $class): bool
        {
            return !$class->isAbstract();
        };
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $locator = new ClassLocator();
    }

    /**
     * @covers ::setFilter
     * @dataProvider providerSetFilter
     */
    public function testSetFilter(bool $isFilterValid = false)
    {
        $validatorMock = $this->getMockBuilder(ClassLocatorFilterValidator::class)
            ->setMethods(["validate"])
            ->getMock();

        if (!$isFilterValid) {
            $this->setExpectedException(\InvalidArgumentException::class);
            $validatorMock
                ->method("validate")
                ->will($this->throwException(new \Exception()));

        } else {
            $validatorMock
                ->method("validate")
                ->willReturn(true);
        }

        $locator = new ClassLocator(null, $validatorMock);
        $locator->setFilter($this->createValidFilter());
    }

    public function providerSetFilter()
    {
        return [
            [false],
            [true],
        ];
    }

    public function testEverything()
    {
        $locator = new ClassLocator($this->createValidFilter());
        # search the repo root
        $locator->searchDir(__DIR__."/../../../..", false);
        # search the src directory recursively
        $locator->searchDir(__DIR__."/../../../../src", true);
        # re-search to test dkipping already searched directories
        $locator->searchDir(__DIR__."/../../../../src", true);

        # test getting a list of classes found
        foreach ($locator->getClasses() as $class) {
            $this->assertTrue(class_exists($class));
        }

        # test getting a list of reflections for the classes found
        foreach ($locator->getReflectionClasses() as $reflection) {
            $this->assertInstanceOf(\ReflectionClass::class, $reflection);
        }
    }
}
