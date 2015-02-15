<?php

namespace sndsgd\fs;

use \ReflectionClass;
use \sndsgd\Str;


/**
 * @coversDefaultClass \sndsgd\fs\EntityAbstract
 */
class EntityAbstractTest extends TestCase
{
   const CLASSNAME = "sndsgd\\fs\\EntityAbstract";

   private function getMockedEntity($path)
   {
      $arguments = [$path];
      return $this->getMockForAbstractClass(self::CLASSNAME, $arguments);
   }

   public function setUp()
   {
      $this->path = "/test/dir/file.txt";
      $this->mock = $this->getMockedEntity($this->path);
      $this->rc = new ReflectionClass(self::CLASSNAME);
   }

   /**
    * @covers ::sanitizeName
    */
   public function testSanitizeName()
   {
      $test = __METHOD__.".test";
      $expect = "sndsgd_fs_EntityAbstractTest__testSanitizeName.test";
      $this->assertEquals($expect, File::sanitizeName($test));

      $test = "!@#$%^&*(";
      $expect = "_________";
      $this->assertEquals($expect, File::sanitizeName($test));
   }

   public function testCoreFuncs()
   {
      $this->assertEquals(basename($this->path), basename($this->mock));
      $this->assertEquals(dirname($this->path), dirname($this->mock));
   }

   /**
    * @covers ::__construct
    */
   public function testConstructor()
   {
      $constructor = $this->rc->getConstructor();
      $constructor->invoke($this->mock, $this->path);
      $this->assertEquals($this->path, $this->mock->getPath());
   }

   /**
    * @covers ::__toString
    */
   public function testToString()
   {
      $this->assertEquals($this->path, $this->mock->__toString());
   }

   /**
    * @covers ::setError
    */
   public function testSetError()
   {
      $message = "whoop! error!";
      $this->mock->setError($message);
      $this->assertEquals($message, $this->mock->getError());

      # force an error to be created
      @file_put_contents($this->getPath("root/file-no-rw"), "test");
      $message = "file write failure";
      $this->mock->setError($message);
      $begin = "$message; file_put_contents";
      $this->assertEquals(0, strpos($this->mock->getError(), $begin));
   }

   /**
    * @covers ::getError
    */
   public function testGetError()
   {
      $this->assertNull($this->mock->getError());

      $err = "oh noes!";
      $property = $this->rc->getProperty("error");
      $property->setAccessible(true);
      $property->setValue($this->mock, $err);
      $this->assertSame($err, $this->mock->getError());
      $this->assertNull($this->mock->getError());
   }

   public function testGetPath()
   {
      $this->assertSame($this->path, $this->mock->getPath());
   }

   /**
    * @covers ::test
    * @expectedException InvalidArgumentException
    */
   public function testTestInvalidArg()
   {
      $this->mock->test("must-be-int");
   }

   /**
    * @covers ::getParent
    */
   public function testGetParent()
   {
      $path = "/test/path/file.txt";
      $mock = $this->getMockedEntity($path);
      
      $parent = $mock->getParent();
      $this->assertInstanceof("sndsgd\\fs\\Dir", $parent);
      $this->assertEquals("/test/path", $parent->getPath());

      $parent = $parent->getParent();
      $this->assertInstanceof("sndsgd\\fs\\Dir", $parent);
      $this->assertEquals("/test", $parent->getPath());

      $parent = $parent->getParent();
      $this->assertInstanceof("sndsgd\\fs\\Dir", $parent);
      $this->assertEquals("/", $parent->getPath());

      $this->assertNull($parent->getParent());
   }

   /**
    * @covers ::normalize
    * @covers ::normalizeLeadingDots
    */
   public function testNormalize()
   {
      $cwd = getcwd();
      $dir = dirname($cwd);

      $mock = $this->getMockedEntity(".file.txt");
      $this->assertEquals(".file.txt", $mock->normalize());

      $mock = $this->getMockedEntity("/tmp///path/.//file.txt");
      $this->assertEquals("/tmp/path/file.txt", $mock->normalize());

      $mock = $this->getMockedEntity("/tmp/path/../file.txt");
      $this->assertEquals("/tmp/file.txt", $mock->normalize());

      $mock = $this->getMockedEntity("/tmp/test/path/../../file.txt");
      $this->assertEquals("/tmp/file.txt", $mock->normalize());

      $mock = $this->getMockedEntity("test/./path/one/..");
      $this->assertEquals("test/path", $mock->normalize());

      $mock = $this->getMockedEntity(".");
      $this->assertEquals($cwd, $mock->normalize());

      $mock = $this->getMockedEntity("./");
      $this->assertEquals($cwd, $mock->normalize());

      $mock = $this->getMockedEntity("./test");
      $this->assertEquals("$cwd/test", $mock->normalize());

      $mock = $this->getMockedEntity("../test");
      $this->assertEquals("$dir/test", $mock->normalize());

      $mock = $this->getMockedEntity("..");
      $this->assertEquals($dir, $mock->normalize());

      $mock = $this->getMockedEntity("../");
      $this->assertEquals($dir, $mock->normalize());
   }

   /**
    * @covers ::getRelativePath
    */
   public function testGetRelativePath()
   {
      $from = "/one/two/file.txt";
      $to = "/one/file.txt";
      $expect = "../file.txt";
      $mock = $this->getMockedEntity($from);
      $this->assertEquals($expect, $mock->getRelativePath($to));

      $from = "/a/b/c";
      $to = "/x/y";
      $expect = "../../x/y";
      $mock = $this->getMockedEntity($from);
      $this->assertEquals($expect, $mock->getRelativePath($to));
   }
}

