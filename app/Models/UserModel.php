<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserModel extends Model
{
    protected $table = "users";
    
    use SoftDeletes;

    public function profile()
    {
        return $this->hasOne(ProfileModel::class, 'id', 'profile_id');
    }
}
