<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Repos\Interfaces\RoleRepo;

class PermissionController extends AdminController
{
    public function __construct(
        RoleRepo $RoleRepo
    ) {
        $this->RoleRepo = $RoleRepo;
        parent::__construct();
    }

    public function index()
    {
        return view('admin.permissions', [
            'roles' => $this->RoleRepo->getAll('web'),
        ]);
    }
}
