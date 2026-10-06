<?php

namespace App\Support\Pdf;

/**
 * Penulis PDF minimalis (tanpa dependensi eksternal).
 *
 * Hanya mendukung kebutuhan laporan HR: teks Helvetica (WinAnsi), garis, dan
 * beberapa halaman. Dibuat sendiri agar ekspor PDF tidak menambah dependensi
 * composer dan tetap dapat diuji tanpa ekstensi biner.
 *
 * Batasan yang disengaja: tanpa word-wrap otomatis (teks dipotong di lebar
 * kolom), tanpa gambar, dan tanpa font tertanam.
 */
class SimplePdfWriter
{
    /**
     * @var list<array{texts: list<array{0:string,1:float,2:float,3:float}>, lines: list<array{0:float,1:float,2:float,3:float}>}>
     */
    private array $pages = [];

    public function __construct(
        public readonly float $width = 595.0,
        public readonly float $height = 842.0,
    ) {
        $this->pages[] = ['texts' => [], 'lines' => []];
    }

    public function newPage(): void
    {
        $this->pages[] = ['texts' => [], 'lines' => []];
    }

    public function text(string $text, float $x, float $y, float $size = 10): void
    {
        $this->pages[array_key_last($this->pages)]['texts'][] = [$text, $x, $y, $size];
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->pages[array_key_last($this->pages)]['lines'][] = [$x1, $y1, $x2, $y2];
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    /** Output PDF sebagai string biner. */
    public function output(): string
    {
        $n = count($this->pages);
        $fontObj = 3 + $n * 2;

        $kids = [];
        for ($i = 0; $i < $n; $i++) {
            $kids[] = (3 + $i * 2).' 0 R';
        }

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids)."] /Count {$n} >>";

        foreach ($this->pages as $i => $page) {
            $pageObj = 3 + $i * 2;
            $contentObj = $pageObj + 1;
            $content = $this->contentStream($page);

            $objects[$pageObj] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '
                .$this->num($this->width).' '.$this->num($this->height)
                .'] /Resources << /Font << /F1 '.$fontObj.' 0 R >> >> /Contents '
                .$contentObj.' 0 R >>';
            $objects[$contentObj] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
        }

        $objects[$fontObj] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $num." 0 obj\n".$body."\nendobj\n";
        }

        $maxObj = max(array_keys($objects));
        $size = $maxObj + 1;
        $xrefPos = strlen($pdf);

        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObj; $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF\n";

        return $pdf;
    }

    /**
     * @param  array{texts: list<array{0:string,1:float,2:float,3:float}>, lines: list<array{0:float,1:float,2:float,3:float}>}  $page
     */
    private function contentStream(array $page): string
    {
        $out = "BT\n";

        foreach ($page['texts'] as [$text, $x, $y, $size]) {
            $out .= "/F1 ".$this->num($size)." Tf\n";
            $out .= "1 0 0 1 ".$this->num($x).' '.$this->num($y)." Tm\n";
            $out .= '('.self::escape($text).") Tj\n";
        }

        $out .= "ET\n";

        foreach ($page['lines'] as [$x1, $y1, $x2, $y2]) {
            $out .= "0.8 w\n";
            $out .= $this->num($x1).' '.$this->num($y1).' m '.$this->num($x2).' '.$this->num($y2)." l S\n";
        }

        return $out;
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') ?: '0';
    }

    public static function escape(string $text): string
    {
        // Konversi UTF-8 → Windows-1252 agar cocok dengan /Encoding WinAnsiEncoding.
        $encoded = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

        return strtr($encoded, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => ' ', "\n" => ' ']);
    }
}
