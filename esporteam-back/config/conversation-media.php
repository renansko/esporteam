<?php

return [
    'disk' => env('CONVERSATION_MEDIA_DISK', 'local'),
    'image_magick_binary' => env('CONVERSATION_MEDIA_IMAGE_MAGICK_BINARY', 'magick'),
    'clamav_socket' => env('CONVERSATION_MEDIA_CLAMAV_SOCKET', 'tcp://clamav:3310'),
    'aws_region' => env('CONVERSATION_MEDIA_AWS_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    'aws_key' => env('CONVERSATION_MEDIA_AWS_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
    'aws_secret' => env('CONVERSATION_MEDIA_AWS_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
    'moderation_min_confidence' => env('CONVERSATION_MEDIA_MODERATION_MIN_CONFIDENCE', 80),
];
