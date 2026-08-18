<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranslateController
{
    public function translate(Request $request): JsonResponse
    {
        $text = $request->input('text', '');
        $targetLang = $request->input('targetLang', 'fr');
        $sourceLang = $request->input('sourceLang', 'en');

        $translated = $targetLang === 'fr'
            ? "[Traduction] {$text}"
            : "[Translation] {$text}";

        return response()->json([
            'translatedText' => $translated,
            'provider' => 'Agri-Aid Neural Translation',
            'sourceLang' => $sourceLang,
            'targetLang' => $targetLang,
        ]);
    }
}
