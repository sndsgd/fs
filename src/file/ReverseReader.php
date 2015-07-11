<?php

namespace sndsgd\fs\file;


class ReverseReader implements \Iterator
{
   const BUFFER_SIZE = 8192;
   const NEWLINE = PHP_EOL;

   /**
    * The absolute path to the file
    * 
    * @var string
    */
   protected $path;

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
    */
   public function __construct($path)
   {
      $this->path = $path;
      $this->fp = fopen($path, "r");
      $this->filesize = filesize($path);
   }

   /**
    * @see http://php.net/manual/en/class.iterator.php
    * @return ?string
    */
   public function current()
   {
      return $this->value;
   }

   /**
    * @see http://php.net/manual/en/class.iterator.php
    * @return integer
    */
   public function key()
   {
      return $this->lineNumber;
   }

   /**
    * @see http://php.net/manual/en/class.iterator.php
    * @return ?string
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
         $this->buffer = explode(
            self::NEWLINE,
            $this->read($this->filesize % self::BUFFER_SIZE ?: self::BUFFER_SIZE)
         );
         $this->next();
      }
   }

   /**
    * @see http://php.net/manual/en/class.iterator.php
    * @return boolean
    */
   public function valid()
   {
      return $this->value !== null;
   }

   /**
    * @param integer $size The number of bytes to read
    * @return string
    */
   private function read($size)
   {
      $this->pos -= $size;
      fseek($this->fp, $this->pos);
      return fread($this->fp, $size);
   }

   /**
    * @return string
    */
   private function readline()
   {
      while ($this->pos !== 0 && count($this->buffer) < 2) {
         $this->buffer = explode(
            self::NEWLINE,
            $this->read(self::BUFFER_SIZE).$this->buffer[0]
         );
      }
      return array_pop($this->buffer);
   }
}
