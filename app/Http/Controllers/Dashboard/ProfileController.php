<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ElementModel;
use App\Models\ModuleModel;
use App\Models\ProfileElementModel;
use App\Models\ProfileModel;
use Illuminate\Http\Request;
use stdClass;

class ProfileController extends Controller
{
    public function index(Request $data)
    {
        $profiles = ProfileModel::orderBy('title')->get();

        $items = ModuleModel::select(
            'id',
            'title'
        )->with([
            'elements' => function ($query) {
                $query->select(
                    'id',
                    'module_id',
                    'title'
                )->orderBy('title');
            }
        ])
            ->orderBy('title')
            ->get();

        $result = new stdClass;
        $result->profiles = $profiles;
        $result->items = $items;
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
                "Perfil não encontrado.",
            );
        endif;

        $profile = ProfileModel::find($data->id);
        if (!$profile) :
            return apiReturn(
                false,
                404,
                "Perfil não encontrado.",
            );
        endif;

        $result = new stdClass;
        $result->profile = $profile;
        $result->elements = $profile->elements->pluck('element_id')->toArray();

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
            $profile = new ProfileModel();
        else :
            $profile = ProfileModel::find($id);
        endif;

        $findProfile = ProfileModel::where("title", $data->title)
            ->when($id, function ($query) use ($id) {
                $query->where("id", '!=', $id);
            })->first();

        if ($findProfile) :
            return apiReturn(
                false,
                400,
                "Já existe um perfil cadastrado com esse título."
            );
        endif;

        /** CADASTRA/ATUALIZA */
        $profile->title = $data->title;

        if (!$profile->save()) :
            return apiReturn(
                false,
                500,
                "Não foi possível cadastrar o perfil."
            );
        endif;

        /** GERENCIA PERMISSÕES DE ELEMENTOS PARA O PERFIL */
        $this->managerProfileElements($profile->id, $data->elements);

        return apiReturn(
            true,
            200,
            "O perfil foi salvo com sucesso.",
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

    private function managerProfileElements($profile_id, $elements)
    {
        $id = $profile_id;

        $profile = ProfileModel::find($id);
        $getElements = $profile->elements;

        /** EXCLUI TODAS AS PERMISSÕES DE ELEMENTOS */
        if ($getElements) :
            foreach ($getElements as $elemP) :
                $profileElement = ProfileElementModel::find($elemP->id);
                $profileElement->delete();
            endforeach;
        endif;

        /** INSERE NOVOS ELEMENTOS */
        if (count($elements)) :
            foreach ($elements as $elemAdd) :
                $newElement = new ProfileElementModel();
                $newElement->profile_id = $id;
                $newElement->element_id = $elemAdd;
                $newElement->save();
            endforeach;
        endif;
    }
}
