<?php

namespace App\Services;

class FileUploaderService
{
    private string $baseDir;
    private string $basePublicPath;

    public function __construct()
    {
        $this->baseDir = __DIR__ . '/../../public/assets/images/uploads/';
        $this->basePublicPath = 'uploads/'; // utilisé avec IMG_PATH
    }

    /**
     * Upload un fichier et retourne le chemin relatif à IMG_PATH
     *
     * @param array $file L'entrée $_FILES['nom']
     * @param string|null $oldFile Ancien fichier à supprimer (chemin relatif depuis /uploads/)
     * @param string $type Dossier cible (avatars, books, etc.)
     * @return string|null
     */
    public function upload(array $file, ?string $oldFile = null, string $type = 'avatars'): ?string
    {
        if (empty($file['tmp_name'])) {
            return null;
        }

        $uploadDir = $this->baseDir . $type . '/';
        $relativePath = $this->basePublicPath . $type . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        if ($oldFile && file_exists($this->baseDir . $oldFile)) {
            unlink($this->baseDir . $oldFile);
        }

        $filename = uniqid() . '-' . basename($file['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $relativePath . $filename; // ex: uploads/books/filename.jpg
        }

        return null;
    }
}
