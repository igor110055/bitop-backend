<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class AuthenticatedController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth:api');
        $this->middleware('userlock');
    }
}
