<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('username'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('subscription_plan'),
                IconEntry::make('is_premium')
                    ->boolean(),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('phone_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('date_of_birth')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('bio')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('avatar')
                    ->placeholder('-'),
                TextEntry::make('cover')
                    ->placeholder('-'),
                IconEntry::make('is_online')
                    ->boolean(),
                TextEntry::make('last_seen_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_private')
                    ->boolean(),
                IconEntry::make('is_child')
                    ->boolean(),
                IconEntry::make('two_factor_enabled')
                    ->boolean(),
                TextEntry::make('two_factor_secret')
                    ->placeholder('-'),
                TextEntry::make('two_factor_backup_codes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('followers_count')
                    ->numeric(),
                TextEntry::make('following_count')
                    ->numeric(),
                TextEntry::make('posts_count')
                    ->numeric(),
                IconEntry::make('email_notifications_enabled')
                    ->boolean(),
                TextEntry::make('google_id')
                    ->placeholder('-'),
                TextEntry::make('github_id')
                    ->placeholder('-'),
                TextEntry::make('facebook_id')
                    ->placeholder('-'),
                TextEntry::make('last_active_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_flagged')
                    ->boolean(),
                IconEntry::make('is_suspended')
                    ->boolean(),
                IconEntry::make('is_banned')
                    ->boolean(),
                TextEntry::make('suspended_until')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('banned_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
