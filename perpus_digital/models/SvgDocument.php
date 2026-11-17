<?php

namespace App\Models;

/**
 * Pembuat dokumen SVG ("Simpan ke Perangkat") untuk detail peminjaman dan detail user.
 */
final class SvgDocument
{
    private const FONT = 'Poppins,Arial,sans-serif';

    /**
     * @param array<int, array{0: string, 1: string}> $rows pasangan [label, nilai]
     */
    public function build(string $title, array $rows, ?string $coverData = null): string
    {
        $width = 1000;
        $rowY = 245;
        $rowH = 58;
        $height = max(610, $rowY + count($rows) * $rowH + 90);
        $font = self::FONT;

        $svg = [];
        $svg[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        $svg[] = '<defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#eef1ff"/><stop offset="1" stop-color="#f8f9ff"/></linearGradient>'
            . '<filter id="shadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="10" stdDeviation="14" flood-color="#5269cf" flood-opacity=".13"/></filter></defs>';
        $svg[] = '<rect width="1000" height="' . $height . '" fill="url(#bg)"/>';
        $svg[] = '<rect x="48" y="38" width="904" height="' . ($height - 76) . '" rx="28" fill="#fff" filter="url(#shadow)"/>';
        $svg[] = '<rect x="48" y="38" width="904" height="12" rx="6" fill="#5269cf"/>';
        $svg[] = '<text x="80" y="105" font-family="' . $font . '" font-size="30" font-weight="700" fill="#20242a">Aksaraya</text>';
        $svg[] = '<text x="80" y="130" font-family="' . $font . '" font-size="14" fill="#8a90a0">Perpustakaan Digital</text>';
        $svg[] = '<text x="80" y="190" font-family="' . $font . '" font-size="25" font-weight="700" fill="#20242a">' . $this->escape($title) . '</text>';
        $svg[] = '<text x="80" y="215" font-family="' . $font . '" font-size="12" fill="#8a90a0">Dokumen digital dari aplikasi Aksaraya</text>';

        if ($coverData !== null) {
            $svg[] = '<defs><clipPath id="coverClip"><rect x="785" y="78" width="115" height="145" rx="12"/></clipPath></defs>';
            $svg[] = '<rect x="785" y="78" width="115" height="145" rx="12" fill="#eef1ff"/>';
            $svg[] = '<image href="' . $this->escape($coverData) . '" x="785" y="78" width="115" height="145" preserveAspectRatio="xMidYMid slice" clip-path="url(#coverClip)"/>';
        }

        $index = 0;
        foreach ($rows as $pair) {
            $y = $rowY + $index * $rowH;
            $fill = $index % 2 === 0 ? '#f4f6ff' : '#ffffff';
            $svg[] = '<rect x="70" y="' . $y . '" width="860" height="48" rx="10" fill="' . $fill . '"/>';
            $svg[] = '<text x="90" y="' . ($y + 29) . '" font-family="' . $font . '" font-size="13" font-weight="600" fill="#333947">' . $this->escape($pair[0]) . '</text>';
            foreach (array_slice($this->wrap((string) $pair[1], 52), 0, 2) as $lineIndex => $line) {
                $svg[] = '<text x="360" y="' . ($y + 23 + $lineIndex * 15) . '" font-family="' . $font . '" font-size="13" fill="#5d6472">' . $this->escape($line) . '</text>';
            }
            $index++;
        }

        $footerY = $rowY + $index * $rowH + 35;
        $svg[] = '<line x1="80" y1="' . $footerY . '" x2="920" y2="' . $footerY . '" stroke="#e2e4eb"/>';
        $svg[] = '<text x="80" y="' . ($footerY + 28) . '" font-family="' . $font . '" font-size="11" fill="#9aa0ae">Disimpan ke perangkat • ' . date('d/m/Y H:i') . '</text>';
        $svg[] = '<text x="920" y="' . ($footerY + 28) . '" text-anchor="end" font-family="' . $font . '" font-size="11" font-weight="600" fill="#5269cf">AKSARAYA</text>';
        $svg[] = '</svg>';

        return implode('', $svg);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** Pecah teks panjang menjadi beberapa baris. */
    private function wrap(string $text, int $max): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['-'];
        }

        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/', $text) ?: [] as $word) {
            $test = $line === '' ? $word : $line . ' ' . $word;
            if (mb_strlen($test) > $max && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $test;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }
}
