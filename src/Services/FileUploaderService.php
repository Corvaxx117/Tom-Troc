<?php

namespace App\Services;

class FileUploaderService
{
    private string $baseUploadDir;
    private string $basePublicPath;

    public function __construct()
    {
        $this->baseUploadDir = __DIR__ . '/../../public/assets/images/uploads/';
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
    public function upload(array $file, ?string $oldFile = null, string $subfolder = 'avatars'): ?string
    {
        // Gestion du chemin
        $uploadDir = $this->baseUploadDir . $subfolder . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = uniqid() . '-' . basename($file['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Supprimer l'ancien fichier si fourni
            if ($oldFile && file_exists($this->baseUploadDir . $oldFile)) {
                unlink($this->baseUploadDir . $oldFile);
            }

            return $this->basePublicPath . $subfolder . '/' . $filename;
        }

        return null;
    }
}
