<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();

$html = "
<h1>Test PDF Berhasil</h1>
<p>Dompdf sudah siap digunakan.</p>
";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("test.pdf", ["Attachment" => false]);