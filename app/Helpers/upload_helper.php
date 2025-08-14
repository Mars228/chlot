<?php
use CodeIgniter\HTTP\Files\UploadedFile;

if (! function_exists('handle_logo_upload')) {
    /**
     * Upload LOGO do /public/assets/img
     * Zwraca ścieżkę WEB, np. "assets/img/abcd.png"
     */
    function handle_logo_upload(?UploadedFile $file): ?string
    {
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
        $exts = ['png','jpg','jpeg','svg'];
        $ext  = strtolower($file->getExtension());
        if (! in_array($ext, $exts, true)) {
            return null;
        }
        $newName = $file->getRandomName();
        $target  = rtrim(FCPATH, '/\\') . '/assets/img'; // FCPATH = /public
        if (! is_dir($target)) {
            mkdir($target, 0775, true);
        }
        $file->move($target, $newName);
        return 'assets/img/' . $newName; // ścieżka WEB do <img src="/...">
    }
}

if (! function_exists('handle_public_upload')) {
    /**
     * Upload ogólny do /public/uploads[/subdir]
     * Zwraca ścieżkę WEB, np. "uploads/csv/file.csv"
     */
    function handle_public_upload(?UploadedFile $file, string $subdir = ''): ?string
    {
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
        $newName = $file->getRandomName();
        $base    = rtrim(FCPATH, '/\\') . '/uploads';
        $target  = $base . ($subdir ? '/' . trim($subdir, '/ ') : '');
        if (! is_dir($target)) {
            mkdir($target, 0775, true);
        }
        $file->move($target, $newName);
        return 'uploads' . ($subdir ? '/' . trim($subdir, '/ ') : '') . '/' . $newName;
    }
}
