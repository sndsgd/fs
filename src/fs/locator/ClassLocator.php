<?php

namespace sndsgd\fs\locator;

class ClassLocator extends GenericLocator
{
    /**
     * A map of the classes found
     *
     * @var array<string,\ReflectionClass>
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
     * @var \ClassLoaderFilterValidator
     */
    protected $filterValidator;

    public function __construct(
        callable $filter = null,
        ClassLocatorFilterValidator $validator = null
    )
    {
        $this->filterValidator = $validator ?? new ClassLocatorFilterValidator();
        $this->setFilter($filter);
    }

    public function searchDir(
        string $dir,
        bool $recursive = false
    ): LocatorInterface
    {
        foreach ($this->getIterator($dir, $recursive) as $file) {
            $class = $this->getClassFromFile($file);
            if (!$class) {
                continue;
            }

            require_once $file->getPathName();
            $reflectionClass = new \ReflectionClass($class);
            if (
                $this->filter &&
                !call_user_func($this->filter, $reflectionClass)
            ) {
                continue;
            }

            $this->results[$class] = $reflectionClass;
        }

        return $this;
    }

    protected function getClassFromFile(\SplFileInfo $file): string
    {
        if (!$file->isFile()) {
            return "";
        }

        $file = new \sndsgd\fs\entity\FileEntity($file);
        $extension = $file->getExtension();
        if (strtolower($extension) !== "php") {
            return "";
        }

        $contents = $file->read();
        if ($contents === false) {
            throw new \RuntimeException($file->getError());
        }

        return \sndsgd\Classname::fromContents($contents);
    }

    public function getClasses(): array
    {
        return array_keys($this->results);
    }

    public function getReflectionClasses(): array
    {
        return array_values($this->results);
    }
}
