<?php

namespace App\Http\Middleware;

use App\Support\PersistentLogin;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        PersistentLogin::COOKIE,
        'surabaya_alirsyad',
        'muallimat_presensi',
        'at-tibyan',
    ];
}
