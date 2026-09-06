<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class XlsxWorkbookReader
{
    /** @return array<string, array<int, array<string, mixed>>> */
    public function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Fișierul Excel nu poate fi deschis.');
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheets = [];

            foreach ($this->sheetFiles($zip) as $sheetName => $sheetPath) {
                $xml = $zip->getFromName($sheetPath);
                if ($xml === false) {
                    continue;
                }

                $rows = $this->rows($xml, $sharedStrings);
                if (! isset($rows[4])) {
                    continue;
                }

                $headers = $rows[4];
                foreach ($rows as $rowNumber => $values) {
                    if ($rowNumber <= 4) {
                        continue;
                    }

                    $record = [];
                    foreach ($headers as $column => $header) {
                        if ($header !== null && trim((string) $header) !== '') {
                            $record[trim((string) $header)] = $values[$column] ?? null;
                        }
                    }

                    if (collect($record)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty()) {
                        $sheets[$sheetName][] = $record;
                    }
                }
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $document = $this->xml($xml);
        $namespace = $this->mainNamespace($document);
        $document->registerXPathNamespace('x', $namespace);

        return collect($document->xpath('//x:si') ?: [])->map(function (SimpleXMLElement $item) use ($namespace): string {
            $item->registerXPathNamespace('x', $namespace);

            return collect($item->xpath('.//x:t') ?: [])->map(fn (SimpleXMLElement $text) => (string) $text)->implode('');
        })->all();
    }

    /** @return array<string, string> */
    private function sheetFiles(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relationsXml === false) {
            throw new RuntimeException('Structura workbook-ului este incompletă.');
        }

        $relations = $this->xml($relationsXml);
        $relationsNamespaces = $relations->getNamespaces(true);
        $relationsNamespace = $relationsNamespaces['']
            ?? $relationsNamespaces['rel']
            ?? 'http://schemas.openxmlformats.org/package/2006/relationships';
        $relationMap = [];
        $relations->registerXPathNamespace('rel', $relationsNamespace);
        foreach ($relations->xpath('//rel:Relationship') ?: [] as $relation) {
            $attributes = $relation->attributes();
            $relationMap[(string) $attributes['Id']] = (string) $attributes['Target'];
        }

        $workbook = $this->xml($workbookXml);
        $mainNamespace = $this->mainNamespace($workbook);
        $files = [];
        $workbook->registerXPathNamespace('x', $mainNamespace);

        foreach ($workbook->xpath('//x:sheets/x:sheet') ?: [] as $sheet) {
            $name = (string) $sheet->attributes()['name'];
            $sheetNamespaces = $sheet->getNamespaces(true);
            $relationNamespace = $sheetNamespaces['r']
                ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
            $relationId = (string) $sheet->attributes($relationNamespace)['id'];
            $target = $relationMap[$relationId] ?? null;
            if ($target === null) {
                continue;
            }

            $files[$name] = str_starts_with($target, '/')
                ? ltrim($target, '/')
                : 'xl/'.ltrim($target, '/');
        }

        return $files;
    }

    /** @param array<int, string> $sharedStrings
     * @return array<int, array<int, mixed>>
     */
    private function rows(string $xml, array $sharedStrings): array
    {
        $document = $this->xml($xml);
        $namespace = $this->mainNamespace($document);
        $rows = [];
        $document->registerXPathNamespace('x', $namespace);

        foreach ($document->xpath('//x:sheetData/x:row') ?: [] as $row) {
            $rowNumber = (int) $row->attributes()['r'];
            $row->registerXPathNamespace('x', $namespace);
            foreach ($row->xpath('./x:c') ?: [] as $cell) {
                $reference = (string) $cell->attributes()['r'];
                preg_match('/^([A-Z]+)\d+$/', $reference, $matches);
                $column = $this->columnIndex($matches[1] ?? 'A');
                $type = (string) $cell->attributes()['t'];
                $children = $cell->children($namespace);
                $raw = (string) ($children->v ?? '');

                $value = match ($type) {
                    's' => $sharedStrings[(int) $raw] ?? '',
                    'inlineStr' => $this->inlineText($children->is, $namespace),
                    'b' => $raw === '1',
                    'str' => $raw,
                    default => $raw === '' ? null : (is_numeric($raw) ? (float) $raw : $raw),
                };

                $rows[$rowNumber][$column] = $value;
            }
        }

        return $rows;
    }

    private function inlineText(?SimpleXMLElement $item, string $namespace): string
    {
        if ($item === null) {
            return '';
        }

        $item->registerXPathNamespace('x', $namespace);

        return collect($item->xpath('.//x:t') ?: [])->map(fn (SimpleXMLElement $text) => (string) $text)->implode('');
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function xml(string $contents): SimpleXMLElement
    {
        $xml = simplexml_load_string($contents);
        if ($xml === false) {
            throw new RuntimeException('Fișierul Excel conține XML invalid.');
        }

        return $xml;
    }

    private function mainNamespace(SimpleXMLElement $xml): string
    {
        $namespaces = $xml->getNamespaces(true);

        return $namespaces['x']
            ?? $namespaces['']
            ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    }
}
