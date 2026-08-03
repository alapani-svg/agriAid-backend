<?php

namespace App\Support;

class AgriAidBrand
{
    /**
     * Path to the official circular agriAid logo for emails.
     *
     * Place your exact logo file at:
     *   public/images/agriAid-logo.png
     *
     * (green circle, hand + seedling, white "agriAid" script)
     */
    public static function logoPath(): ?string
    {
        $dir = public_path('images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $candidates = [
            'agriAid-logo.png',
            'agriaid-logo.png',
            'agriAid-logo.jpg',
            'logo.png',
        ];

        foreach ($candidates as $name) {
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if (is_file($path) && filesize($path) > 500) {
                return $path;
            }
        }

        return null;
    }
}
