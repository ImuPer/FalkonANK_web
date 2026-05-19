<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;

class AudioConverter
{
    public function convertToMp3(string $inputPath, string $outputPath): string
    {
        $command = sprintf(
            'ffmpeg -i %s -vn -ar 44100 -ac 2 -b:a 192k %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        shell_exec($command);

        return $outputPath;
    }
}