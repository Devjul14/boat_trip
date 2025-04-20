<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // Di app/Filament/Resources/UserResource/Pages/CreateUser.php
protected function mutateFormDataBeforeCreate(array $data): array
{
    // Simpan role_id ke variable jika ada
    $this->roleId = $data['role_id'] ?? null;
    
    // Hapus field role_id dari data karena tidak ada di tabel users
    unset($data['role_id']);
    
    return $data;
}

protected function afterCreate(): void
{
    // Assign role yang dipilih
    if ($this->roleId) {
        $role = \Spatie\Permission\Models\Role::find($this->roleId);
        if ($role) {
            $this->record->syncRoles([$role->name]);
            
            // Update field role di tabel users jika perlu
            $this->record->update(['role' => $role->name]);
        }
    }
}
}
