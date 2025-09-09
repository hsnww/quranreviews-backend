<?php

namespace App\Services;

use Mpdf\Mpdf;
use Illuminate\View\Factory;

class PdfExportService
{
protected Factory $view;

public function __construct(Factory $view)
{
$this->view = $view;
}

public function generateReviewPlan(array $plans)
{
$html = $this->view->make('exports.review-plan-pdf', compact('plans'))->render();

$mpdf = new Mpdf([
'mode' => 'utf-8',
'format' => 'A4',
'orientation' => 'P',
'default_font' => 'amiri',
]);

$mpdf->WriteHTML($html);
return $mpdf->Output('review-plan.pdf', \Mpdf\Output\Destination::INLINE);
}
}
