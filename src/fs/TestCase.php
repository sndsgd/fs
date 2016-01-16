<?php

namespace sndsgd\fs;

use \org\bovigo\vfs\vfsStream;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $root;

    public function setUp()
    {
        $this->root = vfsStream::setup("root");
        vfsStream::create([
            "file.txt" => "contents...",
            "emptydir" => [],
            "test" => [
                "file.txt" => "contents...",
                "emptydir" => [],
            ],
            "dir.rwx" => [],
            "dir.r-x" => [],
            "dir.--x" => [],
            "dir.---" => [
                "file.txt" => "contents...",
            ],
            "file.rwx" => "contents...",
            "file.rw-" => "contents...",
            "file.r--" => "contents...",
            "file.-w-" => "contents...",
            "file.--x" => "contents...",
            "file.---" => "contents...",
        ]);

        $this->root->getChild("dir.rwx")->chmod(0777);
        $this->root->getChild("dir.r-x")->chmod(0555);
        $this->root->getChild("dir.--x")
            ->chmod(0711)
            ->chgrp(vfsStream::GROUP_ROOT)
            ->chown(vfsStream::OWNER_ROOT);
        $this->root->getChild("dir.---")
            ->chmod(0000)
            ->chgrp(vfsStream::GROUP_ROOT)
            ->chown(vfsStream::OWNER_ROOT);



        $this->root->getChild("file.rwx")->chmod(0777);
        $this->root->getChild("file.rw-")->chmod(0666);
        $this->root->getChild("file.r--")->chmod(0444);
        $this->root->getChild("file.-w-")->chmod(0222);
        $this->root->getChild("file.--x")->chmod(0111);
        $this->root->getChild("file.---")->chmod(0000);

        // reset the tracked temp files
        $rc = new \ReflectionClass("sndsgd\\fs\\Temp");
        $property = $rc->getProperty("entities");
        $property->setAccessible(true);
        $property->setValue([]);
    }
}
