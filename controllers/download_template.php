<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
require_once '../config/security.php';

if (!is_logged_in() || !has_role('admin')) {
    header("Location: ../index.php");
    exit();
}

$type = $_GET['type'] ?? 'siswa';
$filename = ($type == 'guru') ? 'Template_Import_Guru.xlsx' : 'Template_Import_Siswa.xlsx';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set Header
$sheet->setCellValue('A1', 'Nama Lengkap');
$sheet->setCellValue('B1', 'NIS / NIP');
$sheet->setCellValue('C1', 'Kelas / Mapel');
$sheet->setCellValue('D1', 'Tempat Lahir');
$sheet->setCellValue('E1', 'Tanggal Lahir (YYYY-MM-DD)');
$sheet->setCellValue('F1', 'Password (Kosongkan jika default)');

// Example Data
$sheet->setCellValue('A2', 'Budi Santoso');
$sheet->setCellValue('B2', ($type == 'guru') ? '198001012005011001' : '20240001');
$sheet->setCellValue('C2', ($type == 'guru') ? 'Matematika' : '10A');
$sheet->setCellValue('D2', 'Jakarta');
$sheet->setCellValue('E2', '1995-05-20');
$sheet->setCellValue('F2', '');

// Style header
$headerRange = 'A1:F1';
$sheet->getStyle($headerRange)->getFont()->setBold(true);
$sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('E2F2FF');

// Auto size columns
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
