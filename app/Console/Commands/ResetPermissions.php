<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ResetPermissions extends Command
{
    protected $signature = 'permissions:reset';
    protected $description = 'Reset all roles and permissions';

    public function handle()
    {
        $this->info('Resetting all roles and permissions...');
        
        // Disable foreign key checks to avoid constraint issues
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Truncate all related tables
        \DB::table('role_has_permissions')->truncate();
        \DB::table('model_has_roles')->truncate();
        \DB::table('model_has_permissions')->truncate();
        \DB::table('roles')->truncate();
        \DB::table('permissions')->truncate();
        
        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->info('All roles and permissions have been reset successfully.');
    }
}