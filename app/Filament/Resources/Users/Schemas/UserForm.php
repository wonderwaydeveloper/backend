<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('subscription_plan')
                    ->required()
                    ->default('basic'),
                Toggle::make('is_premium')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                DateTimePicker::make('phone_verified_at'),
                DatePicker::make('date_of_birth'),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('avatar'),
                TextInput::make('cover'),
                Toggle::make('is_online')
                    ->required(),
                DateTimePicker::make('last_seen_at'),
                Toggle::make('is_private')
                    ->required(),
                Toggle::make('is_child')
                    ->required(),
                Toggle::make('two_factor_enabled')
                    ->required(),
                TextInput::make('two_factor_secret'),
                Textarea::make('two_factor_backup_codes')
                    ->columnSpanFull(),
                TextInput::make('backup_codes'),
                TextInput::make('followers_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('following_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('posts_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('email_notifications_enabled')
                    ->required(),
                TextInput::make('google_id'),
                TextInput::make('github_id'),
                TextInput::make('facebook_id'),
                DateTimePicker::make('last_active_at'),
                TextInput::make('notification_preferences'),
                Toggle::make('is_flagged')
                    ->required(),
                Toggle::make('is_suspended')
                    ->required(),
                Toggle::make('is_banned')
                    ->required(),
                DateTimePicker::make('suspended_until'),
                DateTimePicker::make('banned_at'),
            ]);
    }
}
