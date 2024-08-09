<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileElementModel extends Model
{
    protected $table = "profiles_elements";

    public function element()
    {
        return $this->hasOne(ElementModel::class, 'element_id', 'id');
    }

    public function profile()
    {
        return $this->hasOne(ProfileModel::class, 'profile_id', 'id');
    }
}
