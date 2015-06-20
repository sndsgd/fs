<?php

namespace sndsgd\fs;

use \org\bovigo\vfs\vfsStream;
use \sndsgd\Str;


/**
 * @coversDefaultClass \sndsgd\fs\File
 */
class FileTest extends TestCase
{
   /**
    * @covers ::get
    * @covers \sndsgd\fs\EntityAbstract::get
    */
   public function testGet()
   {
      $file = File::get("./some/file.txt");
      $expect = getcwd()."/some/file.txt";
      $this->assertEquals($expect, $file->getPath());
   }

   /**
    * @covers ::formatSize
    * @expectedException InvalidArgumentException
    */
   public function testFormatSizeInvalidBytes()
   {
      File::formatSize("string");
   }

   /**
    * @covers ::formatSize
    * @expectedException InvalidArgumentException
    */
   public function testFormatSizeInvalidPrecision()
   {
      File::formatSize(1234, "string");
   }

   /**
    * @covers ::formatSize
    */
   public function testFormatSize()
   {
      $this->assertEquals("572.0 MB", File::formatSize(599785472, 1));
      $this->assertEquals("1 MB", File::formatSize(1234567, 0));
      $this->assertEquals("1.18 MB", File::formatSize(1234567, 2));
      $this->assertEquals("1.1498 GB", File::formatSize(1234567890, 4));
   }

   /**
    * @covers nothing
    */
   protected function createTestFile()
   {
      $rand= Str::random(10);
      $path = vfsStream::url("root/$rand.txt");
      $fp = fopen($path, "w");
      $bytes = 0;
      $len = rand(100, 200);
      for ($i=0; $i<$len; $i++) {
         $bytes += fwrite($fp, Str::random(rand(5000, 10000)).PHP_EOL);
      }
      fclose($fp);
      return [$path, $bytes, $len];
   }

   /**
    * @covers ::test
    * @covers \sndsgd\fs\EntityAbstract::test
    */
   public function testTest()
   {
      $file = new File(vfsStream::url("root/does/not/exist.txt"));
      $this->assertFalse($file->test(File::EXISTS));

      $file = new File(vfsStream::url("root/file.rw-"));
      $this->assertTrue($file->test(File::READABLE));

      $file = new File(vfsStream::url("root/dir.rwx"));
      $this->assertFalse($file->test(File::READABLE));

      $file = new File(vfsStream::url("root/file.---"));
      $this->assertFalse($file->test(File::READABLE));
      $this->assertFalse($file->test(File::WRITABLE));
      $this->assertFalse($file->test(File::EXECUTABLE));
   }

   /**
    * @covers ::prepareWrite
    */
   public function testPrepareWrite()
   {
      $file = new File(vfsStream::url("root/file.rw-"));
      $this->assertTrue($file->prepareWrite());
      $this->assertNull($file->getError());

      $file = new File(vfsStream::url("root/dir.rwx/file.txt"));
      $this->assertTrue($file->prepareWrite());
      $this->assertNull($file->getError());

      $file = new File(vfsStream::url("root/dir.--x/file.txt"));
      $this->assertFalse($file->prepareWrite());
      $this->assertTrue(is_string($file->getError()));
   }

   /**
    * @covers ::getDir
    * @covers ::getParent
    */
   public function testGetDir()
   {
      $file = new File("/test/dir/file.txt");
      $dir = $file->getDir();
      $this->assertInstanceOf("sndsgd\\fs\\Dir", $dir);
      $this->assertEquals("/test/dir", $dir->getPath());
   }

   /**
    * @covers ::getSize
    * @expectedException
    */
   public function testGetSizeFailure()
   {
      $path = vfsStream::url("root/dir.--x/file.txt");
      $file = new File($path);
      $this->assertFalse($file->getSize());
   }

   /**
    * @covers ::getSize
    */
   public function testGetSizeFailureDoesntExist()
   {
      $path = vfsStream::url("root/does/not/exist.txt");
      $file = new File($path);
      $file->getSize();
   }

   /**
    * @covers ::getSize
    */
   public function testGetSizeException()
   {
      $mock = $this->getMockBuilder("sndsgd\\fs\\File")
         ->setConstructorArgs([ vfsStream::url("root/dir-no-rw/file.txt") ])
         ->setMethods(["test"])
         ->getMock();

      $mock->method("test")->willReturn(true);
      $this->assertFalse($mock->getSize());
   }

