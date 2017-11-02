<?php

namespace sndsgd;

use \sndsgd\fs\Dir;
use \sndsgd\fs\File;

/**
 * Filesystem utility methods
 */
class Fs
{
    const BYTES_PER_KB = 1024;
    const BYTES_PER_MB = 1048576;
    const BYTES_PER_GB = 1073741824;
    const BYTES_PER_TB = 1099511627776;
    const BYTES_PER_PB = 1125899906842624;
    const BYTES_PER_EB = 1152921504606846976;

    # bitmask values for use in sndsgd\fs\entity\EntityInterface::test()
    const EXISTS = 1;
    const DIR = 2;
    const FILE = 4;
    const READABLE = 8;
    const WRITABLE = 16;
    const EXECUTABLE = 32;

    /**
     * Format a bytesize into a human readable string
     * Note: precision will be ignored for results less than a KB
     *
     * @param int $bytes The number fo bytes to format
     * @param int $precision The number of decimal places to round to
     * @param string $point Decimal point
     * @param string $sep Thousands separator
     * @return string
     */
    public static function formatSize(
        int $bytes,
        int $precision = 0,
        string $point = ".",
        string $sep = ","
    ): string
    {
        $i = 0;
        $sizes = ["bytes", "KB", "MB", "GB", "TB", "PB", "EB"];
        while ($bytes > 1024) {
            $bytes /= 1024;
            $i++;
        }

        if ($i === 0) {
            $precision = 0;
        }

        return number_format($bytes, $precision, $point, $sep)." ".$sizes[$i];
    }

    /**
     * Remove wonky characters from a path name
     *
     * @param string $name The basename to sanitize
     * @return string
     */
    public static function sanitizeName(string $name): string
    {
        $basename = basename($name);
        $dir = ($basename === $name) ? null : dirname($name);
        $basename = preg_replace("/[^a-zA-Z0-9-_.]/", "_", $basename);
        return ($dir === null) ? $basename : "$dir/$basename";
    }

    /**
     * Convenience method to get a directory instance with a normalized path
     *
     * @param string $path
     * @return \sndsgd\fs\entity\DirEntity
     */
    public static function dir(string $path): fs\entity\DirEntity
    {
        return (new fs\entity\DirEntity($path))->normalize();
    }

    /**
     * Convenience method to get a file instance with a normalized path
     *
     * @param string $path
     * @return \sndsgd\fs\entity\FileEntity
     */
    public static function file(string $path): fs\entity\FileEntity
    {
        return (new fs\entity\FileEntity($path))->normalize();
    }

    /**
     * Create an entity from an instance of \SplFileInfo
     *
     * @param \SplFileInfo $info
     * @return \sndsgd\fs\entity\EntityInterface
     */
    public static function createFromSplFileInfo(\SplFileInfo $info)
    {
        $realpath = $info->getRealPath();
        if ($info->isFile()) {
            return new fs\entity\FileEntity($realpath);
        }
        return new fs\entity\DirEntity($realpath);
    }
}
