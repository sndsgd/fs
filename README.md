# sndsgd/fs

[![Latest Version](https://img.shields.io/github/release/sndsgd/sndsgd-fs.svg?style=flat-square)](https://github.com/sndsgd/sndsgd-fs/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/sndsgd/sndsgd-fs/LICENSE)
[![Build Status](https://img.shields.io/travis/sndsgd/sndsgd-fs/master.svg?style=flat-square)](https://travis-ci.org/sndsgd/sndsgd-fs)
[![Coverage Status](https://img.shields.io/coveralls/sndsgd/sndsgd-fs.svg?style=flat-square)](https://coveralls.io/r/sndsgd/sndsgd-fs?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/sndsgd/fs.svg?style=flat-square)](https://packagist.org/packages/sndsgd/fs)

A filesystem toolkit for PHP.


## Why?

The classes in `sndsgd\fs` attempt to simplify tedious filesystem tasks.

```php
use \sndsgd\Fs;

# lets assume `/tmp/some` doesn't exist
$path = "/tmp/some/deep/path/file.txt";

# write to a file that doesn't exist in a directory that doesn't exist
$file = Fs::getFile($path);
if ($file->write("the contents...") === false) {
   throw new Exception($file->getError());
}
```


## Requirements

This project is unstable and subject to changes from release to release. If you intend to depend on this project, be sure to make note of and specify the version in your project's `composer.json`. Doing so will ensure any breaking changes do not break your project.

You need **PHP >= 7.0.0** to use this library.


## Install

Install `sndsgd/fs` using [Composer](https://getcomposer.org/).

```
composer require sndsgd/fs
```
