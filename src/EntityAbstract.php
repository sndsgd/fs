<?php

namespace sndsgd\fs;

use \InvalidArgumentException;


abstract class EntityAbstract
{
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
    * The path as provided to the constructor
    * 
    * @var string
    */
   protected $path;

   /**
    * An error message
    * 
    * @var string|null
    */
   protected $error = null;

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
    * Set an error message
    *
    * @param string $message
    */
   public function setError($msg)
   {
      if (($err = error_get_last()) !== null) {
         $msg .= "; '{$err['message']}' in {$err['file']} on {$err['line']}";
      }
      $this->error = $msg;
   }

   /**
    * Retreive and clear the last error
    * 
    * @return string
    */
   public function getError()
   {
      $message = $this->error;
      $this->error = null;
      return $message;
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
   public abstract function canWrite();

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

   public function getRelativePath($to)
   {
      $from = $this->path;
      $i = 0;
      $minlen = min(strlen($from), strlen($to));
      while ($i < $minlen && $from{$i} === $to{$i}) {
         $i++;
      }

      $from = substr($from, $i);
      $to = substr($to, $i);
      $fparts = explode("/", $from);
      return str_repeat("../", count($fparts) - 1).$to;
   }
}

