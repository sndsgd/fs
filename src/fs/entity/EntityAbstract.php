<?php

namespace sndsgd\fs\entity;

/**
 * Base class for filesystem entities
 */
abstract class EntityAbstract implements EntityInterface
{
    use \sndsgd\ErrorTrait;

    /**
     * The path as provided to the constructor
     * 
     * @var string
     */
    protected $path;

    /**
     * Constructor
     * 
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Get the path as a string
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * Determine whether the entity is a directory
     * 
     * @return bool
     */
    public function isDir(): bool
    {
        return ($this instanceof \sndsgd\fs\entity\DirEntity);
    }

    /**
     * Determine whether the entity is a file
     * 
     * @return bool
     */
    public function isFile(): bool
    {
        return ($this instanceof \sndsgd\fs\entity\FileEntity);
    }

    /**
     * Get the path as a string
     * 
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the parent directory name
     *
     * @return string
     */
    public function getDirname(): string
    {
        return dirname($this->path);
    }

    /**
     * Retrieve the basename of the entity
     *
     * @return string
     */
    public function getBasename(): string
    {
        return basename($this->path);
    }

    /**
     * Perform type/permissions tests on an entity
     *
     * @param int $opts
     * @return bool
     */
    public function test(int $opts): bool
    {
        if ($opts & \sndsgd\Fs::EXISTS && file_exists($this->path) === false) {
            $this->error = "'{$this->path}' does not exist";
            return false;
        }
        else if ($opts & \sndsgd\Fs::FILE && is_file($this->path) === false) {
            $this->error = "'{$this->path}' is not a file";
            return false;
        }
        else if ($opts & \sndsgd\Fs::DIR && is_dir($this->path) === false) {
            $this->error = "'{$this->path}' is not a directory";
            return false;
        }
        else if ($opts & \sndsgd\Fs::READABLE && is_readable($this->path) === false) {
            $this->error = "'{$this->path}' is not readable";
            return false;
        }
        else if ($opts & \sndsgd\Fs::WRITABLE && is_writable($this->path) === false) {
            $this->error = "'{$this->path}' is not writable";
            return false;
        }
        else if ($opts & \sndsgd\Fs::EXECUTABLE && is_executable($this->path) === false) {
            $this->error = "'{$this->path}' is not executable";
            return false;
        }
        return true;
    }

    /**
     * Get the parent directory
     * 
     * @return sndsgd\fs\Dir|null
     * @return sndsgd\fs\Dir The parent directory
     * @return null The entity has no parent
     */
    public function getParent()
    {
        if ($this->path === "/" || ($path = dirname($this->path)) === ".") {
            return null;
        }

        return new DirEntity($path);
    }

    /**
     * {@inheritdoc}
     */
    public function isAbsolute(): bool
    {
        return $this->path{0} === "/";
    }

    /**
     * Normalize a path to remove dots
     * 
     * @return \sndsgd\fs\EntityInterface
     */
    public function normalize(): EntityInterface
    {
        $path = rtrim($this->path, DIRECTORY_SEPARATOR);
        if ($path{0} === ".") {
            $path = $this->normalizeLeadingDots($path);
        }

        $parts = explode("/", $path);
        $isAbsolute = ($parts[0] === "");
        $temp = [];
        foreach ($parts as $part) {
            if ($part === "." || $part === "") {
                continue;
            }
            else if ($part === "..") {
                array_pop($temp);
            }
            else {
                $temp[] = $part;
            }
        }
        $temp = implode("/", $temp);
        $this->path = ($isAbsolute) ? "/$temp" : $temp;
        return $this;
    }

    private function normalizeLeadingDots($path)
    {
        if ($path === ".") {
            return getcwd();
        }
        else if ($path === "..") {
            return dirname(getcwd());
        }
        else if ($path{1} === "/") {
            $path = getcwd().substr($path, 1);
        }
        else if ($path{1} === ".") {
            $path = dirname(getcwd()).substr($path, 2);
        }
        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeTo($dir)
    {
        if ($this->isAbsolute()) {
            return $this->path;   
        }
        $this->path = "$dir/$this->path";
        return $this->normalize();
    }

    /**
     * Get the relative path from the current path to another
     * 
     * @param string $path
     * @return string
     */
    public function getRelativePath(string $path): string
    {
        $from = $this->path;

        $fromParts = explode("/", $from);
        $toParts = explode("/", $path);
        $max = max(count($fromParts), count($toParts));
        for ($i=0; $i<$max; $i++) {
            if (
                !isset($fromParts[$i]) ||
                !isset($toParts[$i]) ||
                $fromParts[$i] !== $toParts[$i]
            ) {
                break;
            }
        }

        $len = count($fromParts) - $i - 1;
        $path = array_slice($toParts, $i);
        if ($len < 0) {
            return implode("/", $path);
        }

        return str_repeat("../", $len).implode("/", $path);
    }
}
