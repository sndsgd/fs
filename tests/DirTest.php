<?php

namespace sndsgd\fs;

use \sndsgd\Str;


/**
 * @coversDefaultClass \sndsgd\fs\Dir
 */
class DirTest extends TestCase
{
   /**
    * @covers ::test
    * @covers \sndsgd\fs\EntityAbstract::test
    */
   public function testTest()
   {
      $dir = new Dir($this->getPath("root/test"));
      $this->assertTrue($dir->test(Dir::EXISTS));

      $path = $this->getPath("root/test/file.txt");
      $dir = new Dir($path);
      $this->assertFalse($dir->test(Dir::EXISTS));
      $this->assertEquals("'{$path}' is not a directory", $dir->getError());

      $dir = new Dir($this->getPath("root/dir-no-rw"));
      $this->assertFalse($dir->test(File::READABLE));
      $this->assertFalse($dir->test(File::WRITABLE));
      $this->assertFalse($dir->test(File::EXECUTABLE));
   }

   /**
    * @covers ::canWrite
    */
   public function testCanWrite()
   {
      $dir = new Dir($this->getPath("root/test"));
      $this->assertTrue($dir->canWrite());

      $dir = new Dir($this->getPath("root/a/new/path"));
      $this->assertTrue($dir->canWrite());
   }

   /**
    * @covers ::prepareWrite
    */
   public function testPrepareWrite()
   {
      $dir = new Dir($this->getPath("root/test"));
      $this->assertTrue($dir->prepareWrite());

      $path = $this->getPath("root/test/a/new/dir");
      $this->assertFalse(file_exists($path));
      $dir = new Dir($path);
      $this->assertTrue($dir->prepareWrite());
      $this->assertTrue(file_exists($path));

      $dir = new Dir($this->getPath("root/file.txt/nope"));
      $this->assertFalse($dir->prepareWrite());

      $dir = new Dir($this->getPath("root/dir-no-rw/nope"));
      $this->assertFalse($dir->prepareWrite());
   }

   /**
    * @covers ::isEmpty
    */
   public function testIsEmpty()
   {
      $dir = new Dir($this->getPath("root"));
      $this->assertFalse($dir->isEmpty());

      $dir = new Dir($this->getPath("root/test/emptydir"));
      $this->assertTrue($dir->isEmpty());
   }

   /**
    * @covers ::isEmpty
    * @expectedException Exception
    */
   public function testIsEmptyException()
   {
      $dir = new Dir($this->getPath("root/dir-no-rw"));
      $dir->isEmpty();
   }
}

