<?php

namespace sndsgd\fs;

use \org\bovigo\vfs\vfsStream;
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
      $dir = new Dir(vfsStream::url("root/emptydir"));
      $this->assertTrue($dir->test(Dir::EXISTS));

      $path = vfsStream::url("root/file.txt");
      $dir = new Dir($path);
      $this->assertFalse($dir->test(Dir::EXISTS));
      $this->assertEquals("'{$path}' is not a directory", $dir->getError());

      $dir = new Dir(vfsStream::url("root/dir.--x"));
      $this->assertFalse($dir->test(File::READABLE));
      $this->assertFalse($dir->test(File::WRITABLE));
      $this->assertFalse($dir->test(File::EXECUTABLE));
   }

   /**
    * @covers ::canWrite
    */
   public function testCanWrite()
   {
      $dir = new Dir(vfsStream::url("root/test"));
      $this->assertTrue($dir->canWrite());

      $dir = new Dir(vfsStream::url("root/a/new/path"));
      $this->assertTrue($dir->canWrite());
   }

   /**
    * @covers ::prepareWrite
    */
   public function testPrepareWrite()
   {
      $dir = new Dir(vfsStream::url("root/test"));
      $this->assertTrue($dir->prepareWrite());

      $path = vfsStream::url("root/test/a/new/dir");
      $this->assertFalse(file_exists($path));
      $dir = new Dir($path);
      $this->assertTrue($dir->prepareWrite());
      $this->assertTrue(file_exists($path));

      $dir = new Dir(vfsStream::url("root/file.txt/nope"));
      $this->assertFalse($dir->prepareWrite());

      $dir = new Dir(vfsStream::url("root/dir.--x/nope"));
      $this->assertFalse($dir->prepareWrite());
   }

   /**
    * @covers ::getFile
    */
   public function testGetFile()
   {
      $dir = new Dir("/test/dir");
      $file = $dir->getFile("file.txt");
      $this->assertInstanceOf("sndsgd\\fs\\File", $file);
      $this->assertEquals("/test/dir/file.txt", $file->getPath());
   }

   /**
    * @covers ::getFile
    * @expectedException InvalidArgumentException
    */
   public function testGetFileException()
   {
      $dir = new Dir("/test/dir");
      $file = $dir->getFile([]);
   }

   /**
    * @covers ::isEmpty
    */
   public function testIsEmpty()
   {
      $dir = new Dir(vfsStream::url("root"));
      $this->assertFalse($dir->isEmpty());

      $dir = new Dir(vfsStream::url("root/test/emptydir"));
      $this->assertTrue($dir->isEmpty());
   }

   /**
    * @covers ::isEmpty
    * @expectedException Exception
    */
   public function testIsEmptyException()
   {
      $dir = new Dir(vfsStream::url("root/dir.--x"));
      $dir->isEmpty();
   }

   /**
    * @covers ::getList
    */
   public function testGetListAsStrings()
   {
      $dir = new Dir(vfsStream::url("root"));
      $files = $dir->getList(true);
      $this->assertTrue(is_array($files));

      foreach ($files as $name) {
         $path = "{$dir}/$name";
         $this->assertTrue(file_exists($path));
      }
   }

   /**
    * @covers ::getList
    */
   public function testGetListAsEntities()
   {
      $dir = new Dir(vfsStream::url("root"));
      $files = $dir->getList();
      $this->assertTrue(is_array($files));

      foreach ($files as $entity) {
         $this->assertTrue(file_exists($entity));
      }
   }

   /**
    * @covers ::remove
    */
   public function testRemove()
   {
      $dir = new Dir(vfsStream::url("root/test"));
      $this->assertTrue($dir->remove());
   }

   /**
    * @covers ::remove
    */
   public function testRemoveNonExistingPath()
   {
      $dir = new Dir(vfsStream::url("root/does/not/exist"));
      $this->assertFalse($dir->remove());
   }

   /**
    * @covers ::remove
    */
   public function testRemoveInaccessibleCurrentPath()
   {
      $vdir = vfsStream::newDirectory("cannot-write")
         ->at($this->root)
         ->chmod(0755)
         ->chgrp(vfsStream::GROUP_ROOT)
         ->chown(vfsStream::OWNER_ROOT);

      $testdir = vfsStream::newDirectory("cannot-delete")
         ->at($vdir)
         ->chmod(0755)
         ->chgrp(vfsStream::GROUP_ROOT)
         ->chown(vfsStream::OWNER_ROOT);
      
      $mock = $this->getMockBuilder("sndsgd\\fs\\Dir")
         ->setConstructorArgs([ vfsStream::url($testdir->path()) ])
         ->setMethods(["test"])
         ->getMock();

      $mock->method("test")->willReturn(true);
      $this->assertFalse($mock->remove());
   }

   /**
    * @covers ::remove
    */
   public function testRemoveInaccessibleFile()
   {
      $vdir = vfsStream::newDirectory("cannot-write")
         ->at($this->root)
         ->chmod(0755)
         ->chgrp(vfsStream::GROUP_ROOT)
         ->chown(vfsStream::OWNER_ROOT);
      
      $vfile = vfsStream::newFile("cannot-delete.txt")
         ->at($vdir)
         ->chmod(0700)
         ->chgrp(vfsStream::GROUP_ROOT)
         ->chown(vfsStream::OWNER_ROOT);   
      

      $mock = $this->getMockBuilder("sndsgd\\fs\\Dir")
         ->setConstructorArgs([ vfsStream::url($vdir->path()) ])
         ->setMethods(["test"])
         ->getMock();

      $mock->method("test")->willReturn(true);

      $this->assertFalse($mock->remove());
   }

   /**
    * @covers ::remove
    */
   public function testRemoveInaccessibleDir()
   {
      $vdir = vfsStream::newDirectory("cannot-write")
         ->at($this->root)
         ->chmod(0755)
         ->chgrp(vfsStream::GROUP_ROOT)
         ->chown(vfsStream::OWNER_ROOT);
      
      $vfile = vfsStream::newDirectory("cannot-delete")
         ->at($vdir)
         ->chmod(0755)
         ->chgrp(vfsStream::GROUP_ROOT)
         ->chown(vfsStream::OWNER_ROOT);   
      

      $mock = $this->getMockBuilder("sndsgd\\fs\\Dir")
         ->setConstructorArgs([ vfsStream::url($vdir->path()) ])
         ->setMethods(["test"])
         ->getMock();

      $mock->method("test")->willReturn(true);

      $this->assertFalse($mock->remove());
   }

}

