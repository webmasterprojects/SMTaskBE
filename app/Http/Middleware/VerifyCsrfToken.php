<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Auth endpoints are excluded from CSRF:
     * - Login: can't be CSRF-attacked (attacker needs the user's credentials)
     * - Logout / refresh / change-password: low-risk; session destruction or
     *   token rotation is acceptable even if triggered cross-site
     */
    protected $except = [
        'api/v1/auth/login',
        'api/v1/auth/logout',
        'api/v1/auth/refresh',
        'api/v1/auth/change-password',
    ];
}
