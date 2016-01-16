<?php

namespace sndsgd\fs;

use \Exception;
use \InvalidArgumentException;


class File extends EntityAbstract
{
    /**
     * Format a bytesize into a human readable string
     * 
     * @param integer $bytes The number fo bytes to format
     * @param integer $precision The number of decimal places to round to
     * @param string $point Decimal point
     * @param string $sep Thousands separator
     * @return string
     */
    public static function formatSize(
        $bytes, 
        $precision = 0, 
        $point = '.', 
        $sep = ','
    )
    {
        if (!is_int($bytes)) {
            throw new InvalidArgumentException(
                "invalid value provided for 'bytes'; ".
                "expecting a number of bytes as an integer"
            );
        }
        else if (!is_int($precision)) {
            throw new InvalidArgumentException(
                "invalid value provided for 'precision'; ".
                "expecting the number of decimal places as an integer or null"
            );
        }

        $i = 0;
        $sizes = ['bytes','KB','MB','GB','TB','PB','EB'];
        while ($bytes > 1024) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $precision, $point, $sep).' '.$sizes[$i];
    }

    /**
     * Create a temp file
     *
     * @param string $prefix A prefix for the 
     * @param  integer $mode   [description]
     * @return [type]          [description]
     */
    public static function createTemp($prefix)
    {
        $path = tempnam(Temp::getDir(), $prefix);
        if (!$path) {
            throw new RuntimeException("failed to create temp file");
        }

        $file = new self($path);
        Temp::registerEntity($file);
        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function test($opts)
    {
        return parent::test($opts | self::FILE);
    }

    /**
     * {@inheritdoc}
     */
    public function canWrite()
    {
        if (file_exists($this->path)) {
            return $this->test(self::WRITABLE);
        }
        return (($dir = $this->getParent()) !== null && $dir->canWrite());
    }

    /**
     * {@inheritdoc}
     */
    public function prepareWrite($mode = 0775)
    {
        if (file_exists($this->path)) {
            return $this->test(self::WRITABLE);
        }

        $dir = $this->getParent();
        if ($dir->prepareWrite($mode) === false) {
            $this->error = 
                "failed to prepare '{$this->path}' for writing; ".$dir->getError();
            return false;
        }
        return true;
    }

    /**
     * Get the file's parent directory
     *
     * @aliasof ::getParent
     * @return \sndsgd\fs\Dir
     */
    public function getDir()
    {
        return $this->getParent();
    }

    /**
     * @param string $default The value to return when no extension exists
     * @return string|null
     */
    public function getExtension($default = null)
    {
        $filename = basename($this->path);
        $extpos = strrpos($filename, ".");
        return ($extpos === false || $extpos === 0)
            ? $default 
            : substr($filename, $extpos + 1);
    }

    /**
     * Separate a filename and extension
     * 
     * bug (??) with pathinfo(): 
     * [http://bugs.php.net/bug.php?id=67048](http://bugs.php.net/bug.php?id=67048)
     * 
     * Example Usage:
     * <code>
     * $path = '/path/to/file.txt';
     * list($name, $ext) = File::splitName($path);
     * // => ['file', 'txt']
     * $ext = File::splitName($path)[1];
     * // => 'txt'
     * </code>
     * 
     * @param string|null $defaultExtension
     * @return array
     * - [0] string name
     * - [1] string|null extension
     */
    public function splitName($defaultExtension = null)
    {
        $filename = basename($this->path);
        $extpos = strrpos($filename, ".");
        if ($extpos === false || $extpos === 0) {
            $name = $filename;
            $ext = $defaultExtension;
        }
        else {
            $name = substr($filename, 0, $extpos);
            $ext = substr($filename, $extpos + 1);
        }
        return [$name, $ext];
    }

    /**
     * Get the filesize
     * 
     * @param integer $precision The number of decimal places to return
     * @param string $point The decimal point
     * @param string $sep The thousands separator
     * @return string|integer|false
     * @return string|integer The formatted filesize
     * @return false If the file could not read or the stat failed
     */
    public function getSize($precision = 0, $point = ".", $sep = ",")
    {
        if ($this->test(self::READABLE) !== true) {
            $this->error = "failed to stat filesize; {$this->error}";
            return false;
        }
        else if (($bytes = @filesize($this->path)) === false) {
            $this->setError("failed to stat filesize for '{$this->path}'");
            return false;
        }

        return ($precision === 0)
            ? $bytes
            : self::formatSize($bytes, $precision, $point, $sep);
    }

    /**
     * Prepare and write to the file
     * 
     * @param string $contents
     * @param integer $opts Bitmask options to pass to `file_put_contents`
     * @return boolean
     */
    public function write($contents, $opts = 0)
    {
        if ($this->prepareWrite() !== true) {
            $this->error = "failed to write '{$this->path}; {$this->error}";
            return false;
        }
        return $this->writeFile($contents, $opts);
    }

    /**
     * @param string $contents
     * @param integer $opts 
     * @return boolean
     */
    private function writeFile($contents, $opts)
    {
        if (@file_put_contents($this->path, $contents, $opts) === false) {
            $this->setError("file to write '{$this->path}'");
            return false;
        }
        return true;
    }

    /**
     * Prepend contents to a file
     * 
     * @param string $contents The content to prepend to the file
     * @param integer $maxMemory The max number of bytes to consume
     * @return boolean
     */
    public function prepend($contents, $maxMemory = 8096)
    {
        $test = self::EXISTS | self::READABLE | self::WRITABLE;
        if ($this->test($test) === false) {
            $this->error = "failed to prepend file; {$this->error}";
            return false;
        }

        $len = strlen($contents);
        $size = filesize($this->path);
        $endsize = $len + $size;

        # if the overall filesize is greater than `maxMemory`, write efficiently
        if ($endsize > $maxMemory) {
            return $this->prependFileInPlace($contents, $len, $size, $endsize);
        }

        # use file_get/put_contents to handle the operation
        else if (($tmp = $this->readFile(-1)) === false) {
            return false;
        }
        else if ($this->writeFile($contents.$tmp, 0) === false) {
            return false;
        }
        return true;
    }

    /**
     * @param string $contents
     * @param integer $len
     * @param integer $size
     * @param integer $endsize
     * @return boolean
     */
    private function prependFileInPlace($contents, $len, $size, $endsize)
    {
        $fh = fopen($this->path, "r+");
        $oldcontent = fread($fh, $len);
        rewind($fh);

        $i = 1;
        while (ftell($fh) < $endsize) {
            fwrite($fh, $contents);
            $contents = $oldcontent;
            $oldcontent = fread($fh, $len);
            fseek($fh, $i * $len);
            $i++;
        }
        return true;
    }

    /**
     * Convenience method for appending to a file
     * 
     * @param string $contents The contents to append
     * @return boolean
     */
    public function append($contents)
    {
        return $this->write($contents, FILE_APPEND);
    }

    /**
     * Read the contents of the file
     *
     * @param integer $offset The position to start reading from
     * @return boolean|string
     * @return string The contents of the file on success
     * @return false If the file could not be read
     */
    public function read($offset = -1)
    {
        if ($this->test(self::EXISTS | self::READABLE) === false) {
            $this->error = "failed to read file; {$this->error}";
            return false;
        }
        return $this->readFile($offset);
    }

    /**
     * @param integer $offset
     * @return string|false
     */
    private function readFile($offset)
    {
        $ret = @file_get_contents($this->path, false, null, $offset);
        if ($ret === false) {
            $this->setError("read operation failed on '{$this->path}'");
            return false;
        }
        return $ret;
    }

    /**
     * Delete the file
     * 
     * @return boolean
     */
    public function remove()
    {
        if (@unlink($this->path) === false) {
            $this->setError("failed to delete '{$this->path}");
            return false;
        }
        return true;
    }

    /**
     * Get the number of lines in the file
     * 
     * @return integer
     */
    public function getLineCount()
    {
        $ret = 0;
        $fh = fopen($this->path, "r");
        while (!feof($fh)) {
            $buffer = fread($fh, 8192);
            $ret += substr_count($buffer, PHP_EOL);
        }
        fclose($fh);
        return $ret;
    }
}
