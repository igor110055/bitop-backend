<?php

namespace App\Console\Commands;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Illuminate\Console\Command;
use App\Repos\Interfaces\RoleRepo;
use App\Models\User;

class InitialAdminAuthorizationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'initial:admin-authorization';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initial system permission and admin role';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->config = config('permission.web.categories');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::transaction(function () {
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            Role::query()->delete();
            Permission::query()->delete();
            $this->initialRolesAndPermissions();
            $this->initialUserRoles();
        });
    }

    protected function initialRolesAndPermissions()
    {
        $permissions = $this->definePermissions();
        dump("Initial permissions", $permissions);

        Role::create(['guard_name' => 'web', 'name' => 'super-admin']);
        $admin = Role::create(['guard_name' => 'web', 'name' => 'admin']);
        $assistant= Role::create(['guard_name' => 'web', 'name' => 'assistant']);
        $viewer = Role::create(['guard_name' => 'web', 'name' => 'viewer']);

        $admin_permissions = [
            'edit-users',
            'edit-accounts',
            'verify-users',
            'edit-auth',
            'view-auth',
            'edit-groups',
            'edit-advertisements',
            'edit-orders',
            'edit-limitations',
            'edit-announcements',
            'edit-agencies',
            'edit-agents',
            'edit-configs',
            'edit-bank-accounts',
            'edit-merchants',
        ];

        $assistant_permissions = [
            'verify-users',
            'edit-announcements',
            'edit-bank-accounts',
        ];

        foreach ($admin_permissions as $permission) {
            $admin->givePermissionTo($permission);
        }

        foreach ($assistant_permissions as $permission) {
            $assistant->givePermissionTo($permission);
        }
    }

    protected function definePermissions()
    {
        $permissions = [];
        foreach ($this->config as $group_permissions) {
            $permissions = array_merge($permissions, $group_permissions);
        }
        foreach ($permissions as $per) {
            Permission::create(['guard_name' => 'web', 'name' => $per]);
        }
        return $permissions;
    }

    protected function initialUserRoles()
    {
        $ROOT_ID = env('ROOT_ID');
        $super = User::find('00000000000000');
        $super->syncRoles(['super-admin']);

        $users = User::where('id', '!=', $ROOT_ID)
            ->where('is_admin', true)
            ->get()
            ->each(function ($user, $key) {
                $user->syncRoles(['viewer']);
            });
    }
}
