<?php

namespace App\Http\Middleware;

use App\Models\UserSAD;
use App\Models\UserSADPermissionSAD;
use App\Models\PermissionSAD;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerfilOperador
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
        $esOperador = false;
        $esAdmin = false;

        if (auth()->user()) {
            $regSADuser = UserSAD::where('usua_ldap_nombre', auth()->user()->dni)
                            ->first();

            //rols SAD de l'usuari
            $rolsUser = UserSADPermissionSAD::where('usro_user', $regSADuser->usua_id)->get();

            $rolAdministrador = env('APP_ROL_ADMINISTRADOR');
            $rolOperador = env('APP_ROL_OPERADOR');

            $operari = PermissionSAD::where('role_nombre', $rolOperador)->first();
            $administrador = PermissionSAD::where('role_nombre', $rolAdministrador)->first();

            Log::info('Comprovar si l\'usuari és operador o administrador:');

            foreach ($rolsUser as $rol) {
                if ($rol->usro_rol_id == $operari->role_id) {
                    $esOperador = true;
                    Log::info('Usuari operador');
                } else if ($rol->usro_rol_id == $administrador->role_id) {
                    $esAdmin = true;
                    Log::info('Usuari administrador');
                }
            }

            if (auth()->check() && ($esOperador || $esAdmin))
                return $next($request);
            else
                return redirect(route('reject'))->with('Alert', "Usuari sense permís per accedir a la secció.");
        } else return redirect('/login');
        
    }
}
