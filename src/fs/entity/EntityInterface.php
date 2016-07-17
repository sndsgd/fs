<?php

namespace sndsgd\fs\entity;

interface EntityInterface
{
    /**
     * Get the path as a string
     * 
     * @return string
     */
    public function __toString(): string;

    /**
     * Determine whether the entity is a directory
     * 
     * @return bool
     */
    public function isDir(): bool;

    /**
     * Determine whether the entity is a file
     * 
     * @return bool
     */
    public function isFile(): bool;

    /**
     * Get the path as a string
     * 
     * @return string
     */
    public function getPath(): string;

    /**
     * Perform type/permissions tests on an entity
     *
     * @param int $opts
     * @return bool
     */
    public function test(int $opts): bool;

    /**
     * Determine if a path can be written to
     * 
     * @return bool
     */
    public function canWrite();

    /**
     * Prepare an entity for writing by creating non existing parents
     * 
     * @param  integer $mode The octal permissions value for directories
     * @return bool
     */
    public function prepareWrite($mode = 0775);

    /**
     * Get the parent directory
     * 
     * @return sndsgd\fs\Dir|null
     * @return sndsgd\fs\Dir The parent directory
     * @return null The entity has no parent
     */
    public function getParent();

    /**
     * Determine whether or not a path is absolute
     * 
     * @return bool
     */
    public function isAbsolute(): bool;

    /**
     * Normalize a path to remove dots
     * 
     * @return \sndsgd\fs\EntityInterface
     */
    public function normalize(): EntityInterface;

    /**
     * Normalize the path to a directory
     *
     * @param string $dir
     * @return \sndsgd\fs\EntityInterface|string
     */
    public function normalizeTo($dir);

    /**
     * Get the relative path from the current path to another
     * 
     * @param string $path
     * @return string
     */
    public function getRelativePath($path): string;
}
