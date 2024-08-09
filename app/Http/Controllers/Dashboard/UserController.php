<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ProfileModel;
use App\Models\UserModel;
use App\Models\UserSessionModel;
use App\Providers\BcryptProvider as Hash;

use Illuminate\Http\Request;
use stdClass;

class UserController extends Controller
{
    public function index(Request $data)
    {
        $users = UserModel::select(
            'id',
            'name',
            'email',
            'profile_id'
        )->with([
            'profile' => function ($query) {
                $query->select(
                    'id',
                    'title',
                );
            }
        ])
            ->orderBy('name')
            ->get();

        $profiles = ProfileModel::orderBy('title')->get();

        $result = new stdClass;
        $result->users = $users;
        $result->profiles = $profiles;
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
                "Usuário não encontrado.",
            );
        endif;

        $user = UserModel::find($data->id);
        if (!$user) :
            return apiReturn(
                false,
                404,
                "Usuário não encontrado.",
            );
        endif;

        return apiReturn(
            true,
            200,
            '',
            $user
        );
    }

    public function manager(Request $data)
    {
        if (
            !$data->name
            || !$data->email
            || !$data->profile
        ) :
            return apiReturn(
                false,
                404,
                "Preencha todos os campos obrigatórios."
            );
        endif;

        $id = ($data->id ? $data->id : false);
        $password = false;
        if (!$id) :
            $user = new UserModel();

            if (!$data->password || !$data->password_confirm) :
                return apiReturn(
                    false,
                    400,
                    "Você precisa informar uma senha para cadastrar um usuário."
                );
            endif;
            if ($data->password != $data->password_confirm) :
                return apiReturn(
                    false,
                    400,
                    "As senhas que você informou não são idênticas."
                );
            endif;
            $password = Hash::hash($data->password);
        else :
            $user = UserModel::find($id);
            if ($data->password) :
                if ($data->password != $data->password_confirm) :
                    return apiReturn(
                        false,
                        400,
                        "As senhas que você informou não são idênticas."
                    );
                endif;
                $password = Hash::hash($data->password);
            endif;
        endif;

        /** VERIFICA O FORMATO DE E-MAIL */
        if (!email($data->email)) :
            return apiReturn(
                false,
                400,
                "Preencha um e-mail com formato válido."
            );
        endif;

        /** BUSCA USUÁRIOS EXISTENTES COM MESMO E-MAIL */
        $findUser = UserModel::where("email", $data->email)
            ->when($id, function ($query) use ($id) {
                $query->where("id", '!=', $id);
            })->first();

        if ($findUser) :
            return apiReturn(
                false,
                400,
                "Já existe um usuário cadastrado com esse e-mail."
            );
        endif;

        /** CADASTRA/ATUALIZA */
        $user->name = $data->name;
        $user->email = $data->email;
        $user->profile_id = $data->profile;
        if ($password) :
            $user->password = $password;
        endif;

        if (!$user->save()) :
            return apiReturn(
                false,
                500,
                "Não foi possível salvar os dados do usuário."
            );
        endif;

        return apiReturn(
            true,
            200,
            "Os dados do usuário foram salvos com sucesso.",
        );
    }

    public function delete(Request $data)
    {
        if (!$data->id) :
            return apiReturn(
                false,
                404,
                "Nenhum usuário foi selecionado para ser excluído."
            );
        endif;

        $user = UserModel::find($data->id);
        if (!$user) :
            return apiReturn(
                false,
                404,
                "O usuário não foi encontrado."
            );
        endif;

        if (!$user->delete()) :
            return apiReturn(
                false,
                500,
                "Ocorreu um erro ao excluir o usuário."
            );
        endif;

        return apiReturn(
            true,
            200,
            "O usuário foi excluído com sucesso."
        );
    }

    public function update(Request $data)
    {
        $profiles = ProfileModel::orderBy('title')->get();

        $result = new stdClass;
        $result->profiles = $profiles;
        $result->profile_elements = $data->profile_elements;

        return apiReturn(
            true,
            200,
            '',
            $result
        );
    }

    public function indexPassword(Request $data)
    {
        return apiReturn(
            true,
            200,
            ''
        );
    }

    public function updatePassword(Request $data)
    {
        if (!$data->id) :
            return apiReturn(
                false,
                404,
                'Usuário não encontrado'
            );
        endif;

        if (!$data->password || !$data->password_confirm) :
            return apiReturn(
                false,
                400,
                "Você deve preencher as senhas nos campos abaixo."
            );
        endif;

        if ($data->password != $data->password_confirm) :
            return apiReturn(
                false,
                400,
                "As senhas que você informou não são idênticas."
            );
        endif;

        $password = Hash::hash($data->password);
        $user = UserModel::find($data->id);
        $user->password = $password;

        if (!$user->save()) :
            return apiReturn(
                false,
                500,
                "Não foi possível salvar a sua senha."
            );
        endif;

        return apiReturn(
            true,
            200,
            'Sua senha foi redefinida com sucesso.'
        );
    }

    public function getUserByToken(Request $data)
    {
        if (!$data->token) :
            return apiReturn(
                false,
                401,
                'Token não encontrado.'
            );
        endif;

        $userSession = UserSessionModel::where('token', $data->token)->first();
        if (!$userSession) :
            return apiReturn(
                false,
                401,
                'Usuário não encontrado.'
            );
        endif;

        $user = $userSession->user;
        $user->profile;

        $result = new stdClass;
        $result->id = $user->id;
        $result->name = $user->name;
        $result->email = $user->email;
        $result->profile_id = $user->profile_id;
        $result->profile_title = $user->profile->title;

        return apiReturn(
            true,
            200,
            '',
            $result
        );
    }
}
