<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('department_id')
                    ->relationship('department', 'name'),
                Select::make('roles')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => $role->value]))
                    ->multiple()
                    ->preload()
                    ->afterStateHydrated(function (Select $component): void {
                        /** @var Model|null $record */
                        $record = $component->getRecord();

                        $component->state($record?->roles->pluck('name')->toArray() ?? []);
                    }),
            ]);
    }
}
