<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MessageModel;
use Illuminate\Http\Request;
use stdClass;

class MessageController extends Controller
{
    public function index(Request $data)
    {
        $result = new stdClass;
        $result->profile_elements = $data->profile_elements;

        return apiReturn(
            true,
            200,
            '',
            $result
        );
    }

    public function getMessages(Request $data)
    {
        $limit = (!$data->limit ? 50 : $data->limit);
        $offset = (!$data->offset ? 0 : $data->offset);
        $search = ($data->search ? $data->search : false);

        $modelMessages = MessageModel::when($search, function ($query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        });

        $totalMessages = $modelMessages->count();

        $messages = $modelMessages
            ->offset($offset)
            ->limit($limit)
            ->orderByDesc('id')
            ->get();

        $result = new stdClass;
        $result->total_messages = intval($totalMessages);
        $result->messages = $messages;

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

        $message = MessageModel::find($data->id);
        if (!$message) :
            return apiReturn(
                false,
                404,
                "Mensagem não encontrada.",
            );
        endif;

        $result = new stdClass;
        $result->message = $message;
        $result->profile_elements = $data->profile_elements;

        return apiReturn(
            true,
            200,
            '',
            $result
        );
    }

    public function delete(Request $data)
    {
        if (!$data->id) :
            return apiReturn(
                false,
                404,
                "Nenhuma mensagem foi selecionado para ser excluída."
            );
        endif;

        $message = MessageModel::find($data->id);
        if (!$message) :
            return apiReturn(
                false,
                404,
                "A mensagem não foi encontrada."
            );
        endif;

        if (!$message->delete()) :
            return apiReturn(
                false,
                500,
                "Ocorreu um erro ao excluir a mensagem."
            );
        endif;

        return apiReturn(
            true,
            200,
            "A mensagem foi excluída com sucesso."
        );
    }
}
