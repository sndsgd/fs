<?php

namespace sndsgd\fs;

/**
 * Temp file and directory utility methods
 */
class Temp
{
    /**
     * The directory to use as the temp directory
     *
     * @var string|null
     */
    private static $dir = null;

    /**
     * All created paths are added here for easy removal at script exit
     *
     * @var array<string,boolean|null>
     */
    private static $files = [];

    /**
     * Set the root temp directory (overrides use of the system temp directory)
     *
     * @param string|null $path
     * @return void
     * @throws InvalidArgumentException If the provided path isn't usable
     */
    public static function setDir($path)
    {
        if ($path === null) {
            self::$dir = null;
            return;
        }

        if (!is_string($path)) {
            throw new \InvalidArgumentException(
                "invalid value provided for 'path'; ".
                "expecting an absolute directory path as string"
            );
        }

        $dir = new Dir($path);
        if (!$dir->test(Dir::READABLE | Dir::WRITABLE)) {
            throw new \InvalidArgumentException(
                "invalid value provided for 'path'; ".$dir->getError()
            );
        }

        self::$dir = $path;
    }

    /**
     * Get the root temp directory
     *
     * @return string
     */
    public static function getDir()
    {
        return (self::$dir !== null) ? self::$dir : sys_get_temp_dir();
    }

    /**
     * Register an entity to be deleted when the script exits
     *
     * @param \sndsgd\fs\EntityAbstract $entity
     */
    public static function registerEntity(EntityAbstract $entity)
    {
        if (count(self::$entities) === 0) {
            register_shutdown_function("sndsgd\\fs\\Temp::cleanup");
        }
        self::$entities[$entity->getPath()] = $entity;
    }

    /**
     * Deregister an entity from the list to remove when the script exits
     *
     * @param string $path The path to remove
     * @return boolean Whether or not the path was deregistered
     */
    public static function deregisterEntity(EntityAbstract $entity)
    {
        $path = $entity->getPath();
        if (isset(self::$entities[$path])) {
            unset(self::$entities[$path]);
            return true;    
        }
        return false;
    }

    /**
     * Create a temp directory
     *
     * @param string $prefix A prefix for the directory name
     * @param octal $mode The permissions for the directory
     * @return string The path to the newly created temp directory
     */
    public static function dir($prefix = "temp", $mode = 0775)
    {
        $tmpdir = self::getDir();
        $prefix = Dir::sanitizeName($prefix);
        do {
            $rand = substr(md5(microtime(true)), 0, 6);
            $path = "$tmpdir/$prefix-$rand";
        }
        while (@mkdir($path, $mode) === false);

        self::registerPath($path, true);
        return $path;
    }

    /**
     * Remove all temp files & directories created since script start
     *
     * @return boolean
     */
    public static function cleanup()
    {
        $ret = true;
        foreach (self::$files as $path => $entity) {
            if (!$entity->remove()) {
                $ret = false;
            }
        }
        return $ret;
    }
}
