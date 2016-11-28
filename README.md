# proxy-generator
[![Build Status](https://travis-ci.org/delboy1978uk/proxy-generator.png?branch=master)](https://travis-ci.org/delboy1978uk/proxy-generator) [![Code Coverage](https://scrutinizer-ci.com/g/delboy1978uk/proxy-generator/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/delboy1978uk/proxy-generator/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/delboy1978uk/proxy-generator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/delboy1978uk/proxy-generator/?branch=master) <br />
A Proxy generator for getting third party libraries to implement your interface. Just tell it what Interface you wish to 
replace, and it will search through, and generate your own class extending it but implementing your own interface.
##Example
Replacing Some\Symfony\Lib\SomeInterface with My\Awesome\Lib\SomeInterface will result in this before and after:
####Before
```php
<?php

namespace Some\Symfony\Lib;

class UsefulClass implements SomeInterface 
{
    // etc
}
```
####After
```php
<?php

namespace My\Awesome\Lib;

use Some\Symfony\Lib\UsefulClass as ThirdPartyUsefulClass;

class UsefulClass extends ThirdPartyUsefulClass implements SomeInterface 
{
}
```
For each class implementing the interface, we recursively iterate over the vendor classes and generate any classes 
extending them. These classes will also implement our interface. 
```php
<?php

namespace My\Awesome\Lib\Number;

use Some\Symfony\Lib\Number\UsefulNuberClass as ThirdPartyUsefulNumberClass;
use My\Awesome\Lib\SomeInterface;

class UsefulClass extends ThirdPartyUsefulNumberClass implements SomeInterface 
{
}
```
##Installation
Install using composer
```
$ composer require delboy1978uk/proxy-generator
```
##Usage
You can use Del\ProxyGenerator\Service\ProxyGeneratorService if doing it programatically, or you can use the CLI command bin/proxy-generator.
```
$ cd bin
$ ./proxy-generator 'VendorInterface' 'YourInterface' look/in/this/folder 'BaseVendorNamespace', 'YourBaseNamespace', 'relative/path/to/genarate', '/absolute/project/root/basedir'
```