<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Di app/Filament/Resources/UserResource/Pages/EditUser.php
protected function mutateFormDataBeforeFill(array $data): array
{
    // Tambahkan data role_id untuk form
    $roleId = $this->record->roles->first()?->id;
    $data['role_id'] = $roleId;
    
    return $data;
}

protected function mutateFormDataBeforeSave(array $data): array
{
    // Simpan role_id ke variable
    $this->roleId = $data['role_id'] ?? null;
    
    // Hapus field role_id dari data
    unset($data['role_id']);
    
    return $data;
}

protected function afterSave(): void
{
    // Sync role yang dipilih
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
