<?php

namespace sndsgd\fs\locator;

interface LocatorInterface
{
    public function searchDir(
        string $dir, 
        bool $recursive = false
    ): LocatorInterface;
}
