<?php

declare(strict_types=1);

namespace App\Services\Glc\Curriculum;

use InvalidArgumentException;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser;

final class TextExtractor
{
    public function extract(string $path, string $format): string
    {
        return match (mb_strtolower($format)) {
            'pdf' => $this->extractPdf($path),
            'docx' => $this->extractDocx($path),
            'txt' => $this->extractTxt($path),
            default => throw new InvalidArgumentException(sprintf('Unsupported format [%s].', $format)),
        };
    }

    private function extractPdf(string $path): string
    {
        $text = (new Parser)->parseFile($path)->getText();

        return $this->normalize($text);
    }

    private function extractDocx(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $parts = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $parts[] = $this->elementText($element);
            }
        }

        return $this->normalize(implode("\n", array_filter($parts, fn (string $part): bool => $part !== '')));
    }

    private function extractTxt(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read file [%s].', $path));
        }

        return $this->normalize($contents);
    }

    private function elementText(AbstractElement $element): string
    {
        if ($element instanceof Table) {
            $parts = [];

            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $parts[] = $this->elementText($cellElement);
                    }
                }
            }

            return implode("\n", array_filter($parts, fn (string $part): bool => $part !== ''));
        }

        if ($element instanceof AbstractContainer) {
            $parts = [];

            foreach ($element->getElements() as $child) {
                $parts[] = $this->elementText($child);
            }

            return implode('', $parts);
        }

        if ($element instanceof Text || $element instanceof Link) {
            return (string) $element->getText();
        }

        if ($element instanceof Title) {
            $text = $element->getText();

            return $text instanceof AbstractElement ? $this->elementText($text) : (string) $text;
        }

        if ($element instanceof ListItem) {
            return $element->getTextObject()->getText() ?? '';
        }

        if ($element instanceof TextBreak) {
            return "\n";
        }

        return '';
    }

    private function normalize(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);

        return mb_trim($normalized);
    }
}
