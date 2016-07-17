<?php

namespace sndsgd\fs\locator;

use \org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \sndsgd\fs\locator\GenericLocator
 */
class GenericLocatorTest extends \PHPUnit_Framework_TestCase
{
    private function createValidFilter()
    {
        return function (\sndsgd\fs\entity\EntityInterface $entity): bool
        {
            return $entity->isFile();
        };
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $locator = new GenericLocator();
    }

    /**
     * @covers ::setFilter
     * @dataProvider providerSetFilter
     */
    public function testSetFilter(bool $isFilterValid = false)
    {
        $validatorMock = $this->getMockBuilder(GenericLocatorFilterValidator::class)
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

        $locator = new GenericLocator(null, $validatorMock);
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
        $locator = new GenericLocator($this->createValidFilter());
        # search the repo root
        $locator->searchDir(__DIR__."/../../..", false);
        # search the src directory recursively
        $locator->searchDir(__DIR__."/../../../src", true);

        # test getting a list of classes found
        foreach ($locator->getPaths() as $path) {
            $this->assertTrue(file_exists($path));
        }

        # test getting a list of reflections for the classes found
        foreach ($locator->getEntities() as $entity) {
            $this->assertInstanceOf(
                \sndsgd\fs\entity\EntityInterface::class,
                $entity
            );
        }
    }
}
