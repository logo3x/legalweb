<?php

namespace App\Services;

use App\Models\Firm;
use App\Models\LegalCase;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class DocumentGenerator
{
    public function generateWord(LegalCase $case, string $documentType, string $content): string
    {
        $firm = $case->user->firm;

        $phpWord = new PhpWord;

        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        $this->addHeader($section, $firm, $case);

        $section->addTextBreak(1);

        // Contenido del documento
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                $section->addTextBreak(1);

                continue;
            }

            $isBold = str_starts_with($line, 'SEÑOR') ||
                str_starts_with($line, 'HONORABLE') ||
                str_starts_with($line, 'REF:') ||
                str_starts_with($line, 'ASUNTO:') ||
                str_starts_with($line, 'REFERENCIA:') ||
                str_starts_with($line, 'HECHOS') ||
                str_starts_with($line, 'PRETENSIONES') ||
                str_starts_with($line, 'PETICION') ||
                str_starts_with($line, 'FUNDAMENTOS') ||
                str_starts_with($line, 'PRUEBAS') ||
                str_starts_with($line, 'NOTIFICACIONES') ||
                (mb_strtoupper($line) === $line && mb_strlen($line) > 3);

            $alignment = $isBold ? Jc::CENTER : Jc::BOTH;

            // Detectar placeholders <<<TEXTO>>> y resaltarlos
            if (preg_match('/<<<(.+?)>>>/', $line)) {
                $this->addLineWithHighlights($section, $line, $alignment);
            } else {
                $section->addText(
                    $line,
                    ['bold' => $isBold, 'size' => $isBold ? 12 : 11],
                    ['alignment' => $alignment, 'spaceAfter' => 120]
                );
            }
        }

        $this->addFooter($section, $firm);

        $fileName = 'borrador_'.str_replace(' ', '_', strtolower($documentType)).'_'.$case->case_number.'.docx';
        $filePath = storage_path('app/public/generated/'.$fileName);

        if (! is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $phpWord->save($filePath, 'Word2007');

        return $filePath;
    }

    private function addHeader($section, ?Firm $firm, LegalCase $case): void
    {
        $header = $section->addHeader();

        if ($firm?->logo_path && file_exists(storage_path('app/public/'.$firm->logo_path))) {
            try {
                $logoPath = storage_path('app/public/'.$firm->logo_path);
                $pngPath = $this->ensurePng($logoPath);

                if ($pngPath) {
                    $header->addImage($pngPath, ['width' => 60, 'height' => 60, 'alignment' => Jc::LEFT]);
                }
            } catch (\Exception $e) {
                // Si la imagen falla, continuar sin logo
            }
        }

        if ($firm) {
            $header->addText(
                $firm->name,
                ['bold' => true, 'size' => 14, 'color' => '1E3A5F'],
                ['alignment' => Jc::LEFT]
            );

            $details = [];
            if ($firm->nit) {
                $details[] = 'NIT: '.$firm->nit;
            }
            if ($firm->address) {
                $details[] = $firm->address;
            }
            if ($firm->city) {
                $details[] = $firm->city;
            }
            if ($firm->phone) {
                $details[] = 'Tel: '.$firm->phone;
            }
            if ($firm->email) {
                $details[] = $firm->email;
            }

            if ($details) {
                $header->addText(
                    implode(' | ', $details),
                    ['size' => 8, 'color' => '666666'],
                    ['alignment' => Jc::LEFT]
                );
            }
        }

        $section->addText(
            'Caso: '.$case->case_number.' | '.$case->caseType->name,
            ['size' => 9, 'color' => '999999', 'italic' => true],
            ['alignment' => Jc::RIGHT]
        );

        $section->addText(
            str_repeat('_', 80),
            ['size' => 8, 'color' => 'CCCCCC'],
            ['spaceAfter' => 200]
        );
    }

    private function ensurePng(string $path): ?string
    {
        $pngPath = storage_path('app/public/generated/'.md5($path).'.png');

        if (file_exists($pngPath)) {
            return $pngPath;
        }

        if (! is_dir(dirname($pngPath))) {
            mkdir(dirname($pngPath), 0755, true);
        }

        $image = @imagecreatefromstring(file_get_contents($path));

        if (! $image) {
            return null;
        }

        imagesavealpha($image, true);
        imagepng($image, $pngPath);
        imagedestroy($image);

        return file_exists($pngPath) ? $pngPath : null;
    }

    private function addLineWithHighlights($section, string $line, string $alignment): void
    {
        $textRun = $section->addTextRun(['alignment' => $alignment, 'spaceAfter' => 120]);

        // Dividir la linea en partes normales y placeholders
        $parts = preg_split('/(<<<.+?>>>)/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $part) {
            if (preg_match('/^<<<(.+?)>>>$/', $part, $match)) {
                // Placeholder: fondo amarillo, texto rojo, negrita
                $textRun->addText(
                    '['.$match[1].']',
                    [
                        'bold' => true,
                        'size' => 11,
                        'color' => 'CC0000',
                        'bgColor' => 'FFFF00',
                        'italic' => true,
                    ]
                );
            } elseif ($part !== '') {
                $textRun->addText($part, ['size' => 11]);
            }
        }
    }

    private function addFooter($section, ?Firm $firm): void
    {
        $footer = $section->addFooter();

        $footer->addText(
            'Documento generado por LegalWeb'.($firm ? ' para '.$firm->name : '').' - '.now()->format('d/m/Y H:i'),
            ['size' => 8, 'color' => '999999', 'italic' => true],
            ['alignment' => Jc::CENTER]
        );

        $footer->addText(
            'BORRADOR - Este documento debe ser revisado y ajustado por el abogado antes de su presentacion.',
            ['size' => 8, 'color' => 'CC0000', 'bold' => true],
            ['alignment' => Jc::CENTER]
        );
    }
}
