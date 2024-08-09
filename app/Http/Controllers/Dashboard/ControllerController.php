<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ControllerModel;
use Illuminate\Http\Request;
use stdClass;

class ControllerController extends Controller
{
    public function index(Request $data)
    {
        $controllers = ControllerModel::orderBy('title')->get();

        $result = new stdClass;
        $result->controllers = $controllers;
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
                "Controlador não encontrado.",
            );
        endif;

        $controller = ControllerModel::find($data->id);
        if (!$controller) :
            return apiReturn(
                false,
                404,
                "Controlador não encontrado.",
            );
        endif;

        return apiReturn(
            true,
            200,
            '',
            $controller
        );
    }

    public function manager(Request $data)
    {
        if (!$data->title) :
            return apiReturn(
                false,
                404,
                "Preencha todos os campos obrigatórios."
            );
        endif;

        $id = ($data->id ? $data->id : false);
        if (!$id) :
            $controller = new ControllerModel();
        else :
            $controller = ControllerModel::find($id);
        endif;

        $findRoute = ControllerModel::where("title", $data->title)
            ->when($id, function ($query) use ($id) {
                $query->where("id", '!=', $id);
            })->first();

        if ($findRoute) :
            return apiReturn(
                false,
                400,
                "Já existe um controlador cadastrada com esse título."
            );
        endif;

        /** CADASTRA/ATUALIZA */
        $controller->title = $data->title;

        if (!$controller->save()) :
            return apiReturn(
                false,
                500,
                "Não foi possível cadastrar um controlador."
            );
        endif;

        return apiReturn(
            true,
            200,
            "O controlador foi salvo com sucesso.",
        );
    }

    public function delete(Request $data)
    {
        if (!$data->id) :
            return apiReturn(
                false,
                404,
                "Nenhum controlador foi selecionado para ser excluído."
            );
        endif;

        $controller = ControllerModel::find($data->id);
        if (!$controller) :
            return apiReturn(
                false,
                404,
                "O controlador não foi encontrado."
            );
        endif;

        if (!$controller->delete()) :
            return apiReturn(
                false,
                500,
                "Ocorreu um erro ao excluir o controlador."
            );
        endif;

        return apiReturn(
            true,
            200,
            "O controlador foi excluído com sucesso."
        );
    }
}
