<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSessionModel extends Model
{
    protected $table = "users_session";
    
    public function user()
    {
        return $this->hasOne(UserModel::class, 'id', 'user_id');
    }
}
