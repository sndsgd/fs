<?php

namespace sndsgd\fs;

use \Exception;


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
}