   /**
    * @covers ::getSize
    */
   public function testGetSize()
   {
      $path = vfsStream::url("root/test.txt");
      $file = new File($path);
      $file->write(Str::random(rand(100, 1000)));
      $this->assertEquals(filesize($path), $file->getSize());
      $this->assertTrue(is_string($file->getSize(2)));
   }

   /**
    * @covers ::canWrite
    */
   public function testCanWrite()
   {
      $file = new File(vfsStream::url("root/file.-w-"));
      $this->assertTrue($file->canWrite());

      $file = new File(vfsStream::url("root/dir.rwx/file.txt"));
      $this->assertTrue($file->canWrite());

      $file = new File(vfsStream::url("root/newdir/file.txt"));
      $this->assertTrue($file->canWrite());

      $file = new File(vfsStream::url("root/dir.--x/file.txt"));
      $this->assertFalse($file->canWrite());
   }

   /**
    * @covers ::remove
    */
   public function testRemove()
   {
      $file = new File(vfsStream::url("root/file.rwx"));
      $this->assertTrue($file->remove());
      $this->assertFalse($file->test(File::EXISTS));

      $file = new File(vfsStream::url("root/dir.--x/file.txt"));
      $this->assertFalse($file->remove());
   }

   /**
    * @covers ::getExtension
    */
   public function testGetExtension()
   {
      $file = new File("file.txt");
      $this->assertEquals("txt", $file->getExtension());

      $file = new File("/some/path/file.txt");
      $this->assertEquals("txt", $file->getExtension());

      $file = new File("file");
      $this->assertNull($file->getExtension());
      $this->assertSame("", $file->getExtension(""));

      $file = new File(".hidden");
      $this->assertNull($file->getExtension());

      $file = new File(".hidden.ext");
      $this->assertEquals("ext", $file->getExtension());
   }

   /**
    * @covers ::splitName
    */
   public function testSplitNameFile()
   {
      $file = new File("file.txt");
      list($name, $ext) = $file->splitName();
      $this->assertEquals("file", $name);
      $this->assertEquals("txt", $ext);
      $file = new File("/file.txt");
      list($name, $ext) = $file->splitName();
      $this->assertEquals("file", $name);
      $this->assertEquals("txt", $ext);

      # without extension
      $file = new File("file");
      list($name, $ext) = $file->splitName();
      $this->assertEquals("file", $name);
      $this->assertNull($ext);
      $file = new File("/file");
      list($name, $ext) = $file->splitName();
      $this->assertEquals("file", $name);
      $this->assertNull($ext);

      # hidden file with extension
      $file = new File(".hidden.txt");
      list($name, $ext) = $file->splitName();
      $this->assertEquals(".hidden", $name);
      $this->assertEquals("txt", $ext);
      $file = new File("/.hidden.txt");
      list($name, $ext) = $file->splitName();
      $this->assertEquals(".hidden", $name);
      $this->assertEquals("txt", $ext);

      # hidden file no extension
      $file = new File(".hidden");
      list($name, $ext) = $file->splitName();
      $this->assertEquals(".hidden", $name);
      $this->assertNull($ext);
      $file = new File("/.hidden");
      list($name, $ext) = $file->splitName();
      $this->assertEquals(".hidden", $name);
      $this->assertNull($ext);

      # provide an empty string as the default value for a missing extension
      $file = new File("filename");
      list($name, $ext) = $file->splitName("");
      $this->assertEquals("filename", $name);
      $this->assertSame("", $ext);
   }

   /**
    * @covers ::write
    * @covers ::writeFile
    */
   public function testWrite()
   {
      $path = vfsStream::url("root/some/new/path/file.txt");
      $contents = Str::random(rand(100, 1000));
      $this->assertFalse(file_exists($path));
      $file = new File($path);
      $file->write($contents);
      $this->assertTrue(file_exists($path));
      $this->assertEquals($contents, file_get_contents($path));

      $contents = "✓ ✔ ✕ ✖ ✗ ✘ ✙ ✚ ✛ ✜ ✝ ✞ ✟ ✠ ✡ ✢ ✣ ✤ ✥ ✦ ✧ ✩ ✪ ✫ ✬ ✭";
      $file->write($contents);
      $this->assertEquals($contents, file_get_contents($path));
   }

