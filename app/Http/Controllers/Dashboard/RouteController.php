<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ControllerModel;
use App\Models\ModuleModel;
use App\Models\ProfileModel;
use App\Models\ProfileRouteModel;
use App\Models\RouteModel;
use Illuminate\Http\Request;
use stdClass;

class RouteController extends Controller
{
    public function index(Request $data)
    {
        $profiles = ProfileModel::orderBy('title')->get();
        $controllers = ControllerModel::orderBy('title')->get();
        $modules = ModuleModel::orderBy('title')->get();
        $routesGroup = RouteModel::where('group', 1)
            ->orderBy('title')
            ->get();

        $routes = RouteModel::select(
            'id',
            'module_id',
            'controller_id',
            'method',
            'type',
            'title',
            'uri',
        )->with([
            'module' => function ($query) {
                $query->select(
                    'id',
                    'title'
                );
            },
            'controller' => function ($query) {
                $query->select(
                    'id',
                    'title'
                );
            },
        ])->get();

        $result = new stdClass;
        $result->routes = $routes;
        $result->controllers = $controllers;
        $result->modules = $modules;
        $result->profiles = $profiles;
        $result->routes_group = $routesGroup;
        $result->profile_elements = $data->profile_elements;

        return apiReturn(
            true,
            200,
            '',
            $result
        );
    }

    public function view(Request $data)
    {
        if (!$data->id) :
            return apiReturn(
                false,
                404,
                "Rota não encontrada.",
            );
        endif;

        $route = RouteModel::find($data->id);
        if (!$route) :
            return apiReturn(
                false,
                404,
                "Rota não encontrada.",
            );
        endif;

        $result = new stdClass;
        $result->route = $route;
        $result->profiles = [];

        if (count($route->profiles)) :
            foreach ($route->profiles as $prof) :
                $result->profiles[] = $prof->profile_id;
            endforeach;
        endif;

        return apiReturn(
            true,
            200,
            '',
            $result
        );
    }

    public function manager(Request $data)
    {
        if (
            !$data->title
            || !$data->uri
            || !$data->controller
            || !$data->method
            || !$data->module
            || !$data->type
            || $data->group === null
            || $data->view_menu === null
        ) :
            return apiReturn(
                false,
                404,
                "Preencha todos os campos obrigatórios."
            );
        endif;

        $id = ($data->id ? $data->id : false);
        if (!$id) :
            $route = new RouteModel();
        else :
            $this->deleteProfilesRoutes($id);
            $route = RouteModel::find($id);
        endif;

        $findRoute = RouteModel::where("uri", $data->uri)
            ->when($id, function ($query) use ($id) {
                $query->where("id", '!=', $id);
            })->first();

        if ($findRoute) :
            return apiReturn(
                false,
                400,
                "Já existe uma rota cadastrada com essa URI."
            );
        endif;

        /** CADASTRA/ATUALIZA */
        $route->title = $data->title;
        $route->uri = $data->uri;
        $route->type = $data->type;
        $route->method = $data->method;
        $route->controller_id = $data->controller;
        $route->module_id = $data->module;
        $route->group = $data->group;
        $route->view_menu = $data->view_menu;
        $route->route_id = ($data->route_id ? $data->route_id : 0);

        if (!$route->save()) :
            return apiReturn(
                false,
                500,
                "Não foi possível salvar os dados da rota."
            );
        endif;

        /** PERMISSÕES DE ROTA PARA OS PERFIS */
        if (count($data->profiles)) :
            foreach ($data->profiles as $prof) :
                $profileRoute = new ProfileRouteModel();
                $profileRoute->profile_id = $prof;
                $profileRoute->route_id = $route->id;
                $profileRoute->save();
            endforeach;
        endif;

        return apiReturn(
            true,
            200,
            "A rota foi salva com sucesso.",
        );
    }

    public function delete(Request $data)
    {
        if (!$data->id) :
            return apiReturn(
                false,
                404,
                "Nenhuma rota foi selecionada para ser excluída."
            );
        endif;

        $route = RouteModel::find($data->id);
        if (!$route) :
            return apiReturn(
                false,
                404,
                "A rota não foi encontrada."
            );
        endif;

        /** EXCLUI TODAS AS PERMISSÕES DOS PERFIS DA ROTA A SER EXCLUÍDA */
        $profilesRoute = $route->profiles;
        if ($profilesRoute) :
            foreach ($profilesRoute as $profRoute) :
                $pr = ProfileRouteModel::find($profRoute->id);
                $pr->delete();
            endforeach;
        endif;

        if (!$route->delete()) :
            return apiReturn(
                false,
                500,
                "Ocorreu um erro ao excluir a rota."
            );
        endif;

        return apiReturn(
            true,
            200,
            "A rota foi excluída com sucesso."
        );
    }

    public function getMenu(Request $data)
    {
        $arrRoutes = [];
        $profile = $data->user_session->user->profile_id;

        $routes = RouteModel::select(
            'routes.id',
            'routes.title',
            'routes.uri',
            'routes.icon_menu',
            'routes.group',
        )
            ->join('profiles_routes AS pr', 'routes.id', '=', 'pr.route_id')
            ->where('pr.profile_id', $profile)
            ->where('routes.uri', '!=', '/admin')
            ->where('routes.view_menu', 1)
            ->where(function ($query) {
                $query->where('routes.route_id', '=', 0)
                    ->orWhere('routes.group', '=', 1);
            })
            ->orderBy('routes.title')
            ->get();

        if (count($routes)) :
            foreach ($routes as $route) :
                $r['title'] = $route->title;
                $r['uri'] = $route->uri;
                $r['icon_menu'] = $route->icon_menu;

                $routesSub = RouteModel::select(
                    'routes.title',
                    'routes.uri',
                )
                    ->join('profiles_routes AS pr', 'routes.id', '=', 'pr.route_id')
                    ->where('pr.profile_id', $profile)
                    ->where('routes.view_menu', 1)
                    ->where('routes.route_id', $route->id)
                    ->orderBy('routes.title')
                    ->get();

                $r['submenu'] = [];
                if (count($routesSub)) :
                    foreach ($routesSub as $routeSub) :
                        $sub['title'] = $routeSub->title;
                        $sub['uri'] = $routeSub->uri;
                        $r['submenu'][] = $sub;
                    endforeach;
                endif;
                $arrRoutes[] = $r;
            endforeach;
        endif;

        return apiReturn(
            true,
            200,
            '',
            $arrRoutes
        );
    }

    private function deleteProfilesRoutes($route_id)
    {
        $id = $route_id;

        $route = RouteModel::find($id);
        if ($route && $route->profiles) :
            foreach ($route->profiles as $profileRoute) :
                $pr = ProfileRouteModel::find($profileRoute->id);
                $pr->delete();
            endforeach;
        endif;
    }
}
