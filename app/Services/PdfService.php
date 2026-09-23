<?php

namespace App\Services;

/**
 * Generador minimo de PDF A4 con fuentes base Helvetica (WinAnsi),
 * cubre los acentos del espanol sin dependencias externas.
 */
final class PdfService
{
    private float $margin;

    private float $pageW = 595.28;

    private float $pageH = 841.89;

    /** @var string[][] lineas de contenido por pagina */
    private array $pages = [];

    /** @var string[] linea de contenido de la pagina actual */
    private array $current = [];

    public function __construct(?float $margin = null)
    {
        $this->margin = $margin ?? 56;
    }

    public function addPage(): void
    {
        if ($this->current !== []) {
            $this->pages[] = $this->current;
            $this->current = [];
        }
    }

    public function ensurePage(): void
    {
        if ($this->current !== [] || $this->pages !== []) {
            $this->current = [];
        }
    }

    public function text(float $x, float $y, string $text, float $size = 10, string $font = 'normal', string $color = '0 0 0'): void
    {
        $this->current[] = 'BT /F' . ($font === 'bold' ? 2 : 1) . ' ' . $this->num($size) . ' Tf '
            . $color . ' rg '
            . $this->num($x) . ' ' . $this->num($y) . ' Td ('
            . $this->escape($text) . ') Tj ET';
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $width = 0.7, string $color = '0.6 0.6 0.6'): void
    {
        $this->current[] = $color . ' RG ' . $this->num($width) . ' w '
            . $this->num($x1) . ' ' . $this->num($y1) . ' m '
            . $this->num($x2) . ' ' . $this->num($y2) . ' l S';
    }

    public function rect(float $x, float $y, float $w, float $h, string $fill = '0.95 0.95 0.95'): void
    {
        $this->current[] = $fill . ' rg ' . $this->num($x) . ' ' . $this->num($y) . ' '
            . $this->num($w) . ' ' . $this->num($h) . ' re f';
    }

    /**
     * Texto en varias lineas limitado al ancho. Devuelve la nueva coordenada y.
     */
    public function paragraph(float $x, float $y, string $text, float $maxWidth, float $size = 10, float $lineHeight = 14, string $font = 'normal', string $color = '0 0 0'): float
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (strlen($candidate) * $size * 0.50 > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        foreach ($lines as $line) {
            $this->text($x, $y, $line, $size, $font, $color);
            $y -= $lineHeight;
        }
        return $y;
    }

    public function contentWidth(): float
    {
        return $this->pageW - 2 * $this->margin;
    }

    public function build(): string
    {
        $this->addPage();

        $body = '%PDF-1.4' . "\n";
        $offsets = [];

        $objectBodies = [];
        $objectBodies[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objectBodies[2] = '';

        $fonts = [
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        $nextObj = 4;
        $contentsIds = [];
        $pagesIds = [];
        foreach ($this->pages as $idx => $lines) {
            $stream = mb_convert_encoding(implode("\n", $lines), 'windows-1252', 'UTF-8');
            $contentId = ++$nextObj;
            $contentsIds[$idx] = $contentId;
            $objectBodies[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }
        foreach ($this->pages as $idx => $lines) {
            $pageId = ++$nextObj;
            $pagesIds[$idx] = $pageId;
            $objectBodies[$pageId] = '<< /Type /Page /Parent 2 0 R /Contents ' . $contentsIds[$idx] . ' 0 R /MediaBox [0 0 ' . $this->num($this->pageW) . ' ' . $this->num($this->pageH) . '] >>';
        }

        $kids = implode(' ', array_map(fn ($p) => $p . ' 0 R', $pagesIds));
        $objectBodies[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pagesIds) . ' >>';

        ksort($objectBodies);
        $objectBodies = $fonts + $objectBodies;
        ksort($objectBodies);

        $pdf = '%PDF-1.4' . "\n";
        $offset = strlen($pdf);
        $offsets = [];
        foreach ($objectBodies as $num => $objBody) {
            $offsets[$num] = $offset;
            $chunk = $num . ' 0 obj' . "\n" . $objBody . "\nendobj" . "\n";
            $pdf .= $chunk;
            $offset += strlen($chunk);
        }

        $xrefPos = strlen($pdf);
        $count = count($objectBodies) + 1;
        $pdf .= 'xref' . "\n" . '0 ' . $count . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= 'trailer' . "\n"
            . '<< /Size ' . $count . ' /Root 1 0 R >>' . "\n"
            . 'startxref' . "\n" . $xrefPos . "\n" . '%%EOF';

        return $pdf;
    }

    public function output(string $filename): never
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $this->build();
        exit;
    }

    public function pageHeight(): float
    {
        return $this->pageH;
    }

    public function num(float $n): string
    {
        $s = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
        return $s === '' ? '0' : $s;
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}