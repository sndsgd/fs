<?php

namespace sndsgd\fs;

use \Exception;
use \InvalidArgumentException;


class Dir extends EntityAbstract
{
   /**
    * {@inheritdoc}
    */
   public function test($opts)
   {
      return parent::test($opts | self::DIR);
   }

   /**
    * {@inheritdoc}
    */
   public function canWrite()
   {
      $path = $this->path;
      while (file_exists($path) === false) {
         $path = dirname($path);
      }
      $dir = new self($path);
      return $dir->test(self::WRITABLE);
   }

   /**
    * {@inheritdoc}
    */
   public function prepareWrite($mode = 0775)
   {
      if (file_exists($this->path)) {
         return $this->test(self::WRITABLE);
      }
      else if (mkdir($this->path, $mode, true) === false) {
         $this->setError("failed to create directory '{$this->path}'");
         return false;
      }
      else {
         return true;
      }
   }

   /**
    * Get an instance of \sndsgd\fs\File for a file in this directory
    * 
    * @param string $filename The name of the file in the directory
    * @return \sndsgd\fs\File
    */
   public function getFile($filename)
   {
      if (!is_string($filename)) {
         throw new InvalidArgumentException(
            "invalid value provided for 'filename'; ".
            "expecting a string"
         );
      }
      return new File($this->path.DIRECTORY_SEPARATOR.$filename);
   }

   /**
    * Determine if a directory is empty
    * 
    * @return boolean 
    * @throws Exception If the directory does not exist or is not readable
    */
   public function isEmpty()
   {
      if ($this->test(self::EXISTS | self::READABLE) === false) {
         throw new Exception(
            "failed to determine if a directory is empty; ".$this->getError()
         );
      }

      return count(scandir($this->path)) === 2;
   }

   /**
    * get a list of the directory children
    * 
    * @return array<string>|boolean
    */
   public function getList()
   {
      return array_diff(scandir($this->path), [".", ".."]);
   }

   /**
    * Recursively remove the directory
    * 
    * @return boolean
    */
   public function remove()
   { 
      if ($this->test(self::EXISTS | self::READABLE | self::WRITABLE) === false) {
         $this->error = "failed to remove directory; {$this->error}";
         return false;
      }

      foreach ($this->getList() as $name) {
         $path = "{$this->path}/$name";
         if (is_dir($path)) {
            $dir = new self($path);
            if ($dir->remove() === false) {
               $this->error = $dir->getError();
               return false;
            }
         }
         else if (@unlink($path) === false) {
            $this->setError("failed to remove file '$path'");
            return false;
         }
      }

      if (@rmdir($this->path) === false) {
         $this->setError("failed to remove directory '{$this->path}'");
         return false;
      }

      return true;
   }
}