   /**
    * @covers ::write
    */
   public function testWritePrepareFailure()
   {
      $path = vfsStream::url("root/dir.--x/file.txt");
      $contents = Str::random(rand(100, 1000));
      $file = new File($path);
      $this->assertFalse($file->write($contents));
      $this->assertTrue(is_string($file->getError()));
   }

   /**
    * @covers ::write
    * @covers ::writeFile
    */
   public function testWriteFailure()
   {
      vfsStream::setQuota(10);
      $path = vfsStream::url("root/test.txt");
      $contents = Str::random(rand(100, 1000));
      $file = new File($path);
      $this->assertFalse($file->write($contents));
      $this->assertTrue(is_string($file->getError()));
   }

   private function preparePrependTest($len)
   {
      $path = vfsStream::url("root/ile.txt");
      $contents = Str::random($len);
      file_put_contents($path, $contents);
      $str = Str::random(100);
      $expect = $str.$contents;
      return [$path, $contents, $str, $expect];
   }

   /**
    * @covers ::prepend
    */
   public function testPrepend()
   {
      list($path, $contents, $str, $expect) = $this->preparePrependTest(1024);
      $file = new File($path);
      $this->assertTrue($file->prepend($str));
      $this->assertEquals($expect, file_get_contents($path));
   }

   /**
    * @covers ::prepend
    */
   public function testPrependCannotWrite()
   {
      $file = new File(vfsStream::url("root/file.---"));
      $this->assertFalse($file->prepend("42"));
      $this->assertTrue(is_string($file->getError()));
   }

   /**
    * @covers ::prepend
    * @covers ::prependFileInPlace
    */
   public function testPrependInPlace()
   {
      list($path, $contents, $str, $expect) = $this->preparePrependTest(1024);
      $file = new File($path);
      $this->assertTrue($file->prepend($str, 512));
      $this->assertEquals($expect, file_get_contents($path));
   }

   /**
    * @covers ::prepend
    */
   public function testPrependReadFailure()
   {
      $mock = $this->getMockBuilder("sndsgd\\fs\\File")
         ->setConstructorArgs([ vfsStream::url("root/file.-w-") ])
         ->setMethods(["test"])
         ->getMock();
      $mock->method("test")->willReturn(true);
      $this->assertFalse($mock->prepend("42"));
   }

   /**
    * @covers ::prepend
    */
   public function testPrependWriteFailure()
   {
      $mock = $this->getMockBuilder("sndsgd\\fs\\File")
         ->setConstructorArgs([ vfsStream::url("root/file.r--") ])
         ->setMethods(["test"])
         ->getMock();
      $mock->method("test")->willReturn(true);
      $this->assertFalse($mock->prepend("42"));
   }
   
   /**
    * @covers ::read
    * @covers ::readFile
    */
   public function testReadFailure()
   {
      $file = new File(vfsStream::url("root/dir.--x/file.txt"));
      $this->assertFalse($file->read());
      $this->assertTrue(is_string($file->getError()));
   }

   /**
    * @covers ::read
    * @covers ::readFile
    */
   public function testRead()
   {
      # success
      $file = new File(vfsStream::url("root/file.rwx"));
      $this->assertSame("contents...", $file->read());

      $this->assertSame("contents...", file_get_contents($file));

      # permissions
      $file = new File(vfsStream::url("root/file.---"));
      $this->assertFalse($file->read());
   }

   /**
    * @covers ::read
    * @covers ::readFile
    */
   public function testReadFileGetContentsFailure()
   {
      $mock = $this->getMockBuilder("sndsgd\\fs\\File")
         ->setConstructorArgs([ vfsStream::url("root/dir.--x") ])
         ->setMethods(["test"])
         ->getMock();

      $mock->method("test")->willReturn(true);
      $this->assertFalse($mock->read());
   }

   /**
    * @covers ::getLineCount
    */
   public function testGetLineCount()
   {
      list($path, $bytes, $lines) = $this->createTestFile();
      $file = new File($path);
      $this->assertEquals($lines, $file->getLineCount());
   }

   
}

