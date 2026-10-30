<?php
final class ContentLibrary {
    private const ROOT = __DIR__ . '/../storage/content-files';
    private const MAX_FILE_SIZE = 26214400;
    private const TYPES = [
        'csv' => ['text/csv', 'CSV document'],
        'doc' => ['application/msword', 'Word document'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Word document'],
        'gif' => ['image/gif', 'GIF image'],
        'jpeg' => ['image/jpeg', 'JPEG image'],
        'jpg' => ['image/jpeg', 'JPEG image'],
        'pdf' => ['application/pdf', 'PDF document'],
        'png' => ['image/png', 'PNG image'],
        'ppt' => ['application/vnd.ms-powerpoint', 'PowerPoint document'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'PowerPoint document'],
        'txt' => ['text/plain; charset=UTF-8', 'Text document'],
        'webp' => ['image/webp', 'WebP image'],
        'xls' => ['application/vnd.ms-excel', 'Excel document'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Excel document'],
    ];

    public static function folders(bool $approvedOnly = false): array {
        $statusCondition = $approvedOnly ? " AND status = 'Approved'" : '';
        $rows = Database::pdo()->query("SELECT id, parent_id, name FROM bc_items WHERE kind = 'folder'$statusCondition ORDER BY no, name")->fetchAll();
        $byParent = [];
        foreach ($rows as $row) $byParent[$row['parent_id'] ?? 'root'][] = $row;
        return self::folderNodes($byParent, null);
    }

    private static function folderNodes(array $byParent, ?string $parentId): array {
        $nodes = [];
        foreach ($byParent[$parentId ?? 'root'] ?? [] as $row) {
            $row['children'] = self::folderNodes($byParent, $row['id']);
            $nodes[] = $row;
        }
        return $nodes;
    }

    public static function children(?string $parentId, bool $approvedOnly = false): array {
        $statusCondition = $approvedOnly ? " AND status = 'Approved'" : '';
        $query = Database::pdo()->prepare(
            "SELECT id, parent_id, name, kind, mime_type, size, modified_at, owner, status, starred, shared, stored_name, url, thumbnail_url, description
             FROM bc_items WHERE parent_id <=> ?$statusCondition ORDER BY (kind = 'folder') DESC, name"
        );
        $query->execute([$parentId]);
        return $query->fetchAll();
    }

    public static function viewItems(string $view, bool $approvedOnly = false): array {
        $condition = match ($view) {
            'starred' => 'i.starred = 1',
            'shared' => 'i.shared = 1',
            default => "i.kind <> 'folder'",
        };
        if ($approvedOnly) $condition .= " AND i.status = 'Approved'";
        return Database::pdo()->query(
            "SELECT i.id, i.parent_id, i.name, i.kind, i.mime_type, i.size, i.modified_at, i.owner, i.status, i.starred, i.shared, i.stored_name, i.url, i.thumbnail_url, i.description, p.name AS parent_name
             FROM bc_items i LEFT JOIN bc_items p ON p.id = i.parent_id
             WHERE $condition ORDER BY i.modified_at DESC, i.name"
        )->fetchAll();
    }

    public static function find(string $id, bool $approvedOnly = false): ?array {
        if (!self::validId($id)) return null;
        $query = Database::pdo()->prepare('SELECT * FROM bc_items WHERE id = ?' . ($approvedOnly ? " AND status = 'Approved'" : ''));
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public static function createFolder(string $name, ?string $parentId, string $owner): string {
        $name = self::validateName($name);
        self::validateParent($parentId);
        self::assertUniqueName($name, $parentId);
        $id = self::newId();
        $statement = Database::pdo()->prepare(
            "INSERT INTO bc_items (id, parent_id, name, kind, modified_at, owner, status) VALUES (?, ?, ?, 'folder', ?, ?, 'Approved')"
        );
        $statement->execute([$id, $parentId, $name, gmdate('Y-m-d\TH:i:s\Z'), $owner]);
        return $id;
    }

    public static function createLink(string $name, string $url, ?string $parentId, string $owner): string {
        $name = self::validateName($name);
        $url = trim($url);
        $parts = parse_url($url);
        if (strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL) || !is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Enter a valid link starting with http:// or https://.');
        }
        self::validateParent($parentId);
        self::assertUniqueName($name, $parentId);
        $id = self::newId();
        $statement = Database::pdo()->prepare(
            "INSERT INTO bc_items (id, parent_id, name, kind, modified_at, owner, status, url)
             VALUES (?, ?, ?, 'link', ?, ?, 'Approved', ?)"
        );
        $statement->execute([$id, $parentId, $name, gmdate('Y-m-d\TH:i:s\Z'), $owner, $url]);
        return $id;
    }

    public static function upload(array $upload, ?string $parentId, string $owner): string {
        self::validateParent($parentId);
        [$name, $mime, $size, $storedName, $destination] = self::receiveUpload($upload);
        $id = self::newId();
        try {
            self::assertUniqueName($name, $parentId);
            $statement = Database::pdo()->prepare(
                "INSERT INTO bc_items (id, parent_id, name, kind, mime_type, size, modified_at, owner, status, stored_name)
                 VALUES (?, ?, ?, 'file', ?, ?, ?, ?, 'Ready', ?)"
            );
            $statement->execute([$id, $parentId, $name, $mime, $size, gmdate('Y-m-d\TH:i:s\Z'), $owner, $storedName]);
        } catch (Throwable $error) {
            if (is_file($destination) && !unlink($destination)) {
                Logger::write('ERROR', 'Could not remove an uploaded file after its database insert failed.', __FILE__, __LINE__);
            }
            throw $error;
        }
        return $id;
    }

    public static function uploadMany(array $uploads, array $relativePaths, ?string $parentId, string $owner): array {
        self::validateParent($parentId);
        $names = $uploads['name'] ?? null;
        if (!is_array($names) || $names === []) throw new InvalidArgumentException('Choose one or more files to upload.');

        $uploaded = [];
        $errors = [];
        foreach (array_keys($names) as $index) {
            $upload = [];
            foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $field) {
                $upload[$field] = $uploads[$field][$index] ?? null;
            }

            $displayName = (string)($upload['name'] ?? 'Selected file');
            try {
                $relativePath = $relativePaths[$index] ?? $uploads['full_path'][$index] ?? $displayName;
                if (!is_string($relativePath)) throw new InvalidArgumentException('The selected folder path is invalid.');
                $segments = self::validateRelativePath($relativePath, $displayName);
                $destinationId = $parentId;
                foreach (array_slice($segments, 0, -1) as $folderName) {
                    $folder = self::childByName($folderName, $destinationId);
                    if ($folder && $folder['kind'] !== 'folder') {
                        throw new InvalidArgumentException('A file already uses the name "' . $folderName . '" in the destination.');
                    }
                    $destinationId = $folder
                        ? $folder['id']
                        : self::createFolder($folderName, $destinationId, $owner);
                }
                $upload['name'] = $segments[count($segments) - 1];
                self::upload($upload, $destinationId, $owner);
                $uploaded[] = implode('/', $segments);
            } catch (InvalidArgumentException $error) {
                $errors[] = ['name' => $displayName, 'message' => $error->getMessage()];
            } catch (Throwable $error) {
                Logger::write('ERROR', 'Bank Content bulk upload failed for "' . $displayName . '": ' . $error->getMessage(), __FILE__, __LINE__);
                $errors[] = ['name' => $displayName, 'message' => 'The file could not be saved. Please try again.'];
            }
        }
        return ['uploaded' => $uploaded, 'errors' => $errors];
    }

    public static function replaceFile(string $id, array $upload, string $owner): void {
        $item = self::find($id);
        if (!$item || $item['kind'] !== 'file') throw new InvalidArgumentException('Choose a valid file to replace.');
        [$name, $mime, $size, $storedName, $destination] = self::receiveUpload($upload);
        try {
            self::assertUniqueName($name, $item['parent_id'], $id);
            $statement = Database::pdo()->prepare(
                "UPDATE bc_items SET name = ?, mime_type = ?, size = ?, modified_at = ?, owner = ?, status = 'Ready', stored_name = ? WHERE id = ?"
            );
            $statement->execute([$name, $mime, $size, gmdate('Y-m-d\TH:i:s\Z'), $owner, $storedName, $id]);
        } catch (Throwable $error) {
            if (is_file($destination) && !unlink($destination)) {
                Logger::write('ERROR', 'Could not remove a replacement upload after its database update failed.', __FILE__, __LINE__);
            }
            throw $error;
        }
        $oldPath = self::storedPath((string)($item['stored_name'] ?? ''));
        if ($oldPath !== null && is_file($oldPath) && !unlink($oldPath)) {
            Logger::write('ERROR', 'Could not remove replaced Bank Content file "' . $item['stored_name'] . '".', __FILE__, __LINE__);
        }
    }

    public static function update(string $id, string $name, string $status): void {
        $item = self::find($id);
        if (!$item) throw new InvalidArgumentException('The selected item no longer exists.');
        $name = self::validateName($name);
        if (!in_array($status, ['Approved', 'In review', 'Ready'], true)) {
            throw new InvalidArgumentException('Choose a valid status.');
        }
        self::assertUniqueName($name, $item['parent_id'], $id);
        $statement = Database::pdo()->prepare('UPDATE bc_items SET name = ?, status = ?, modified_at = ? WHERE id = ?');
        $statement->execute([$name, $status, gmdate('Y-m-d\TH:i:s\Z'), $id]);
    }

    public static function delete(string $id): void {
        $item = self::find($id);
        if (!$item) throw new InvalidArgumentException('The selected item no longer exists.');
        $storedNames = self::descendantStoredNames($id);
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $statement = $db->prepare('DELETE FROM bc_items WHERE id = ?');
            $statement->execute([$id]);
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
        }
        foreach ($storedNames as $storedName) {
            $path = self::storedPath($storedName);
            if ($path !== null && is_file($path) && !unlink($path)) {
                Logger::write('ERROR', 'Could not remove deleted Bank Content file "' . $storedName . '".', __FILE__, __LINE__);
            }
        }
    }

