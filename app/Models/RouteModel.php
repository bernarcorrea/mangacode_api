<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RouteModel extends Model
{
    protected $table = "routes";

    use SoftDeletes;

    public function module()
    {
        return $this->belongsTo(ModuleModel::class, 'module_id', 'id');
    }

    public function controller()
    {
        return $this->belongsTo(ControllerModel::class, 'controller_id', 'id');
    }

    public function profiles()
    {
        return $this->hasMany(ProfileRouteModel::class, 'route_id', 'id');
    }
}
