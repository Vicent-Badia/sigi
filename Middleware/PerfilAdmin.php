<?php

namespace App\Http\Middleware;

use App\Models\UserSAD;
use App\Models\PermissionSAD;
use App\Models\UserSADPermissionSAD;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerfilAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $esAdmin = false;

        if (auth()->user()) {

            $regSADuser = UserSAD::where('usua_ldap_nombre', auth()->user()->dni)
                            ->first();

            //rols SAD de l'usuari
            $rolsUser = UserSADPermissionSAD::where('usro_user', $regSADuser->usua_id)->get();

            $rolAdministrador = env('APP_ROL_ADMINISTRADOR');
            $administrador = PermissionSAD::where('role_nombre', $rolAdministrador)->first();

            foreach ($rolsUser as $rol) {
                if ($rol->usro_rol_id == $administrador->role_id) {
                    $esAdmin = true;
                    Log::info('Usuari administrador');
                }
            }

            //Comprovar si l'usuari té permís d'administrador
            if (auth()->check() && $esAdmin)
                return $next($request);
            else
                return redirect(route('reject'))->with('Alert', "Usuari sense permís d'administrador");

        } else return redirect('/login');      
    }
}
