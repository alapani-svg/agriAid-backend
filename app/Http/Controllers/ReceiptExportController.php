<?php

namespace App\Http\Controllers;

use App\Models\WarehouseReceipt;
use App\Models\Warehouse;
use App\Models\FarmerProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReceiptExportController extends Controller
{
    /**
     * Download a branded agriAid warehouse receipt as PDF.
     * GET /api/receipts/{id}/pdf
     */
    public function exportPdf(Request $request, string $id): Response
    {
        $receipt = WarehouseReceipt::with(['warehouse', 'farmer'])->findOrFail($id);

        $warehouse = $receipt->warehouse;
        $farmer = $receipt->farmer;

        // Generate QR code SVG
        $qrSvg = $this->generateQrSvg($receipt->receipt_number);

        $pdf = Pdf::loadView('exports.receipt', [
            'receipt' => $receipt,
            'warehouse' => $warehouse,
            'farmer' => $farmer,
            'qrSvg' => $qrSvg,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ]);

        $pdf->setPaper('A4', 'portrait');

        $fileName = "agriAid-receipt-{$receipt->receipt_number}.pdf";

        return $pdf->download($fileName);
    }

    private function generateQrSvg(string $data): string
    {
        try {
            $result = Builder::create()
                ->writer(new SvgWriter())
                ->writerOptions([])
                ->data($data)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(150)
                ->margin(0)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->build();

            return $result->getString();
        } catch (\Throwable $e) {
            return '';
        }
    }
}
