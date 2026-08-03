<?php

namespace App\Support;

class AgriAidBrand
{
    /**
     * Official circular agriAid logo for transactional emails.
     * Writes public/images/agriAid-logo.png from embedded payload if missing.
     */
    public static function logoPath(): ?string
    {
        $dir = public_path('images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.'agriAid-logo.png';

        // Prefer a manually placed high-res file; otherwise materialize embedded logo
        if (! is_file($path) || filesize($path) < 500) {
            $b64 = AgriAidLogoPart0::CHUNK.AgriAidLogoPart1::CHUNK.AgriAidLogoPart2::CHUNK;
            $binary = base64_decode($b64, true);
            if ($binary === false) {
                return null;
            }
            file_put_contents($path, $binary);
        }

        return is_file($path) ? $path : null;
    }
}
