<?php

namespace sndsgd\fs;

use \InvalidArgumentException;
use \sndsgd\ErrorTrait;


abstract class EntityAbstract
{
   use ErrorTrait;

   // bitmask values for use in sndsgd\fs\Entity::test()
   const EXISTS = 1;
   const DIR = 2;
   const FILE = 4;
   const READABLE = 8;
   const WRITABLE = 16;
   const EXECUTABLE = 32;

   /**
    * Remove wonky characters from a path name
    *
    * @param string $name The basename to sanitize
    * @return string
    */
   public static function sanitizeName($name)
   {
      $basename = basename($name);
      $dir = ($basename === $name) ? null : dirname($name);
      $basename = preg_replace("~[^A-Za-z0-9-_.]~", "_", $basename);
      return ($dir === null)
         ? $basename
         : $dir.DIRECTORY_SEPARATOR.$basename;
   }

   /**
    * Get an entity instance and normalize the path
    * @param string $path
    * @return \sndsgd\fs\Dir|\sndsgd\fs\File
    */
   public static function get($path)
   {
      $instance = new static($path);
      $instance->normalize();
      return $instance;
   }

   /**
    * The path as provided to the constructor
    * 
    * @var string
    */
   protected $path;

   /**
    * Constructor
    * 
    * @param string $path
    */
   public function __construct($path)
   {
      $this->path = $path;
   }

   /**
    * Get the path as a string
    * 
    * @return string
    */
   public function __toString()
   {
      return $this->path;
   }

   /**
    * Get the path as a string
    * 
    * @return string
    */
   public function getPath()
   {
      return $this->path;
   }

   /**
    * Perform type/permissions tests on an entity
    *
    * @param integer $opts
    * @return boolean
    */
   public function test($opts)
   {
      if (!is_int($opts)) {
         throw new InvalidArgumentException(
            "invalid value provided for 'opts'; ".
            "expecting options as an integer"
         );
      }

      if ($opts & self::EXISTS && file_exists($this->path) === false) {
         $this->error = "'{$this->path}' does not exist";
         return false;
      }
      else if ($opts & self::FILE && is_file($this->path) === false) {
         $this->error = "'{$this->path}' is not a file";
         return false;
      }
      else if ($opts & self::DIR && is_dir($this->path) === false) {
         $this->error = "'{$this->path}' is not a directory";
         return false;
      }
      else if ($opts & self::READABLE && is_readable($this->path) === false) {
         $this->error = "'{$this->path}' is not readable";
         return false;
      }
      else if ($opts & self::WRITABLE && is_writable($this->path) === false) {
         $this->error = "'{$this->path}' is not writable";
         return false;
      }
      else if ($opts & self::EXECUTABLE && is_executable($this->path) === false) {
         $this->error = "'{$this->path}' is not executable";
         return false;
      }
      else {
         return true;
      }
   }

   /**
    * Determine if a path can be written to
    * 
    * @return boolean
    */
   abstract public function canWrite();

   /**
    * Prepare an entity for writing by creating non existing parents
    * 
    * @param  integer $mode The octal permissions value for directories
    * @return boolean
    */
   abstract public function prepareWrite($mode = 0775);

   /**
    * Get the parent directory
    * 
    * @return sndsgd\fs\Dir|null
    * @return sndsgd\fs\Dir The parent directory
    * @return null The entity has no parent
    */
   public function getParent()
   {
      if ($this->path === "/" || ($path = dirname($this->path)) === ".") {
         return null;
      }

      return new Dir($path);
   }

   /**
    * Determine whether or not a path is absolute
    * 
    * @return string
    */
   public function isAbsolute()
   {
      return $this->path{0} === DIRECTORY_SEPARATOR;
   }

   /**
    * Normalize a path to remove dots
    * 
    * @return string
    */
   public function normalize()
   {
      $path = rtrim($this->path, DIRECTORY_SEPARATOR);
      if ($path{0} === ".") {
         $path = $this->normalizeLeadingDots($path);
      }
      
      $parts = explode("/", $path);
      $abs = ($parts[0] === "");
      $temp = [];
      foreach ($parts as $part) {
         if ($part === "." || $part === "") {
            continue;
         }
         else if ($part === "..") {
            array_pop($temp);
         }
         else {
            $temp[] = $part;
         }
      }
      $temp = implode("/", $temp);
      $this->path = ($abs) ? "/$temp" : $temp;
      return $this->path;
   }

   /**
    * Normalize the path to a directory
    *
    * @param string $dir
    * @return string
    */
   public function normalizeTo($dir)
   {
      if ($this->isAbsolute()) {
         return $this->path;   
      }
      $this->path = "$dir/$this->path";
      return $this->normalize();
   }

   private function normalizeLeadingDots($path)
   {
      if ($path === ".") {
         return getcwd();
      }
      else if ($path === "..") {
         return dirname(getcwd());
      }
      else if ($path{1} === "/") {
         $path = getcwd().substr($path, 1);
      }
      else if ($path{1} === ".") {
         $path = dirname(getcwd()).substr($path, 2);
      }
      return $path;
   }

   /**
    * Get the relative path from the current path to another
    * 
    * @param string $path
    * @return string
    */
   public function getRelativePath($path)
   {
      $path = (string) $path;
      $from = $this->path;
      $fromParts = explode("/", $from);
      $toParts = explode("/", $path);
      $max = max(count($fromParts), count($toParts));
      for ($i=0; $i<$max; $i++) {
         if ($fromParts[$i] !== $toParts[$i]) {
            break;
         }
      }

      $len = count($fromParts) - $i - 1;
      $path = array_slice($toParts, $i);
      return str_repeat("../", $len).implode("/", $path);
   }
}

