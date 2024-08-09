<?php

namespace App\Http\Controllers;

class ErrorController extends Controller
{
    static public function set($code, $message = null)
    {
        $error = [];
        $error['errcode'] = $code;
        $error['errinfo'] = self::get($code)['message_status'];
        $error['description'] = self::get($code)['description'];
        $error['message'] = $message;

        $error = (object) $error;
        return $error;
    }

    static private function get($error = null)
    {
        $arr = [
            400 => [
                "message_status" => "Solicitação incorreta",
                "description" => "Não é possível processar a solicitação porque ela está malformada ou incorreta.",
            ],
            401 => [
                "message_status" => "Não Autorizado",
                "description" => "As informações de autenticação necessárias estão ausentes ou não são válidas para o recurso.",
            ],
            404 => [
                "message_status" => "Não encontrado",
                "description" => "Não foi possível encontrar um resultado para a solicitação.",
            ],
            500 => [
                "message_status" => "Erro interno do servidor",
                "description" => "Ocorreu um erro de servidor interno ao processar a solicitação.",
            ],
            200 => [
                "message_status" => "Processada com Sucesso",
                "description" => "A solicitação foi processada com sucesso.",
            ],
        ];

        if (!empty($error)) :
            return $arr[$error];
        else :
            return $arr;
        endif;
    }
}
