[![Latest Version on Packagist](https://img.shields.io/packagist/v/inmanturbo/module-compiler.svg?style=flat-square)](https://packagist.org/packages/inmanturbo/module-compiler)
[![Total Downloads](https://img.shields.io/packagist/dt/inmanturbo/module-compiler.svg?style=flat-square)](https://packagist.org/packages/inmanturbo/module-compiler)

## Installation

```bash
composer require --dev inmanturbo/module-compiler
```

## Usage

First create a build module:

Example build module: (Create at path modules/test-one-two.php)

```php
<?php

// BEGIN_FILE: (app/Models/TestOne.php)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestOne extends Model
{
    use HasFactory;
}

// END_FILE

// BEGIN_FILE: (app/Models/TestTwo.php)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestTwo extends Model
{
    use HasFactory;
}

// END_FILE
```

Next run the build command:

```bash
php artisan build test-one-two.php
```

example output:

```bash
Created directory: /path/to/your/project/app/Models
Built file: app/Models/TestOne.php
Built file: app/Models/TestTwo.php
All modules have been built successfully.
```

```bash
php artisan build --help  
Description:
  Compile modules into their respective files

Usage:
  build [options] [--] [<module>...]

Arguments:
  module                           File containing code that should be split out into one or more files

Options:
      --module-path[=MODULE-PATH]  Directory under which modules will be found [default: "modules"]
      --build-path[=BUILD-PATH]    Leave empty for `base_path()`
      --realpath                   Use realpath for module and build path(s)
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
      --env[=ENV]                  The environment the command should run under
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

```bash
php artisan combine --help
Description:
  Combine one or more PHP files into a single module file

Usage:
  combine [options] [--] <files>...

Arguments:
  files                            Path to one or more PHP files to combine, relative to build-path

Options:
      --module-path[=MODULE-PATH]  The name of the build modules directory [default: "modules"]
      --build-path[=BUILD-PATH]    The base path to find the files under (leave empty for `base_path()`
      --realpath                   Indicates indicates provided paths will be absolute
      --module[=MODULE]            The name of the build module file [default: "app.php"]
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
      --env[=ENV]                  The environment the command should run under
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Todo Roadmap

- [ ] Minify built files.
- [ ] Phar compression.
- [ ] Adminer compression.
- [ ] Frankenphp binaries.
