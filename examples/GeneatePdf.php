<?php

use Bigdanhe\Phpdf\PhpDf;

$pdf = new PhpDf('mpdf');
$pdf->setPaper('A4')
    ->setDpi(300)
    ->loadHtml('<h1>Hello World</h1>', 'h1 { color: blue; }')
    ->render('output.pdf');
