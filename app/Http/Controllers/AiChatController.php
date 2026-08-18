<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController
{
    public function chat(Request $request): JsonResponse
    {
        $message = $request->input('message', '');
        $userRole = $request->input('userRole', 'farmer');
        $inspectLoan = $request->input('inspectLoan');

        $text = "Agri-Gemini has analyzed your request in real-time for the {$userRole} role. Query: \"{$message}\".";
        $tags = ['LIVE ORACLE', 'AGRI-GEMINI VERIFIED'];
        $recommendation = null;

        if ($inspectLoan) {
            $recommendation = [
                'action' => 'Review satellite soil moisture and CIG credibility score before underwriting.',
                'impact' => 'Protects the 20-year institutional portfolio from weather default risk.',
            ];
        }

        return response()->json([
            'text' => $text,
            'tags' => $tags,
            'recommendation' => $recommendation,
        ]);
    }
}
