<?php

$replacements = [
    "config('validation." => "config('content.validation.",
    "config('media." => "config('content.media.",
];

echo "🔄 Updating content domain references...\n\n";

$files = [];
exec('dir /s /b app\*.php 2>nul', $files);

$updatedCount = 0;
$totalReplacements = 0;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
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
