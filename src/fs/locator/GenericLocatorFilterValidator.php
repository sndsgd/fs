<?php

namespace sndsgd\fs\locator;

class GenericLocatorFilterValidator
{
    /**
     * @return bool
     * @throws \Exception
     */
    public function validate(callable $filter = null): bool
    {
        if ($filter === null) {
            return true;
        }

        try {
            $this->validateSignature($filter);
        } catch (\Exception $ex) {
            $detailMessage = $ex->getMessage();
            $message = "invalid value provided for 'filter': {$detailMessage}; ".
                "expecting a filter function with the following signature: ".
                "`function(\sndsgd\fs\entity\EntityInterface \$entity): bool;`";

            throw new \Exception($message, 0, $ex);
        }

        return true;
    }

    private function validateSignature(callable $filter = null)
    {
        $reflection = new \ReflectionFunction($filter);
        if ($reflection->getNumberOfParameters() !== 1) {
            throw new \Exception("invalid number of parameters");
        }

        $parameter = $reflection->getParameters()[0];
        $name = $parameter->getName();
        if (!$parameter->hasType()) {
            throw new \Exception("missing required type for parameter '$name'");
        }

        $type = $parameter->getType();
        if ((string) $type !== \sndsgd\fs\entity\EntityInterface::class) {
            throw new \Exception("invalid type for type for parameter '$name'");
        }

        if (!$reflection->hasReturnType()) {
            throw new \Exception("missing required return type");
        }

        $returnType = $reflection->getReturnType();
        if ((string) $returnType !== "bool") {
            throw new \Exception("invalid return type");
        }
    }
}
