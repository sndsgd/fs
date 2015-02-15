<?php

namespace sndsgd\fs;


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
}

