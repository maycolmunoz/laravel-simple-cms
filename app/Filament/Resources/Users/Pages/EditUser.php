<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => $this->record->is(auth()->user())),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $role = $data['role'] ?? null;
        unset($data['role']);

        $record->fill($data);

        if ($role !== null) {
            $this->guardAgainstLastAdminDemotion($record, $role);
            $record->role = $role;
        }

        $record->save();

        return $record;
    }

    /**
     * Block demoting the final remaining admin (typically oneself) out of the Admin role,
     * which would otherwise lock all users out of user management with no in-app recovery.
     */
    protected function guardAgainstLastAdminDemotion(User $record, mixed $newRole): void
    {
        $demotingFromAdmin = $record->role === UserRole::Admin
            && $newRole !== UserRole::Admin
            && $newRole !== UserRole::Admin->value;

        if (! $demotingFromAdmin) {
            return;
        }

        $otherAdmins = User::where('role', UserRole::Admin)
            ->whereKeyNot($record->getKey())
            ->exists();

        if (! $otherAdmins) {
            throw ValidationException::withMessages([
                'data.role' => 'At least one administrator must remain. Promote another user to admin first.',
            ]);
        }
    }
}
