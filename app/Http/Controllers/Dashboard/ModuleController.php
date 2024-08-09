<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ControllerModel;
use App\Models\ElementModel;
use App\Models\ModuleModel;
use Illuminate\Http\Request;
use stdClass;

class ModuleController extends Controller
{
    public function index(Request $data)
    {
        $modules = ModuleModel::orderBy('title')->get();

        $result = new stdClass;
        $result->modules = $modules;
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
                "Módulo não encontrado.",
            );
        endif;

        $module = ModuleModel::find($data->id);
        if (!$module) :
            return apiReturn(
                false,
                404,
                "Módulo não encontrado.",
            );
        endif;

        $result = new stdClass;
        $result->module = $module;
        $result->elements = $module->elements;
        $result->profile_elements = $data->profile_elements;

        return apiReturn(
            true,
            200,
            '',
            $result
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
            $module = new ModuleModel();
        else :
            $module = ModuleModel::find($id);
        endif;

        $findRoute = ModuleModel::where("title", $data->title)
            ->when($id, function ($query) use ($id) {
                $query->where("id", '!=', $id);
            })->first();

        if ($findRoute) :
            return apiReturn(
                false,
                400,
                "Já existe um módulo cadastrado com esse título."
            );
        endif;

        /** CADASTRA/ATUALIZA */
        $module->application_id = 2;
        $module->title = $data->title;

        if (!$module->save()) :
            return apiReturn(
                false,
                500,
                "Não foi possível cadastrar um módulo."
            );
        endif;

        /** GERENCIA ELEMENTOS PARA O MÓDULO */
        $this->managerElements($module->id, $data->elements);

        return apiReturn(
            true,
            200,
            "O módulo foi salvo com sucesso.",
        );
    }

    public function delete(Request $data)
    {
        if (!$data->id) :
            return apiReturn(
                false,
                404,
                "Nenhum módulo foi selecionado para ser excluído."
            );
        endif;

        $module = ModuleModel::find($data->id);
        if (!$module) :
            return apiReturn(
                false,
                404,
                "O módulo não foi encontrado."
            );
        endif;

        /** EXCLUI TODOS OS ELEMENTOS */
        $elements = $module->elements;
        if (count($elements)) :
            foreach ($elements as $elem) :
                $el = ElementModel::find($elem->id);
                $el->delete();
            endforeach;
        endif;

        if (!$module->delete()) :
            return apiReturn(
                false,
                500,
                "Ocorreu um erro ao excluir o módulo."
            );
        endif;

        return apiReturn(
            true,
            200,
            "O módulo foi excluído com sucesso."
        );
    }

    private function managerElements($module_id, $elements)
    {
        $id = $module_id;

        $module = ModuleModel::find($id);
        $savedElements = $module->elements->pluck('title')->toArray();

        $elementsToAdd = array_diff($elements, $savedElements);
        $elementsToRemove = array_diff($savedElements, $elements);

        /** INSERE NOVOS ELEMENTOS */
        if (count($elementsToAdd)) :
            foreach ($elementsToAdd as $elemAdd) :
                $newElement = new ElementModel();
                $newElement->module_id = $id;
                $newElement->title = $elemAdd;
                $newElement->save();
            endforeach;
        endif;

        /** REMOVE ELEMENTOS */
        if (count($elementsToRemove)) :
            foreach ($elementsToRemove as $elemRemov) :
                ElementModel::where('module_id', $id)
                    ->where('title', $elemRemov)
                    ->first()->delete();
            endforeach;
        endif;
    }
}
