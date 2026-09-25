<?php

require "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;

/*
=========================================================
BUAT TEMPLATE EXCEL SPPT PBB
=========================================================
*/

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("DATA SPPT");

/*
=========================================================
HEADER
=========================================================
*/

$header = [
    "NO",
    "NOP",
    "NAMA WAJIB PAJAK",
    "ALAMAT WP",
    "ALAMAT OBJEK",
    "BLOK TANAH",
    "LUAS TANAH",
    "LUAS BANGUNAN",
    "PAJAK TERHITUNG"
];

$sheet->fromArray(
    $header,
    NULL,
    "A1"
);

/*
=========================================================
CONTOH DATA
=========================================================
*/

$sheet->fromArray(
    [
        1,
        "330507001200100520",
        "CONTOH NAMA",
        "CONTOH ALAMAT WP",
        "CONTOH ALAMAT OBJEK",
        "BL RENGGO 1",
        250,
        0,
        19392
    ],
    NULL,
    "A2"
);

/*
=========================================================
PETUNJUK
=========================================================
*/

$sheet->setCellValue(
    "A4",
    "PETUNJUK:"
);

$sheet->setCellValue(
    "A5",
    "1. Kolom NOP adalah KUNCI UTAMA DATA TANAH."
);

$sheet->setCellValue(
    "A6",
    "2. Jangan mengubah NOP untuk tanah yang sama."
);

$sheet->setCellValue(
    "A7",
    "3. Tahun berikutnya cukup upload kembali data dengan NOP yang sama."
);

$sheet->setCellValue(
    "A8",
    "4. Sistem akan UPDATE data SPPT berdasarkan NOP."
);

$sheet->setCellValue(
    "A9",
    "5. Riwayat jual beli, hibah, waris dan kepemilikan tidak ikut dihapus."
);

$sheet->setCellValue(
    "A10",
    "6. Hapus baris CONTOH DATA sebelum melakukan import data sebenarnya."
);

/*
=========================================================
STYLE HEADER
=========================================================
*/

$sheet->getStyle("A1:I1")->getFont()->setBold(true);

$sheet->getStyle("A1:I1")
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getStyle("A1:I1")->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

/*
=========================================================
STYLE CONTOH
=========================================================
*/

$sheet->getStyle("A2:I2")
    ->getBorders()
    ->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

/*
=========================================================
FORMAT NOP
=========================================================
*/

$sheet->getStyle("B2:B1000")
    ->getNumberFormat()
    ->setFormatCode("@");

/*
=========================================================
LEBAR KOLOM
=========================================================
*/

$width = [
    "A" => 8,
    "B" => 22,
    "C" => 25,
    "D" => 40,
    "E" => 40,
    "F" => 20,
    "G" => 15,
    "H" => 18,
    "I" => 20
];

foreach ($width as $kolom => $lebar) {
    $sheet->getColumnDimension($kolom)->setWidth($lebar);
}

/*
=========================================================
FREEZE HEADER
=========================================================
*/

$sheet->freezePane("A2");

/*
=========================================================
DOWNLOAD
=========================================================
*/

$filename = "Template_Data_SPPT_PBB.xlsx";

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=\"$filename\""
);

header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);

$writer->save("php://output");

exit;