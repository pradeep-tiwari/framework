<?php

use Lightpack\Database\Lucid\Builder;
use Lightpack\Database\Lucid\Model;

class ResourceQueryUser extends Model
{
    protected $table = 'users';

    protected function scopeStatus(Builder $query, $value): void
    {
        $query->where('status', $value);
    }

    protected function scopeRole(Builder $query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('role', $value);
        } else {
            $query->where('role', $value);
        }
    }

    protected function scopeSearch(Builder $query, $value): void
    {
        $query->where('name', 'LIKE', '%' . $value . '%');
    }
}
