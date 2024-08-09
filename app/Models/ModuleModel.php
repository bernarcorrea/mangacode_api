<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModuleModel extends Model
{
    protected $table = "modules";
    
    use SoftDeletes;

    public function application()
    {
        return $this->belongsTo(ApplicationModel::class, 'application_id', 'id');
    }

    public function routes()
    {
        return $this->hasMany(RouteModel::class, 'module_id', 'id');
    }

    public function elements()
    {
        return $this->hasMany(ElementModel::class, 'module_id', 'id');
    }
}
