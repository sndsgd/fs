<?php

namespace sndsgd\fs\directory;

use \sndsgd\Fs;

class Hasher
{
    /**
     * Absolute path to the directory to hash
     *
     * @var string
     */
    protected $dir;

    /**
     * A map of relative path => file hash
     *
     * @var array<string,string>
     */
    protected $hashes = [];

    /**
     * @param string $dir The directory to hash
     * @throws \InvalidArgumentException If the directory is not readable
     */
    public function __construct(string $dir)
    {
        $this->dir = Fs::dir($dir);
        if (!$this->dir->test(Fs::EXISTS | Fs::READABLE)) {
            throw new \InvalidArgumentException(sprintf(
                "failed to hash directory; %s",
                $this->dir->getError()
            ));
        }
    }

    /**
     * Get a single hash for all files in the directory
     *
     * @return string
     */
    public function getHash()
    {
        return sha1($this->getHashes());
    }

    /**
     * Get all hashes encoded as pretty printed JSON
     *
     * @return string
     */
    public function getHashes()
    {
        if (empty($this->hashes)) {
            $this->generateHashes();
        }
        return json_encode($this->hashes, \sndsgd\Json::HUMAN);
    }

    /**
     * Create an iterator for looping over files
     * Return type hint excluded for mocking in tests
     */
    protected function createIterator()
    {
        $options = \RecursiveDirectoryIterator::SKIP_DOTS;
        $iterator = new \RecursiveDirectoryIterator($this->dir, $options);
        $options = \RecursiveIteratorIterator::SELF_FIRST;
        return new \RecursiveIteratorIterator($iterator, $options);
    }

    /**
     * Generate hashes for all files found using an iterator
     *
     * @return array<string,string>
     * @throws \RuntimeException If a duplicate directory is encountered
     */
    protected function generateHashes(): array
    {
        $dirLength = strlen($this->dir);
        foreach ($this->createIterator() as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $realpath = $file->getRealPath();
            $path = $file->getPath().DIRECTORY_SEPARATOR.$file->getFilename();

            # skip aliases
            if ($realpath !== $path) {
                continue;
            }

            # remove the relative directory from the file path
            $path = substr($realpath, $dirLength);

            # map hashes by a lowercase version of the path
            # this should prevent issues caused by case sensitive filesystems
            $lowerPath = strtolower($path);
            if (isset($this->hashes[$lowerPath])) {
                $message = "duplicate file encountered: $path ($lowerPath)";
                throw new \RuntimeException($message);
            }

            $this->hashes[$lowerPath] = sha1_file($realpath);
        }

        ksort($this->hashes);
        return $this->hashes;
    }
}
