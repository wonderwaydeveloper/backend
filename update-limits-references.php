<?php

$replacements = [
    "config('monetization.roles" => "config('limits.roles",
    "config('monetization.creator_fund" => "config('limits.creator_fund",
    "config('monetization.advertisements" => "config('limits.advertisements",
    "config('pagination." => "config('limits.pagination.",
    "config('polls." => "config('limits.polls.",
    "config('posts." => "config('limits.posts.",
];

echo "🔄 Updating limits domain references...\n\n";

// Find all files
$files = [];
exec('dir /s /b app\*.php 2>nul', $files);

$updatedCount = 0;
$totalReplacements = 0;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $fileReplacements = 0;
    
    foreach ($replacements as $old => $new) {
        $count = 0;
        $content = str_replace($old, $new, $content, $count);
        $fileReplacements += $count;
        $totalReplacements += $count;
    }
    
    if ($fileReplacements > 0) {
        file_put_contents($file, $content);
        $relativePath = str_replace(__DIR__ . '\\', '', $file);
        echo "✅ Updated $relativePath ($fileReplacements replacements)\n";
        $updatedCount++;
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Update completed!\n";
echo "   Files updated: $updatedCount\n";
echo "   Total replacements: $totalReplacements\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
