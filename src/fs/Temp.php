<?php

namespace sndsgd\fs;

/**
 * Temp file and directory utility methods
 *
 * Note: the creation of both directores and files is done from this class
 * so any created paths can be registered for deletion. Otherwise you would 
 * be able to register any filesystem entity to be deleted on script exit.
 */
class Temp
{
    /**
     * The directory to use as the temp directory
     *
     * @var string
     */
    private static $dir = "";

    /**
     * All created paths are added here for easy removal at script exit
     *
     * @var array<string,boolean|null>
     */
    private static $entities = [];

    /**
     * Set the root temp directory (overrides use of the system temp directory)
     *
     * @param string $path
     * @return void
     * @throws InvalidArgumentException If the provided path isn't usable
     */
    public static function setDir(string $path = "")
    {
        if ($path !== "") {
            $dir = new Dir($path);
            if (!$dir->test(Dir::READABLE | Dir::WRITABLE)) {
                throw new \InvalidArgumentException(
                    "invalid value provided for 'path'; ".$dir->getError()
                );
            }
        }
        self::$dir = $path;
    }

    /**
     * Get the root temp directory
     *
     * @return string
     */
    public static function getDir(): string
    {
        return (self::$dir === "") ? sys_get_temp_dir() : self::$dir;
    }

    /**
     * Create a temp directory
     *
     * @param string $prefix A directory name prefix
     * @param int $maxAttempts The max number of times to call mkdir
     * @return \sndsgd\fs\Dir
     */
    public static function createDir(
        string $prefix = "tmp",
        int $maxAttempts = 10
    ): Dir
    {
        $tmpdir = static::getDir();
        $prefix = \sndsgd\Fs::sanitizeName($prefix);
        $attempts = 1;
        do {
            if ($attempts > $maxAttempts) {
                throw new \RuntimeException(
                    "failed to create temp directory; ".
                    "reached max number ($maxAttempts) of attempts"
                );
            }
            $rand = \sndsgd\Str::random(10);
            $path = "$tmpdir/$prefix-$rand";
            $attempts++;
        }
        while (@mkdir($path, $mode) === false);

        $dir = new Dir($path);
        static::registerEntity($dir);
        return $dir;
    }

    /**
     * Create a temp file
     *
     * @param string $prefix A prefix for the filename
     * @return \sndsgd\fs\File
     */
    public static function createFile(
        string $prefix,
        int $maxAttempts = 10
    ): File
    {
        $tmpdir = static::getDir();
        $prefix = \sndsgd\Fs::sanitizeName($prefix);
        $attempts = 1;
        do {
            if ($attempts > $maxAttempts) {
                throw new \RuntimeException(
                    "failed to create temp file; ".
                    "reached max number ($maxAttempts) of attempts"
                );
            }
            $rand = \sndsgd\Str::random(10);
            $path = "$tmpdir/$prefix-$rand";
            $attempts++;
        }
        while (file_exists($path));
        touch($path);
        $file = new File($path);
        static::registerEntity($file);
        return $file;
    }

    /**
     * Register an entity to be deleted when the script exits
     *
     * @param \sndsgd\fs\EntityAbstract $entity
     */
    protected static function registerEntity(EntityAbstract $entity)
    {
        if (count(self::$entities) === 0) {
            register_shutdown_function("sndsgd\\fs\\Temp::cleanup");
        }
        self::$entities[$entity->getPath()] = $entity;
    }

    /**
     * Remove all temp files & directories created since script start
     *
     * @return boolean Indicates whether all files were successfully removed
     */
    public static function cleanup(): bool
    {
        $ret = true;
        foreach (self::$entities as $path => $entity) {
            if (!$entity->remove()) {
                $ret = false;
            }
        }
        self::$entities = [];
        return $ret;
    }
}
