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
     * @var array<string,\sndsgd\fs\entity\EntityInterface>
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
            $dir = new entity\DirEntity($path);
            if (!$dir->test(\sndsgd\Fs::READABLE | \sndsgd\Fs::WRITABLE)) {
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
     * @param int $mode The permissions for the new directory
     * @param int $maxAttempts The max number of times to call mkdir
     * @return \sndsgd\fs\Dir
     */
    public static function createDir(
        string $prefix = "tmp",
        int $mode = 0777,
        int $maxAttempts = 10
    ): entity\DirEntity
    {
        $tmpdir = static::getDir();
        $prefix = \sndsgd\Fs::sanitizeName($prefix);
        $attempts = 0;
        do {
            $attempts++;
            if ($attempts > $maxAttempts) {
                throw new \RuntimeException(
                    "failed to create temp directory; ".
                    "reached max number ($maxAttempts) of attempts"
                );
            }
            $rand = \sndsgd\Str::random(10);
            $path = "$tmpdir/$prefix-$rand";
        }
        while (@mkdir($path, $mode) === false);

        $dir = new entity\DirEntity($path);
        static::registerEntity($dir);
        return $dir;
    }

    /**
     * Create a temp file
     *
     * @param string $prefix A prefix for the filename
     * @return \sndsgd\fs\entity\FileEntity
     */
    public static function createFile(
        string $name,
        int $maxAttempts = 10
    ): entity\FileEntity
    {
        $tmpdir = static::getDir();
        $name = \sndsgd\Fs::sanitizeName($name);
        $pos = strrpos($name, ".");
        if ($pos === false) {
            $extension = "";
        } else {
            $extension = substr($name, $pos);
            $name = substr($name, 0, $pos);
        }

        $attempts = 1;
        do {
            if ($attempts > $maxAttempts) {
                throw new \RuntimeException(
                    "failed to create temp file; ".
                    "reached max number ($maxAttempts) of attempts"
                );
            }
            $rand = \sndsgd\Str::random(10);
            $path = "$tmpdir/$name-$rand$extension";
            $attempts++;
        }
        while (file_exists($path));
        touch($path);
        $file = new entity\FileEntity($path);
        static::registerEntity($file);
        return $file;
    }

    /**
     * Register an entity to be deleted when the script exits
     *
     * @param \sndsgd\fs\entity\EntityInterface $entity
     */
    protected static function registerEntity(entity\EntityInterface $entity)
    {
        if (count(self::$entities) === 0) {
            register_shutdown_function("sndsgd\\fs\\Temp::cleanup");
        }
        self::$entities[$entity->getPath()] = $entity;
    }

    /**
     * Remove all temp files & directories created since script start
     *
     * @return bool Indicates whether all files were successfully removed
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
