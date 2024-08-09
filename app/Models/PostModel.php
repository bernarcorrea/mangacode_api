<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostModel extends Model
{
    protected $table = "posts";

    use SoftDeletes;

    public function category()
    {
        return $this->belongsTo(CategoryModel::class, "categorie_id", "id");
    }
}
