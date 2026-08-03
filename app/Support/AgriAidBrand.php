<?php

namespace App\Support;

class AgriAidBrand
{
    /**
     * Absolute path to the official agriAid logo used in transactional emails.
     * Place the file at: public/images/agriAid-logo.png
     */
    public static function logoPath(): ?string
    {
        $path = public_path('images'.DIRECTORY_SEPARATOR.'agriAid-logo.png');

        if (is_file($path) && filesize($path) > 0) {
            return $path;
        }

        // Common alternate filenames
        foreach (['agriAid-logo.jpg', 'agriaid-logo.png', 'logo.png'] as $name) {
            $alt = public_path('images'.DIRECTORY_SEPARATOR.$name);
            if (is_file($alt) && filesize($alt) > 0) {
                return $alt;
            }
        }

        return null;
    }
}
