[![Latest Version on Packagist](https://img.shields.io/packagist/v/inmanturbo/module-compiler.svg?style=flat-square)](https://packagist.org/packages/inmanturbo/module-compiler)
[![Total Downloads](https://img.shields.io/packagist/dt/inmanturbo/module-compiler.svg?style=flat-square)](https://packagist.org/packages/inmanturbo/module-compiler)

# Usage

example module: create at path modules/test-one-two.php

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

```bash
php artisan app:build
```

example output:

```bash
Created directory: /path/to/your/project/app/Models
Built file: app/Models/TestOne.php
Built file: app/Models/TestTwo.php
All modules have been built successfully.
```
