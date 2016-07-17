<?php

namespace sndsgd\fs\locator;

use \org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \sndsgd\fs\locator\GenericLocatorFilterValidator
 */
class GenericLocatorFilterValidatorTest extends \PHPUnit_Framework_TestCase
{
    private function createValidFilter()
    {
        return function (\sndsgd\fs\entity\EntityInterface $entity): bool
        {
            return true;
        };
    }

    private function createInvalidNumberOfParametersFilter()
    {
        return function ($one, $two): bool
        {
            return true;
        };
    }

    private function createMissingParameterTypeFilter()
    {
        return function ($name): bool
        {
            return true;
        };
    }

    private function createInvalidParameterTypeFilter()
    {
        return function (string $name): bool
        {
            return true;
        };
    }

    private function createMissingReturnTypeFilter()
    {
        return function (\sndsgd\fs\entity\EntityInterface $name)
        {
            return;
        };
    }

    private function createInvalidReturnTypeFilter()
    {
        return function (\sndsgd\fs\entity\EntityInterface $name): string
        {
            return "test";
        };
    }

    /**
     * @dataProvider providerValidate
     */
    public function testIsValid($filter, $expectedException)
    {
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $validator = new GenericLocatorFilterValidator();
        $validator->validate($filter);
    }

    public function providerValidate()
    {
        return [
            [null, ''],
            [$this->createValidFilter(), ''],
            [$this->createInvalidNumberOfParametersFilter(), \Exception::class],
            [$this->createMissingParameterTypeFilter(), \Exception::class],
            [$this->createInvalidParameterTypeFilter(), \Exception::class],
            [$this->createMissingReturnTypeFilter(), \Exception::class],
            [$this->createInvalidReturnTypeFilter(), \Exception::class],
        ];
    }
}
