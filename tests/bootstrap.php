<?php

namespace sndsgd\fs;

use \org\bovigo\vfs\vfsStream;


require __DIR__."/../vendor/autoload.php";


class TestCase extends \PHPUnit_Framework_TestCase
{
   protected $root;

   protected function setUp()
   {
      $this->root = vfsStream::setup("root");
      vfsStream::create([
         "file.txt" => "contents...",
         "test" => [
            "file.txt" => "contents...",
            "emptydir" => []
         ],
         "file-no-rw" => "contents...",
         "dir-no-rw" => []
      ]);

      $this->root->getChild("file-no-rw")
         ->chmod(0700)
         ->chgrp(vfsStream::GROUP_ROOT)
         ->chown(vfsStream::OWNER_ROOT);

      $this->root->getChild("dir-no-rw")
         ->chmod(0711)
         ->chgrp(vfsStream::GROUP_ROOT)
         ->chown(vfsStream::OWNER_ROOT);
   }

   protected function getPath($path)
   {
      return vfsStream::url($path);
   }

   protected function setQuota($bytes = 0)
   {
      vfsStream::setQuota($bytes);
   }
}



class VfsStreamFailFile extends \org\bovigo\vfs\vfsStreamFile
{
   public $fail = null;

   public function getContent()
   {
      var_dump("get content");
      return ($this->fail === true) ? null : parent::getContent();
   }

   public function open()
   {
      var_dump($this->fail);
      if ($this->fail == "open") {
         var_dump('triggering error');
         trigger_error("failed to open file", E_USER_WARNING);
      }
      else {
         parent::open();   
      }
   }
}


