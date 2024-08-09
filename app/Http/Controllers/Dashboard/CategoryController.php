<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CategoryModel;
use Illuminate\Http\Request;
use stdClass;

class CategoryController extends Controller
{
    public function index(Request $data)
    {
        $categories = CategoryModel::orderBy('title')->get();

        $result = new stdClass;
        $result->categories = $categories;
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
                "Categoria não encontrada.",
            );
        endif;

        $category = CategoryModel::find($data->id);
        if (!$category) :
            return apiReturn(
                false,
                404,
                "Categoria não encontrada.",
            );
        endif;

        $result = new stdClass;
        $result->category = $category;
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
            $category = new CategoryModel();
        else :
            $category = CategoryModel::find($id);
        endif;

        $findCategory = CategoryModel::where("title", $data->title)
            ->when($id, function ($query) use ($id) {
                $query->where("id", '!=', $id);
            })->first();

        if ($findCategory) :
            return apiReturn(
                false,
                400,
                "Já existe uma categoria cadastrada com esse título."
            );
        endif;

        /** CADASTRA/ATUALIZA */
        $category->title = $data->title;

        if (!$category->save()) :
            return apiReturn(
                false,
                500,
                "Não foi possível cadastrar uma categoria."
            );
        endif;

        $result = new stdClass;
        $result->new_data = $category;

        return apiReturn(
            true,
            200,
            "A categoria foi salva com sucesso.",
            $result
        );
    }

    public function delete(Request $data)
    {
        if (!$data->id) :
            return apiReturn(
                false,
                404,
                "Nenhuma categoria foi selecionado para ser excluída."
            );
        endif;

        $category = CategoryModel::find($data->id);
        if (!$category) :
            return apiReturn(
                false,
                404,
                "A categoria não foi encontrada."
            );
        endif;

        /** BUSCA POSTS VINCULADOS */
        if (count($category->posts)) :
            return apiReturn(
                false,
                404,
                "Existem posts vinculados a esta categoria."
            );
        endif;

        if (!$category->delete()) :
            return apiReturn(
                false,
                500,
                "Ocorreu um erro ao excluir a categoria."
            );
        endif;

        return apiReturn(
            true,
            200,
            "A categoria foi excluída com sucesso."
        );
    }
}
