<?php

namespace sndsgd\fs\locator;

abstract class LocatorAbstract implements LocatorInterface
{
    protected function getIterator(
        string $dir,
        bool $recursive = false
    ): \Iterator
    {
        $dir = \sndsgd\Fs::dir($dir);
        if (!$dir->test(\sndsgd\Fs::EXISTS | \sndsgd\Fs::READABLE)) {
            throw new \RuntimeException(
                "failed to search directory; ".$dir->getError()
            );
        }

        $opts = \RecursiveDirectoryIterator::SKIP_DOTS;
        $iterator = new \RecursiveDirectoryIterator($dir, $opts);
        if ($recursive) {
            $opts = \RecursiveIteratorIterator::SELF_FIRST;
            $iterator = new \RecursiveIteratorIterator($iterator, $opts);
        }

        return $iterator;
    }
}
