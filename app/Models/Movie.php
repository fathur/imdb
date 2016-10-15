<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Movie extends Model
{
    protected $collection = 'movies';

    protected $fillable = ['imdb_id', 'title', 'year', 'rating', 'runtime', 'genre', 'released', 'director', 'writer', 'cast',
        'meta_critic', 'rating', 'votes', 'poster', 'plot', 'languages', 'countries', 'awards', 'type', 'genres',
        'directors', 'writers', 'actors', 'last_udpate'];

    protected $dates = ['released', 'last_update'];
}