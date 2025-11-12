<?php

namespace Modules\Blog\Models;

use Lightpack\Database\Lucid\Model;

class Post extends Model
{
    protected $table = 'posts';
    protected $primaryKey = 'id';
    protected $timestamps = true;
}
