<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryModel extends Model
{
    protected $table = "categories";

    use SoftDeletes;

    public function posts()
    {
        return $this->hasMany(PostModel::class, "categorie_id", "id");
    }
}
