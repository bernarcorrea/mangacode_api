<?php

use App\Http\Controllers\ErrorController;
use App\Models\ProfileRouteModel;
use App\Models\RouteModel;
use App\Models\UserSessionModel;

function getUserSession($token)
{
    if (!$token) :
        return false;
    endif;

    $userSession = UserSessionModel::select(
        'user_id',
        'id',
        'token',
        'date_start',
        'date_expiry'
    )->with([
        'user' => function ($query) {
            $query->select(
                'id',
                'profile_id',
                'name',
                'email',
            );
        },
        'user.profile' => function ($query) {
            $query->select(
                'id',
                'title'
            );
        },
    ])
        ->where('token', $token)
        ->first();

    if (!$userSession) :
        return false;
    endif;

    $dateToday = new DateTime();
    $dateExpiry = new DateTime($userSession->date_expiry);

    if ($dateToday > $dateExpiry) :
        return false;
    endif;

    return $userSession;
}

function getUserPermission($user_session, $current_route)
{
    $userSession = $user_session;
    $currentRoute = $current_route;

    if (!$userSession || !$currentRoute) :
        return false;
    endif;

    $route = RouteModel::where('uri', $currentRoute)->first();
    if (!$route) :
        return false;
    endif;

    $profileRoute = ProfileRouteModel::where('profile_id', $userSession->user->profile_id)
        ->where('route_id', $route->id)
        ->first();

    if (!$profileRoute) :
        return false;
    endif;

    return true;
}

function apiReturn($status, $code, $message, $data = null)
{
    $result = new stdClass;
    $result->status = $status;
    $result->error = ErrorController::set($code, $message);

    if (!empty($data)) :
        $result->data = $data;
    endif;

    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

function email($email)
{
    $data = (string)$email;
    $format = '/[a-z0-9_\.\-]+@[a-z0-9_\.\-]*[a-z0-9_\.\-]+\.[a-z]{2,4}$/';

    if (preg_match($format, $data)) :
        return true;
    else :
        return false;
    endif;
}

function cpf($cpf)
{
    $data = preg_replace('/[^0-9]/', '', $cpf);

    $digitA = 0;
    $digitB = 0;

    for ($i = 0, $x = 10; $i <= 8; $i++, $x--) {
        $digitA += $data[$i] * $x;
    }

    for ($i = 0, $x = 11; $i <= 9; $i++, $x--) {
        if (str_repeat($i, 11) == $data) {
            return false;
        }
        $digitB += $data[$i] * $x;
    }

    $sumA = (($digitA % 11) < 2) ? 0 : 11 - ($digitA % 11);
    $sumB = (($digitB % 11) < 2) ? 0 : 11 - ($digitB % 11);

    if ($sumA != $data[9] || $sumB != $data[10]) {
        return false;
    } else {
        return true;
    }
}

function getCode($size = 10, $count = 1, $types = "lower_case,upper_case,numbers")
{
    /**
     * $size - the length of the generated password
     * $count - number of passwords to be generated
     * $types - types of characters to be used in the password
     */
    $symbols = array();
    $passwords = array();
    $usedSymbols = '';
    $pass = null;

    $symbols["lower_case"] = 'abcdefghijklmnopqrstuvwxyz';
    $symbols["upper_case"] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $symbols["numbers"] = '1234567890';
    $symbols["special_symbols"] = '!?~@#-_+<>[]{}';

    $characters = explode(",", $types);
    foreach ($characters as $key => $value) :
        $usedSymbols .= $symbols[$value];
    endforeach;
    $symbolsLength = strlen($usedSymbols) - 1;

    if ($count == 1) :
        $pass = null;
        for ($i = 0; $i < $size; $i++) :
            $n = rand(0, $symbolsLength);
            $pass .= $usedSymbols[$n];
        endfor;
        $passwords = $pass;
    else :
        for ($p = 0; $p < $count; $p++) :
            $pass = null;
            for ($i = 0; $i < $size; $i++) :
                $n = rand(0, $symbolsLength);
                $pass .= $usedSymbols[$n];
            endfor;
            $passwords[] = $pass;
        endfor;
    endif;

    return $passwords;
}

function uri($string)
{
    $string = strtolower(trim(preg_replace('/\s+/', ' ', $string)));
    $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    $string = str_replace(' ', '-', $string);
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);

    return $string;
}
