<?php

namespace Database\Seeders;

use App\Models\UploadLimit;
use Illuminate\Database\Seeder;

class UploadLimitSeeder extends Seeder
{
    public function run(): void
    {
        $limits = [
            [
                'type' => 'post',
                'max_files' => 4,
                'max_file_size' => 5120, // 5MB
                'allowed_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov'],
                'max_total_size' => 20480, // 20MB
                'is_video_allowed' => true,
                'max_video_duration' => 60, // 1 minute
                'max_video_size' => 10240, // 10MB
            ],
            [
                'type' => 'article',
                'max_files' => 10,
                'max_file_size' => 10240, // 10MB
                'allowed_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
                'max_total_size' => 51200, // 50MB
                'is_video_allowed' => false,
                'max_video_duration' => 0,
                'max_video_size' => 0,
            ],
            [
                'type' => 'message',
                'max_files' => 5,
                'max_file_size' => 10240, // 10MB
                'allowed_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'pdf', 'doc', 'docx'],
                'max_total_size' => 20480, // 20MB
                'is_video_allowed' => true,
                'max_video_duration' => 300, // 5 minutes
                'max_video_size' => 5120, // 5MB
            ],
            [
                'type' => 'comment',
                'max_files' => 1,
                'max_file_size' => 2048, // 2MB
                'allowed_mimes' => ['jpg', 'jpeg', 'png', 'gif'],
                'max_total_size' => 2048, // 2MB
                'is_video_allowed' => false,
                'max_video_duration' => 0,
                'max_video_size' => 0,
            ],
        ];

        foreach ($limits as $limit) {
            UploadLimit::create($limit);
        }
    }
}