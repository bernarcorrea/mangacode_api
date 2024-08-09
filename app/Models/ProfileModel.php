<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileModel extends Model
{
    protected $table = "profiles";
    
    use SoftDeletes;

    public function elements()
    {
        return $this->hasMany(ProfileElementModel::class, 'profile_id', 'id');
    }
}
