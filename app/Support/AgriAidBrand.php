<?php

namespace App\Support;

class AgriAidBrand
{
    /**
     * Materialize public/images/agriAid-logo.png and return absolute path.
     */
    public static function logoPath(): string
    {
        $dir = public_path('images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.'agriAid-logo.png';

        if (! is_file($path) || filesize($path) < 500) {
            $b64 = AgriAidLogoPart0::CHUNK.AgriAidLogoPart1::CHUNK.AgriAidLogoPart2::CHUNK;
            $binary = base64_decode($b64, true);
            if ($binary === false) {
                throw new \RuntimeException('Invalid agriAid logo payload.');
            }
            file_put_contents($path, $binary);
        }

        return $path;
    }
}
