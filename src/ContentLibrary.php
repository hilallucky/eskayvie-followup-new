<?php
final class ContentLibrary {
    private const ROOT = __DIR__ . '/../storage/content';
    private const TYPES = [
        'csv' => ['text/csv', 'CSV document'],
        'doc' => ['application/msword', 'Word document'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Word document'],
        'gif' => ['image/gif', 'GIF image'],
        'jpeg' => ['image/jpeg', 'JPEG image'],
        'jpg' => ['image/jpeg', 'JPEG image'],
        'pdf' => ['application/pdf', 'PDF document'],
        'png' => ['image/png', 'PNG image'],
        'txt' => ['text/plain; charset=UTF-8', 'Text document'],
        'webp' => ['image/webp', 'WebP image'],
        'xls' => ['application/vnd.ms-excel', 'Excel document'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Excel document'],
    ];

    public static function folders(): array {
        return self::scanFolders(self::ROOT);
    }

    public static function files(?string $folder = null): array {
        $directory = $folder === null || $folder === '' ? self::ROOT : self::resolve($folder, true);
        if ($directory === null) return [];
        $files = self::scanFiles($directory);
        usort($files, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
        return $files;
    }

    private static function scanFiles(string $directory, string $prefix = ''): array {
        $files = [];
        foreach (scandir($directory) ?: [] as $name) {
            if ($name === '.' || $name === '..' || str_starts_with($name, '.')) continue;
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            if (is_dir($path) && !is_link($path)) {
                $relative = $prefix === '' ? $name : $prefix . '/' . $name;
                $files = array_merge($files, self::scanFiles($path, $relative));
                continue;
            }
            if (!is_file($path) || is_link($path)) continue;
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!isset(self::TYPES[$extension])) continue;
            $relative = ($prefix === '' ? '' : $prefix . '/') . $name;
            $files[] = [
                'name' => $name,
                'path' => $relative,
                'type' => self::TYPES[$extension][1],
                'size' => filesize($path),
                'modified' => filemtime($path),
            ];
        }
        return $files;
    }

    public static function file(string $relative): ?array {
        $path = self::resolve($relative, false);
        if ($path === null || !is_file($path) || is_link($path)) return null;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$extension])) return null;
        return [
            'path' => $path,
            'name' => basename($path),
            'extension' => $extension,
            'mime' => self::TYPES[$extension][0],
            'type' => self::TYPES[$extension][1],
            'size' => filesize($path),
            'modified' => filemtime($path),
        ];
    }

    public static function renderPreview(array $file): ?string {
        if (in_array($file['extension'], ['csv'], true)) return self::renderCsv($file['path']);
        if ($file['extension'] === 'xlsx') return self::renderSpreadsheet($file['path']);
        if ($file['extension'] === 'docx') return self::renderDocx($file['path']);
        return null;
    }

    private static function scanFolders(string $directory, string $prefix = ''): array {
        $folders = [];
        foreach (scandir($directory) ?: [] as $name) {
            if ($name === '.' || $name === '..' || str_starts_with($name, '.') || str_contains($name, '/') || str_contains($name, '\\')) continue;
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            if (!is_dir($path) || is_link($path)) continue;
            $relative = $prefix === '' ? $name : $prefix . '/' . $name;
            $folders[] = $relative;
            $folders = array_merge($folders, self::scanFolders($path, $relative));
        }
        return $folders;
    }

    private static function resolve(string $relative, bool $directory): ?string {
        if ($relative === '' || str_contains($relative, "\0") || str_contains($relative, '\\') || str_starts_with($relative, '/')) return null;
        foreach (explode('/', $relative) as $part) {
            if ($part === '' || $part === '.' || $part === '..' || str_starts_with($part, '.')) return null;
        }
        $root = realpath(self::ROOT);
        $path = realpath(self::ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
        if ($root === false || $path === false) return null;
        $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($path, $prefix) || ($directory && !is_dir($path))) return null;
        return $path;
    }

    private static function renderCsv(string $path): string {
        $handle = fopen($path, 'rb');
        if ($handle === false) throw new RuntimeException('The CSV document could not be opened.');
        $rows = [];
        try {
            while (($row = fgetcsv($handle)) !== false && count($rows) < 500) {
                $rows[] = array_slice($row, 0, 50);
            }
        } finally {
            fclose($handle);
        }
        return self::tablePreview($rows, 'CSV preview shows up to 500 rows and 50 columns.');
    }

    private static function renderSpreadsheet(string $path): string {
        if (filesize($path) > 20 * 1024 * 1024) throw new RuntimeException('This spreadsheet is too large to preview. Download it to view the full document.');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $lastRow = min((int)$sheet->getHighestDataRow(), 500);
            $lastColumn = min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()), 50);
            $rows = [];
            for ($row = 1; $row <= $lastRow; $row++) {
                $values = [];
                for ($column = 1; $column <= $lastColumn; $column++) {
                    $values[] = $sheet->getCell([$column, $row])->getFormattedValue();
                }
                $rows[] = $values;
            }
            return self::tablePreview($rows, 'Spreadsheet preview shows up to 500 rows and 50 columns.');
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function renderDocx(string $path): string {
        if (filesize($path) > 20 * 1024 * 1024) throw new RuntimeException('This document is too large to preview. Download it to view the full document.');
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('DOCX preview requires the PHP ZIP extension.');
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('The DOCX document could not be opened.');
        try {
            $index = $zip->locateName('word/document.xml');
            if ($index === false || $zip->statIndex($index)['size'] > 10 * 1024 * 1024) {
                throw new RuntimeException('The DOCX document does not contain a readable document body.');
            }
            $xml = $zip->getFromIndex($index);
        } finally {
            $zip->close();
        }
        $document = new DOMDocument();
        if ($xml === false || !$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw new RuntimeException('The DOCX document body is invalid.');
        }
        $paragraphs = [];
        foreach ($document->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p') as $paragraph) {
            $text = '';
            foreach ($paragraph->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't') as $node) {
                $text .= $node->textContent;
            }
            $paragraphs[] = $text;
        }
        return '<!doctype html><meta charset="utf-8"><style>body{font:16px/1.6 system-ui,sans-serif;color:#1f2937;padding:24px;white-space:pre-wrap}</style><main>' .
            e(implode("\n\n", array_slice($paragraphs, 0, 1000))) . '</main>';
    }

    private static function tablePreview(array $rows, string $limitMessage): string {
        $html = '<!doctype html><meta charset="utf-8"><style>body{font:14px system-ui,sans-serif;color:#1f2937;padding:16px}table{border-collapse:collapse}td,th{border:1px solid #d1d5db;padding:6px 10px;text-align:left;white-space:pre-wrap;max-width:360px;overflow-wrap:anywhere}</style><table>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) $html .= '<td>' . e($cell) . '</td>';
            $html .= '</tr>';
        }
        if (count($rows) === 500) $html .= '<caption>' . e($limitMessage) . '</caption>';
        return $html . '</table>';
    }
}
