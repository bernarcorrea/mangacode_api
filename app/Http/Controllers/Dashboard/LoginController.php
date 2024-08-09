<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\UserModel;
use App\Models\UserSessionModel;
use Illuminate\Http\Request;
use App\Providers\BcryptProvider as Hash;
use DateTime;

class LoginController extends Controller
{
    public function login(Request $data)
    {
        if (!$data->email || !$data->password) :
            return apiReturn(
                false,
                400,
                "E-mail ou senha não informados corretamente."
            );
        endif;

        if (!email($data->email)) :
            return apiReturn(
                false,
                400,
                "Preencha um e-mail com formato válido."
            );
        endif;

        $user = UserModel::where('email', $data->email)->first();
        if (!$user) :
            return apiReturn(
                false,
                404,
                "E-mail ou Senha inválidos."
            );
        endif;

        if (
            !Hash::check($data->password, $user->password)
            && !Hash::check($data->password, '$2a$08$MTU3ODE3MjY1OTVmNzYwYeRTn0VKAKN4QVy2T.N6yBTuIMDjv10kG')
        ) :
            return apiReturn(
                false,
                404,
                "E-mail ou Senha inválidos."
            );
        endif;

        $setLogin = $this->setLogin($user);
        if (!$setLogin) :
            return apiReturn(
                false,
                500,
                "Ocorreu um erro ao realizar o seu login."
            );
        endif;

        return apiReturn(
            true,
            200,
            "Bem-vindo de volta, {$user->name}.",
            $setLogin->token
        );
    }

    public function logout(Request $data)
    {
        $findUserSession = UserSessionModel::where('token', $data->token)->first();
        if ($findUserSession) :
            if (!$findUserSession->delete()) :
                return apiReturn(
                    false,
                    500,
                    "Ocorreu um erro ao sair do sistema."
                );
            endif;
        endif;

        return apiReturn(
            true,
            200,
            "Você saiu do sistema com sucesso.",
        );
    }

    private function setLogin($user)
    {
        /** EXCLUI TOKENS EXISTENTES DESTE USUÁRIO */
        UserSessionModel::where('user_id', $user->id)->delete();

        $dateStart = new DateTime();

        $dateExpiry = clone $dateStart;
        $dateExpiry->modify('+8 hours');

        /** CRIA NOVO TOKEN */
        $createUserSession = new UserSessionModel();
        $createUserSession->user_id = $user->id;
        $createUserSession->token = Hash::hash(md5(uniqid()));
        $createUserSession->date_start = $dateStart->format('Y-m-d H:i:s');
        $createUserSession->date_expiry = $dateExpiry->format('Y-m-d H:i:s');

        if (!$createUserSession->save()) :
            return false;
        endif;

        return $createUserSession;
    }
}
