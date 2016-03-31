<?php

namespace sndsgd\fs;


class File extends EntityAbstract
{
    /**
     * {@inheritdoc}
     */
    public function test(int $opts): bool
    {
        return parent::test($opts | self::FILE);
    }

    /**
     * {@inheritdoc}
     */
    public function canWrite(): bool
    {
        if (file_exists($this->path)) {
            return $this->test(self::WRITABLE);
        }
        return (($dir = $this->getParent()) !== null && $dir->canWrite());
    }

    /**
     * {@inheritdoc}
     */
    public function prepareWrite($mode = 0775): bool
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
    public function getDir(): Dir
    {
        return $this->getParent();
    }

    /**
     * @param string $default The value to return when no extension exists
     * @return string
     */
    public function getExtension(string $default = ""): string
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
     * @param string $defaultExtension
     * @return array
     * - [0] string name
     * - [1] string|null extension
     */
    public function splitName(string $defaultExtension = ""): array
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
     * Get the byte size of the file
     *
     * @return int `-1` if the size could not be determined
     */
    public function getByteSize(): int
    {
        if ($this->test(self::READABLE) !== true) {
            $this->error = "failed to stat filesize; {$this->error}";
            return -1;
        }
        $bytes = @filesize($this->path);
        if ($bytes === false) {
            $this->setError("failed to stat filesize for '{$this->path}'");
            return -1;
        }
        return $bytes;
    }

    /**
     * Get the filesize as a formatted string
     * 
     * @param int $precision The number of decimal places to return
     * @param string $point The decimal point
     * @param string $sep The thousands separator
     * @return string An empty string if the size could not be determined
     */
    public function getSize(
        int $precision = 0,
        string $point = ".",
        string $sep = ","
    ): string
    {
        $bytes = $this->getByteSize();
        if ($bytes === -1) {
            return "";
        }
        return \sndsgd\Fs::formatSize($bytes, $precision, $point, $sep); 
    }

    /**
     * Prepare and write to the file
     * 
     * @param string $contents
     * @param int $opts Bitmask options to pass to `file_put_contents`
     * @return bool
     */
    public function write($contents, $opts = 0): bool
    {
        if ($this->prepareWrite() !== true) {
            $this->error = "failed to write '{$this->path}; {$this->error}";
            return false;
        }
        return $this->writeFile($contents, $opts);
    }

    /**
     * @param string $contents
     * @param int $opts 
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
     * @param int $maxMemory The max number of bytes to consume
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
            return $this->prependFileInPlace($contents, $len, $endsize);
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
     * @param int $len
     * @param int $endsize
     * @return boolean
     */
    private function prependFileInPlace($contents, $len, $endsize)
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
     * @param int $offset The position to start reading from
     * @return boolean|string
     * @return string The contents of the file on success
     * @return false If the file could not be read
     */
    public function read(int $offset = -1)
    {
        if ($this->test(self::EXISTS | self::READABLE) === false) {
            $this->error = "failed to read file; {$this->error}";
            return false;
        }
        return $this->readFile($offset);
    }

    /**
     * @param int $offset
     * @return string|false
     */
    private function readFile(int $offset)
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
    public function remove(): bool
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
     * @return int
     */
    public function getLineCount():int
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
