<?php

use App\Models\RouteModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/', function () {
    var_dump('.:: API ::.');
});

/** ROUTE GET CSRF-TOKEN */
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});

$routes = RouteModel::select(
    'module_id',
    'controller_id',
    'id',
    'method',
    'type',
    'uri',
)->with([
    'module' => function ($query) {
        $query->select(
            'application_id',
            'id',
            'title'
        );
    },
    'module.application' => function ($query) {
        $query->select(
            'id',
            'path'
        );
    },
    'controller' => function ($query) {
        $query->select(
            'id',
            'title'
        );
    },
])->get();

/** 
 * ***********************
 * * APLICAÇÃO `DASHBOARD`
 * ***********************
 */

/** ROTAS DE HOME, LOGIN E LOGOUT */
Route::post('/admin', [App\Http\Controllers\Dashboard\HomeController::class, 'home']);
Route::post('/admin/login', [App\Http\Controllers\Dashboard\LoginController::class, 'login']);
Route::post('/admin/logout', [App\Http\Controllers\Dashboard\LoginController::class, 'logout']);

if (count($routes)) :
    foreach ($routes as $route) :
        $module = ($route->module->application ? $route->module->application->path : 'NDA');
        $controller = "App\\Http\\Controllers\\{$module}\\{$route->controller->title}";
        $type = $route->type;

        /** SOMENTE ROTAS DA APLICAÇÃO `DASHBOARD` */
        if ($route->module->application->path == 'Dashboard') :
            Route::middleware(['user.permissions'])->group(function () use ($type, $controller, $route) {
                Route::$type(
                    "{$route->uri}",
                    [
                        $controller,
                        $route->method
                    ]
                );
            });
        endif;
    endforeach;
endif;
