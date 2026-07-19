<?php

namespace App\Services\ConversationMedia;

use App\Contracts\ConversationMedia\ImageNormalizer;
use RuntimeException;

class ImageMagickNormalizer implements ImageNormalizer
{
    public function normalize(string $contents, string $mime): array
    {
        $source = tempnam(sys_get_temp_dir(), 'conversation-media-source-');
        $safe = tempnam(sys_get_temp_dir(), 'conversation-media-safe-');
        $thumb = tempnam(sys_get_temp_dir(), 'conversation-media-thumb-');
        file_put_contents($source, $contents);
        $binary = (string) config('conversation-media.image_magick_binary', 'magick');
        $command = escapeshellcmd($binary).' '.escapeshellarg($source).' -auto-orient -strip -quality 88 '.escapeshellarg($safe.'.jpg');
        $thumbnail = escapeshellcmd($binary).' '.escapeshellarg($source).' -auto-orient -strip -thumbnail 480x480^ -gravity center -extent 480x480 -quality 78 '.escapeshellarg($thumb.'.jpg');
        exec($command, $ignored, $result); exec($thumbnail, $ignored, $thumbnailResult);
        $safeFile = $safe.'.jpg'; $thumbFile = $thumb.'.jpg';
        try {
            if ($result !== 0 || $thumbnailResult !== 0 || ! is_file($safeFile) || ! is_file($thumbFile)) throw new RuntimeException('Image normalization failed.');
            return ['safe' => file_get_contents($safeFile), 'thumbnail' => file_get_contents($thumbFile), 'mime' => 'image/jpeg'];
        } finally {
            foreach ([$source, $safe, $thumb, $safeFile, $thumbFile] as $file) @unlink($file);
        }
    }
}
