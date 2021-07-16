<?php

require_once 'Owner.php';
require_once 'Option.php';

use \Lightpack\Database\Lucid\Model;

class Product extends Model
{   
    protected $table = 'products';

    public function options()
    {
        return $this->hasMany(Option::class, 'product_id');
    }

    public function owner()
    {
        return $this->hasOne(Owner::class, 'product_id');
    }
}