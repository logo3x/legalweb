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
                mb_strtoupper($line) === $line && mb_strlen($line) > 3;

            $section->addText(
                $line,
                ['bold' => $isBold, 'size' => $isBold ? 12 : 11],
                ['alignment' => $isBold ? Jc::CENTER : Jc::BOTH, 'spaceAfter' => 120]
            );
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
            $header->addImage(
                storage_path('app/public/'.$firm->logo_path),
                ['width' => 60, 'height' => 60, 'alignment' => Jc::LEFT]
            );
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
