<?php

namespace sndsgd\fs\locator;

class GenericLocator extends LocatorAbstract
{
    /**
     * The entities that were found
     *
     * @var array<string,\sndsgd\fs\entity\EntityInterface>
     */
    protected $results = [];

    /**
     * An optional callback for filtering classes
     *
     * @var callable|null
     */
    protected $filter;

    /**
     * A validator for testing the filter
     *
     * @var \sndsgd\fs\locator\GenericLocatorFilterValidator
     */
    protected $filterValidator;

    public function __construct(
        callable $filter = null,
        GenericLocatorFilterValidator $validator = null
    )
    {
        $this->filterValidator = $validator ?? new GenericLocatorFilterValidator();
        $this->setFilter($filter);
    }

    public function setFilter(callable $filter = null): LocatorInterface
    {
        try {
            $this->filterValidator->validate($filter);
        } catch (\Exception $ex) {
            throw new \InvalidArgumentException("", 0, $ex);
        }

        $this->filter = $filter;
        return $this;
    }

    public function searchDir(
        string $dir,
        bool $recursive = false
    ): LocatorInterface
    {
        foreach ($this->getIterator($dir, $recursive) as $entity) {
            $entity = \sndsgd\Fs::createFromSplFileInfo($entity);
            $path = $entity->getPath();
            if (
                isset($this->results[$path]) ||
                ($this->filter && !call_user_func($this->filter, $entity))
            ) {
                continue;
            }
            $this->results[$path] = $entity;
        }

        return $this;
    }

    public function getPaths(): array
    {
        return array_keys($this->results);
    }

    public function getEntities(): array
    {
        return array_values($this->results);
    }
}
