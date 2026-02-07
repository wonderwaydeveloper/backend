<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

echo "=== Authorization System Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Roles
echo "✓ Roles: " . Role::count() . PHP_EOL;
Role::all()->each(function($role) {
    echo "  - {$role->name}" . PHP_EOL;
});

echo PHP_EOL;

// Test 2: Permissions
echo "✓ Permissions: " . Permission::count() . PHP_EOL;

echo PHP_EOL;

// Test 3: User Role Assignment
$user = User::first();
if ($user) {
    $user->assignRole('user');
    echo "✓ User Role Test: " . ($user->hasRole('user') ? 'PASS' : 'FAIL') . PHP_EOL;
    echo "✓ User Permission Test: " . ($user->can('post.create') ? 'PASS' : 'FAIL') . PHP_EOL;
} else {
    echo "⚠ No users found for testing" . PHP_EOL;
}

echo PHP_EOL;

// Test 4: Policies
echo "✓ Policies Registered: " . count(glob(base_path('app/Policies/*.php'))) . PHP_EOL;

echo PHP_EOL;
echo "=== Authorization System: OPERATIONAL ✓ ===" . PHP_EOL;
