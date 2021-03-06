<?php

namespace sndsgd\fs\entity;

class DirEntity extends EntityAbstract
{
    /**
     * @inheritDoc
     */
    public function test(int $opts): bool
    {
        return parent::test($opts | \sndsgd\Fs::DIR);
    }

    /**
     * @inheritDoc
     */
    public function canWrite()
    {
        $path = $this->path;
        while (file_exists($path) === false) {
            $path = dirname($path);
        }
        $dir = new self($path);
        return $dir->test(\sndsgd\Fs::WRITABLE);
    }

    /**
     * @inheritDoc
     */
    public function prepareWrite($mode = 0775)
    {
        if (file_exists($this->path)) {
            return $this->test(\sndsgd\Fs::WRITABLE);
        }
        else if (mkdir($this->path, $mode, true) === false) {
            $this->setError("failed to create directory '{$this->path}'");
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Get an instance of \sndsgd\fs\File for a file in this directory
     *
     * @param string $name The name of the file in the directory
     * @return \sndsgd\fs\entity\FileEntity
     */
    public function getFile(string $name): FileEntity
    {
        return new FileEntity($this->path.DIRECTORY_SEPARATOR.$name);
    }

    /**
     * Retrieve a subdirectory of the current directory
     *
     * @param string $name The name of the sub directory
     * @return \sndsgd\fs\entity\DirEntity
     */
    public function getDir(string $name): DirEntity
    {
        return new DirEntity($this->path.DIRECTORY_SEPARATOR.$name);
    }

    /**
     * Determine if a directory is empty
     *
     * @return bool
     * @throws \RuntimeException If the directory does not exist or is not readable
     */
    public function isEmpty()
    {
        if ($this->test(\sndsgd\Fs::EXISTS | \sndsgd\Fs::READABLE) === false) {
            throw new \RuntimeException(
                "failed to determine if a directory is empty; ".$this->getError()
            );
        }

        return count(scandir($this->path)) === 2;
    }

    /**
     * get a list of the directory children
     *
     * @param boolean $asStrings Only return the child entity names
     * @return array<string>|boolean
     */
    public function getList($asStrings = false)
    {
        $list = scandir($this->path);
        if ($asStrings === true) {
            return array_diff($list, [".", ".."]);
        }

        $ret = [];
        foreach ($list as $name) {
            if ($name === "." || $name === "..") {
                continue;
            }
            $path = $this->path.DIRECTORY_SEPARATOR.$name;
            $ret[] = (is_dir($path)) ? new static($path) : new FileEntity($path);
        }
        return $ret;
    }

    /**
     * Recursively remove the directory
     *
     * @return bool
     */
    public function remove(): bool
    {
        if ($this->test(\sndsgd\Fs::EXISTS | \sndsgd\Fs::READABLE | \sndsgd\Fs::WRITABLE) === false) {
            $this->error = "failed to remove directory; {$this->error}";
            return false;
        }

        foreach ($this->getList() as $entity) {
            if ($entity->remove() === false) {
                $this->error = $entity->getError();
                return false;
            }
        }

        if (@rmdir($this->path) === false) {
            $this->setError("failed to remove directory '{$this->path}'");
            return false;
        }

        return true;
    }
}
