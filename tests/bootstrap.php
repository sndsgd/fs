<?php

require __DIR__."/../vendor/autoload.php";
require __DIR__."/lib/TestCase.php";

# create mocks for the following namspaced functions
# this way we don't have to worry about them being called first
# see https://github.com/php-mock/php-mock#requirements-and-restrictions
$mockFunctions = [
    ["sndsgd\\fs\\directory", "sha1_file"],
];

foreach ($mockFunctions as list($namespace, $name)) {
    (new \phpmock\MockBuilder())
        ->setNamespace($namespace)
        ->setName($name)
        ->setFunction(function(){})
        ->build()
        ->define();
}