    public static function file(string $id, bool $approvedOnly = false): ?array {
        $item = self::find($id, $approvedOnly);
        if (!$item || $item['kind'] !== 'file') return null;
        $path = self::storedPath((string)($item['stored_name'] ?? ''));
        if ($path === null || !is_file($path)) return null;
        $extension = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$extension])) return null;
        $item['path'] = $path;
        $item['extension'] = $extension;
        $item['mime'] = self::TYPES[$extension][0];
        $item['type'] = self::TYPES[$extension][1];
        $item['size'] = filesize($path);
        return $item;
    }

    public static function renderPreview(array $file): ?string {
        if ($file['extension'] === 'csv') return self::renderCsv($file['path']);
        if ($file['extension'] === 'xlsx') return self::renderSpreadsheet($file['path']);
        if ($file['extension'] === 'docx') return self::renderDocx($file['path']);
        return null;
    }

    public static function maxFileSize(): int {
        return self::MAX_FILE_SIZE;
    }

    private static function descendantStoredNames(string $id): array {
        $names = [];
        foreach (self::children($id) as $child) {
            if ($child['kind'] === 'folder') {
                $names = array_merge($names, self::descendantStoredNames($child['id']));
            } elseif ($child['kind'] === 'file' && is_string($child['stored_name'])) {
                $names[] = $child['stored_name'];
            }
        }
        $item = self::find($id);
        if ($item && $item['kind'] === 'file' && is_string($item['stored_name'])) $names[] = $item['stored_name'];
        return $names;
    }

    private static function validateParent(?string $parentId): void {
        if ($parentId === null) return;
        $parent = self::find($parentId);
        if (!$parent || $parent['kind'] !== 'folder') throw new InvalidArgumentException('Choose a valid destination folder.');
    }

    private static function childByName(string $name, ?string $parentId): ?array {
        $statement = Database::pdo()->prepare('SELECT id, kind FROM bc_items WHERE parent_id <=> ? AND name = ? LIMIT 1');
        $statement->execute([$parentId, $name]);
        return $statement->fetch() ?: null;
    }

    private static function validateRelativePath(string $path, string $fallbackName): array {
        if ($path === '') $path = $fallbackName;
        if (str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:/', $path)) {
            throw new InvalidArgumentException('The selected folder path is invalid.');
        }
        $segments = explode('/', $path);
        foreach ($segments as $segment) self::validateName($segment);
        return $segments;
    }

    private static function receiveUpload(array $upload): array {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
            throw new InvalidArgumentException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The selected file exceeds the server upload size limit.',
                UPLOAD_ERR_PARTIAL => 'The file upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
                default => 'The file could not be uploaded. Please try again.',
            });
        }
        $tmp = (string)($upload['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) throw new InvalidArgumentException('Invalid uploaded file.');
        $size = (int)($upload['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_FILE_SIZE) throw new InvalidArgumentException('Files must be between 1 byte and 25 MB.');
        $name = self::validateName(basename((string)($upload['name'] ?? '')));
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$extension])) throw new InvalidArgumentException('This file type is not supported.');
        $mime = self::detectMime($tmp, $extension);
        $storedName = self::newId() . '.' . $extension;
        $root = self::storageRoot();
        if (!is_dir($root) && !mkdir($root, 0750, true) && !is_dir($root)) {
            throw new RuntimeException('The content storage directory could not be created.');
        }
        $destination = $root . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($tmp, $destination)) throw new RuntimeException('The uploaded file could not be saved.');
        return [$name, $mime, $size, $storedName, $destination];
    }

    private static function assertUniqueName(string $name, ?string $parentId, ?string $exceptId = null): void {
        $statement = Database::pdo()->prepare(
            'SELECT id FROM bc_items WHERE parent_id <=> ? AND name = ? AND (? IS NULL OR id <> ?) LIMIT 1'
        );
        $statement->execute([$parentId, $name, $exceptId, $exceptId]);
        if ($statement->fetchColumn()) throw new InvalidArgumentException('An item with this name already exists in the selected folder.');
    }

    private static function validateName(string $name): string {
        $name = trim($name);
        if ($name === '' || $name === '.' || $name === '..' || mb_strlen($name) > 180 || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, "\0")) {
            throw new InvalidArgumentException('Enter a valid name up to 180 characters.');
        }
        return $name;
    }

    private static function detectMime(string $path, string $extension): string {
        $detected = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $expected = self::TYPES[$extension][0];
        $officeZip = in_array($extension, ['docx', 'xlsx', 'pptx'], true) && $detected === 'application/zip';
        $legacyOffice = in_array($extension, ['doc', 'xls', 'ppt'], true) && in_array($detected, ['application/octet-stream', 'application/x-ole-storage'], true);
        $plainText = in_array($extension, ['csv', 'txt'], true) && $detected === 'text/plain';
        if ($detected !== $expected && !$officeZip && !$legacyOffice && !$plainText) {
            throw new InvalidArgumentException('The file contents do not match the selected file type.');
        }
        return $expected;
    }

    private static function storedPath(string $storedName): ?string {
        if (!preg_match('/^[a-f0-9-]{36}\.[a-z0-9]{1,10}$/i', $storedName)) return null;
        $root = realpath(self::storageRoot());
        $path = realpath(self::storageRoot() . DIRECTORY_SEPARATOR . $storedName);
        if ($root === false || $path === false || !str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) return null;
        return $path;
    }

    private static function storageRoot(): string {
        return self::ROOT;
    }

    private static function validId(string $id): bool {
        return (bool)preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $id);
    }

    private static function newId(): string {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }

    private static function renderCsv(string $path): string {
        $handle = fopen($path, 'rb');
        if ($handle === false) throw new RuntimeException('The CSV document could not be opened.');
        $rows = [];
        try {
            while (($row = fgetcsv($handle)) !== false && count($rows) < 500) $rows[] = array_slice($row, 0, 50);
        } finally {
            fclose($handle);
        }
        return self::tablePreview($rows, 'CSV preview shows up to 500 rows and 50 columns.');
    }

    private static function renderSpreadsheet(string $path): string {
        if (filesize($path) > self::MAX_FILE_SIZE) throw new RuntimeException('This spreadsheet is too large to preview. Download it to view the full document.');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $lastRow = min((int)$sheet->getHighestDataRow(), 500);
            $lastColumn = min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()), 50);
            $rows = [];
            for ($row = 1; $row <= $lastRow; $row++) {
                $values = [];
                for ($column = 1; $column <= $lastColumn; $column++) $values[] = $sheet->getCell([$column, $row])->getFormattedValue();
                $rows[] = $values;
            }
            return self::tablePreview($rows, 'Spreadsheet preview shows up to 500 rows and 50 columns.');
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function renderDocx(string $path): string {
        if (filesize($path) > self::MAX_FILE_SIZE) throw new RuntimeException('This document is too large to preview. Download it to view the full document.');
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
            foreach ($paragraph->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't') as $node) $text .= $node->textContent;
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
