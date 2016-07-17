<?php

namespace sndsgd\fs\file;

/**
 * A file reader that allows for iterating over its contents 
 * line by line in reverse
 */
class ReverseReader implements \Iterator
{
    /**
     * The absolute path to the file
     * 
     * @var string
     */
    protected $path;

    /**
     * The newline character or characters
     *
     * @var string
     */
    protected $newline;

    /**
     * The number of bytes to read when calling `fread()`
     *
     * @var int
     */
    protected $bytesPerRead;

    /**
     * A pointer to the file once it is opened
     * 
     * @var resource
     */
    protected $fp;

    /**
     * The bytesize of the file
     * 
     * @var integer
     */
    protected $filesize;

    /**
     * The current byte position offset
     *
     * @var integer
     */
    protected $pos = -1;

    /**
     * The portion of the file that is read into memory
     *
     * @var array<string>
     */
    protected $buffer = null;

    /**
     * The current line number
     *
     * @var integer
     */
    protected $lineNumber = -1;

    /**
     * The contents of the current line
     *
     * @var string
     */
    protected $value = null;

    /**
     * @param string $path The absolute path to the file to read
     * @param string $newline The newline character(s) to use
     */
    public function __construct(
        string $path,
        string $newline = PHP_EOL,
        int $bytesPerRead = 8192
    )
    {
        $this->path = $path;
        $this->newline = $newline;
        $this->bytesPerRead = $bytesPerRead;

        $this->fp = fopen($path, "r");
        $this->filesize = filesize($path);
    }

    /**
     * @see http://php.net/manual/en/class.iterator.php
     * @return string|null
     */
    public function current()
    {
        return $this->value;
    }

    /**
     * @see http://php.net/manual/en/class.iterator.php
     * @return int
     */
    public function key(): int
    {
        return $this->lineNumber;
    }

    /**
     * @see http://php.net/manual/en/class.iterator.php
     * @return void
     */
    public function next()
    {
        $this->lineNumber++;
        $this->value = $this->readline();
    }

    /**
     * @see http://php.net/manual/en/class.iterator.php
     * @return void
     */
    public function rewind()
    {
        if ($this->filesize > 0) {
            $this->pos = $this->filesize;
            $this->value = null;
            $this->lineNumber = -1;
            $bytesRemaining = $this->filesize % $this->bytesPerRead;
            $this->buffer = explode(
                $this->newline,
                $this->read($bytesRemaining ?: $this->bytesPerRead)
            );
            $this->next();
        }
    }

    /**
     * @see http://php.net/manual/en/class.iterator.php
     * @return bool
     */
    public function valid()
    {
        return $this->value !== null;
    }

    /**
     * @param integer $size The number of bytes to read
     * @return string
     */
    private function read(int $size): string
    {
        $this->pos -= $size;
        fseek($this->fp, $this->pos);
        return fread($this->fp, $size);
    }

    /**
     * @return string|null
     */
    private function readline()
    {
        while ($this->pos !== 0 && count($this->buffer) < 2) {
            $this->buffer = explode(
                $this->newline,
                $this->read($this->bytesPerRead).$this->buffer[0]
            );
        }
        return array_pop($this->buffer);
    }
}
