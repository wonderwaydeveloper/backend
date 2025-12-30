# راهنمای پیادهسازی Filament Admin Panel

## نصب و راهاندازی

### 1. نصب Filament

```bash
cd wonderway-backend
composer require filament/filament
php artisan filament:install --panels
```

### 2. ایجاد Admin User

```bash
php artisan make:filament-user
```

### 3. دسترسی به Admin Panel

```
URL: http://localhost:8000/admin
```

## Resources اصلی

### 1. User Management

```bash
php artisan make:filament-resource User --generate
```

```php
// app/Filament/Resources/UserResource.php
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('username')->required()->unique(ignoreRecord: true),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            DatePicker::make('date_of_birth'),
            Toggle::make('is_private')->label('Private Account'),
            Toggle::make('is_child')->label('Child Account'),
            Select::make('roles')->relationship('roles', 'name')->multiple(),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('username')->searchable(),
            TextColumn::make('email')->searchable(),
            BooleanColumn::make('is_private'),
            BooleanColumn::make('is_child'),
            TextColumn::make('posts_count')->counts('posts'),
            TextColumn::make('followers_count')->counts('followers'),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->filters([
            Filter::make('private')->query(fn ($query) => $query->where('is_private', true)),
            Filter::make('children')->query(fn ($query) => $query->where('is_child', true)),
        ]);
    }
}
```

### 2. Post Management

```bash
php artisan make:filament-resource Post --generate
```

```php
// app/Filament/Resources/PostResource.php
class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('user_id')->relationship('user', 'name')->required(),
            Textarea::make('content')->required()->rows(4),
            FileUpload::make('image')->image()->directory('posts'),
            FileUpload::make('video')->acceptedFileTypes(['video/*'])->directory('posts'),
            TextInput::make('gif_url')->url(),
            Toggle::make('is_draft'),
            Toggle::make('is_pinned'),
            Select::make('community_id')->relationship('community', 'name'),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->searchable()->sortable(),
            TextColumn::make('content')->limit(50)->searchable(),
            ImageColumn::make('image'),
            BooleanColumn::make('is_draft'),
            BooleanColumn::make('is_pinned'),
            TextColumn::make('likes_count')->sortable(),
            TextColumn::make('comments_count')->sortable(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->filters([
            Filter::make('drafts')->query(fn ($query) => $query->where('is_draft', true)),
            Filter::make('pinned')->query(fn ($query) => $query->where('is_pinned', true)),
        ]);
    }
}
```

### 3. Community Management

```bash
php artisan make:filament-resource Community --generate
```

### 4. Report Management

```bash
php artisan make:filament-resource Report --generate
```

## Dashboard Widgets

### 1. Stats Overview

```bash
php artisan make:filament-widget StatsOverview --stats-overview
```

```php
// app/Filament/Widgets/StatsOverview.php
class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Total Posts', Post::count())
                ->description('Published posts')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
                
            Stat::make('Active Today', User::whereDate('last_seen_at', today())->count())
                ->description('Users active today')
                ->descriptionIcon('heroicon-m-eye')
                ->color('warning'),
                
            Stat::make('Reports', Report::where('status', 'pending')->count())
                ->description('Pending reports')
                ->descriptionIcon('heroicon-m-flag')
                ->color('danger'),
        ];
    }
}
```

### 2. Users Chart

```bash
php artisan make:filament-widget UsersChart --chart
```

```php
// app/Filament/Widgets/UsersChart.php
class UsersChart extends ChartWidget
{
    protected static ?string $heading = 'User Registrations';
    
    protected function getData(): array
    {
        $data = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data->pluck('count')->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
}
```

## Custom Pages

### 1. Analytics Dashboard

```bash
php artisan make:filament-page Analytics
```

```php
// app/Filament/Pages/Analytics.php
class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.analytics';
    
    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            UsersChart::class,
        ];
    }
}
```

### 2. System Settings

```bash
php artisan make:filament-page Settings
```

## Navigation Customization

```php
// app/Providers/Filament/AdminPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->colors([
            'primary' => Color::Amber,
        ])
        ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
        ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
        ->pages([
            Pages\Dashboard::class,
        ])
        ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
        ->widgets([
            Widgets\AccountWidget::class,
            Widgets\FilamentInfoWidget::class,
        ])
        ->middleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ])
        ->authMiddleware([
            Authenticate::class,
        ]);
}
```

## Security & Permissions

### 1. Role-based Access

```php
// در Resource ها
public static function canViewAny(): bool
{
    return auth()->user()->hasRole('admin');
}

public static function canCreate(): bool
{
    return auth()->user()->can('create_posts');
}
```

### 2. Policy Integration

```php
// app/Policies/PostPolicy.php
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'moderator']);
    }
    
    public function delete(User $user, Post $post): bool
    {
        return $user->hasRole('admin') || $user->id === $post->user_id;
    }
}
```

## Advanced Features

### 1. Bulk Actions

```php
// در PostResource
public static function table(Table $table): Table
{
    return $table
        ->bulkActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                BulkAction::make('approve')
                    ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))
                    ->requiresConfirmation(),
            ]),
        ]);
}
```

### 2. Global Search

```php
// در Resource ها
public static function getGloballySearchableAttributes(): array
{
    return ['name', 'email', 'username'];
}
```

### 3. Notifications

```php
// در Actions
->action(function (array $data, Post $record): void {
    $record->update($data);
    
    Notification::make()
        ->title('Post updated successfully')
        ->success()
        ->send();
})
```

## Performance Optimization

### 1. Eager Loading

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->with(['user', 'community']);
}
```

### 2. Caching

```php
protected function getStats(): array
{
    return Cache::remember('admin.stats', 300, function () {
        return [
            // stats calculation
        ];
    });
}
```

## مراحل پیادهسازی

### هفته 1: Setup و User Management
- [x] نصب Filament
- [x] ایجاد Admin User
- [x] UserResource
- [x] Basic Dashboard

### هفته 2: Content Management
- [x] PostResource
- [x] CommentResource
- [x] CommunityResource
- [x] ReportResource

### هفته 3: Analytics و Polish
- [x] Dashboard Widgets
- [x] Analytics Page
- [x] Permissions
- [x] Testing

## دسترسی و URL ها

```
Admin Panel: http://localhost:8000/admin
Dashboard: http://localhost:8000/admin/dashboard
Users: http://localhost:8000/admin/users
Posts: http://localhost:8000/admin/posts
```

این پیادهسازی Admin Panel کاملی برای مدیریت پلتفرم WonderWay فراهم میکند.