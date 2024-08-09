<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CategoryModel;
use App\Models\PostModel;
use CoffeeCode\Uploader\Image;
use Illuminate\Http\Request;
use stdClass;

class PostController extends Controller
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

    public function getPosts(Request $data)
    {
        $limit = (!$data->limit ? 50 : $data->limit);
        $offset = (!$data->offset ? 0 : $data->offset);
        $search = ($data->search ? $data->search : false);

        $modelPosts = PostModel::when($search, function ($query) use ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
        })->with([
            'category' => function ($query) {
                $query->select(
                    'id',
                    'title',
                );
            }
        ]);

        $totalPosts = $modelPosts->count();

        $posts = $modelPosts
            ->offset($offset)
            ->limit($limit)
            ->orderByDesc('id')
            ->get();

        $result = new stdClass;
        $result->total_posts = intval($totalPosts);
        $result->posts = $posts;

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
                "Post não encontrado.",
            );
        endif;

        $post = PostModel::find($data->id);
        if (!$post) :
            return apiReturn(
                false,
                404,
                "Post não encontrado.",
            );
        endif;

        $result = new stdClass;
        $result->post = $post;
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
        $photo = (isset($_FILES['photo']) ? $_FILES['photo'] : false);

        if (
            !$data->title
            || !$data->type
            || !$data->category
        ) :
            return apiReturn(
                false,
                400,
                "Preencha todos os campos obrigatórios.",
            );
        endif;

        $id = ($data->id ? $data->id : false);
        if (!$data->id) :
            $post = new PostModel();
        else :
            $post = PostModel::find($id);
        endif;

        $post->title = $data->title;
        $post->uri = uri($data->title);
        $post->type = $data->type;
        $post->categorie_id = $data->category;
        $post->status = ($data->status ? 1 : 0);
        $post->description = $data->description;

        if ($photo) :
            // if ($id) :
            //     /** EXCLUI FOTO ANTIGA */
            //     if (
            //         !empty($post->cover)
            //         && file_exists($post->cover)
            //     ) :
            //         unlink($post->cover);
            //     endif;
            // endif;
            $post->cover = $this->photo($photo);
        endif;

        if (!$post->save()) :
            return apiReturn(
                false,
                500,
                "Não foi possível salvar os dados do post."
            );
        endif;

        return apiReturn(
            true,
            200,
            'O post foi salvo com sucesso.',
            $post
        );
    }

    public function delete(Request $data)
    {
        if (!$data->id) :
            return apiReturn(
                false,
                404,
                "Nenhum post foi selecionado para ser excluído."
            );
        endif;

        $post = PostModel::find($data->id);
        if (!$post) :
            return apiReturn(
                false,
                404,
                "O post não foi encontrado."
            );
        endif;

        if (!$post->delete()) :
            return apiReturn(
                false,
                500,
                "Ocorreu um erro ao excluir o post."
            );
        endif;

        return apiReturn(
            true,
            200,
            "O post foi excluído com sucesso."
        );
    }

    private function photo($photo)
    {
        $image = new Image("upload", "posts");

        if (
            empty($photo['type'])
            || !in_array($photo['type'], $image::isAllowed())
        ) :
            return false;
        endif;

        $imageTitle = uniqid(time());
        $upload = $image->upload($photo, $imageTitle, 1200);

        if (!$upload) :
            return false;
        endif;

        return $upload;
    }
}
