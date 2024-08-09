<?php

namespace App\Http\Middleware;

use App\Models\ProfileElementModel;
use App\Models\RouteModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class UserPermission
{
    public function handle(Request $request, Closure $next)
    {
        $userSession = getUserSession($request->token);

        if (!$userSession) :
            $response = apiReturn(
                false,
                401,
                "login_expiry"
            );
            return new Response($response, 401);
        endif;

        $currentRoute = Route::current()->uri();
        $currentRoute = (
            strpos($currentRoute, 'api') !== false
            ? str_replace('api', '', $currentRoute)
            : $currentRoute
        );

        if (!getUserPermission($userSession, $currentRoute)) :
            $response = apiReturn(
                false,
                401,
                "permission_denied"
            );
            return new Response($response, 401);
        endif;

        $request->merge([
            'user_session' => $userSession,
            'profile_elements' => $this->elementsByRoute(
                $currentRoute,
                $userSession->user->profile_id
            )
        ]);

        return $next($request);
    }

    private function elementsByRoute($route_uri, $user_profile)
    {
        $uri = $route_uri;
        $profile = $user_profile;
        $arrElementsProfile = [];

        /** BUSCA ELEMENTOS BASEADOS NA ROTA ACESSADA */
        $route = RouteModel::where('uri', $uri)->first();
        $elementsByRoute = $route->module->elements;

        /** BUSCA ELEMENTOS DO PERFIL DE USUÁRIO LOGADO */
        $elementsByProfile = ProfileElementModel::where('profile_id', $profile)
            ->pluck('element_id')->toArray();

        if ($elementsByRoute) :
            foreach ($elementsByRoute as $elemR) :
                if (in_array($elemR->id, $elementsByProfile)) :
                    $arrElementsProfile[] = $elemR->title;
                endif;
            endforeach;
        endif;

        return $arrElementsProfile;
    }
}
