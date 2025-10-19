<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planting;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class ReportController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');

        // Ambil data penanaman sesuai bulan dan tahun
        $plantings = Planting::with('growthStages')
            ->whereMonth('planted_at', $month)
            ->whereYear('planted_at', $year)
            ->get();

        // Parameter ketiga adalah array konfigurasi mpdf
        $pdf = Pdf::loadView('pdf.planting-report', [
            'plantings' => $plantings,
            'month' => $month,
            'year' => $year,
        ], [], [
            'format' => 'A4-L', // A4 Landscape
        ]);

        return $pdf->stream("laporan_toge_{$month}_{$year}.pdf");
    }
}
