<?php

namespace sndsgd\fs\locator;

class ClassLocator
{
    /**
     * A map of directories already searched
     *
     * @var array<string,bool>
     */
    protected $searchedDirs = [];

    /**
     * A map of the classes found
     *
     * @var array<string,\ReflectionClass>
     */
    protected $classes = [];

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

    public function setFilter(callable $filter = null): ClassLocator
    {
        try {
            $this->filterValidator->validate($filter);
        } catch (\Exception $ex) {
            throw new \InvalidArgumentException(null, 0, $ex);
        }

        $this->filter = $filter;
        return $this;
    }

    public function searchDir(string $dir, bool $recursive = false): ClassLocator
    {
        if (isset($this->searchedDirs[$dir])) {
            return $this;
        }

        $this->searchedDirs[$dir] = true;

        $opts = \RecursiveDirectoryIterator::SKIP_DOTS;
        $iterator = new \RecursiveDirectoryIterator($dir, $opts);
        if ($recursive) {
            $opts = \RecursiveIteratorIterator::SELF_FIRST;
            $iterator = new \RecursiveIteratorIterator($iterator, $opts);
        }

        foreach ($iterator as $file) {
            $class = $this->getClassFromFile($file);
            if (!$class) {
                continue;
            }

            require_once $file->getPathName();
            $reflectionClass = new \ReflectionClass($class);
            if ($this->filter && !call_user_func($this->filter, $reflectionClass)) {
                continue;
            }

            $this->classes[$class] = $reflectionClass;
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
        return array_keys($this->classes);
    }

    public function getReflectionClasses(): array
    {
        return array_values($this->classes);
    }
}
