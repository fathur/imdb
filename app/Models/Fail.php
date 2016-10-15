<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Fail extends Model
{
    protected $collection = 'fail';

    protected $fillable = ['imdb_id'];
}