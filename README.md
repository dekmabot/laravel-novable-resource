# Laravel Nova Resource Trait

This trait helps to make laravel nova CRUD interfaces in one string. 

```php
<?php

namespace App\Nova;

use App\Models\User;
use NovableResource\Traits\NovableResource;

class UserResource extends \Laravel\Nova\Resource
{
    use NovableResource;

    public static $model = User::class;
}
```

When generating Nova Resource, you need to describe all fields for each model. And it`s a bit boring. 

If you have already described it once in $casts array of the original Model, you do not need to describe it manually again. 
Just use this trait to automatically use all fields and relations to make CRUD interfaces for model with only one string.

## Installation

```bash
composer require dekmabot/laravel-novable-resource
```

## Usage

This trait allows you to automatically use these casted fields:
* boolean
* date
* datetime
* double
* float
* real
* integer
* string
* timestamp

... and relations:
* BelongsTo