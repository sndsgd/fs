<?php

namespace sndsgd\fs\locator;

class GenericLocator
{
    /**
     * The entities that were found
     *
     * @var array<string,\sndsgd\fs\entity\EntityInterface>
     */
    protected $entities = [];

    /**
     * An optional callback for filtering classes
     *
     * @var callable|null
     */
    protected $filter;

    /**
     * A validator for testing the filter
     *
     * @var \GenericLocatorFilterValidator
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

    public function setFilter(callable $filter = null): GenericLocator
    {
        try {
            $this->filterValidator->validate($filter);
        } catch (\Exception $ex) {
            throw new \InvalidArgumentException(null, 0, $ex);
        }

        $this->filter = $filter;
        return $this;
    }

    protected function getIterator(string $dir, bool $recursive = false): \Iterator
    {
        $opts = \RecursiveDirectoryIterator::SKIP_DOTS;
        $iterator = new \RecursiveDirectoryIterator($dir, $opts);
        if ($recursive) {
            $opts = \RecursiveIteratorIterator::SELF_FIRST;
            $iterator = new \RecursiveIteratorIterator($iterator, $opts);
        }

        return $iterator;
    }

    public function searchDir(string $dir, bool $recursive = false): GenericLocator
    {
        foreach ($this->getIterator($dir, $recursive) as $entity) {
            $entity = \sndsgd\Fs::createFromSplFileInfo($entity);
            $path = $entity->getPath();
            if (
                isset($this->entities[$path]) ||
                ($this->filter && !call_user_func($this->filter, $entity))
            ) {
                continue;
            }
            $this->entities[$path] = $entity;
        }

        return $this;
    }

    public function getPaths(): array
    {
        return array_keys($this->entities);
    }

    public function getEntities(): array
    {
        return array_values($this->entities);
    }
}
