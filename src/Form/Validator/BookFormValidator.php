<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Metroid\Http\Request;

class BookFormValidator implements FormValidatorInterface
{
    private const string TITLE_INPUT_NAME = 'title';
    private const string AUTHOR_INPUT_NAME = 'author';
    private const string DESCRIPTION_INPUT_NAME = 'description';
    private const string IMAGE_INPUT_NAME = 'image_url';

    private array $errors = [];
    private array $data = [];
    private array $files = [];
    private bool $isEditMode = false;

    public function __construct(
        Request $request
    ) {
        $this->data = $request->getAllPost();
        $this->files = $request->files;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function isValid(): bool
    {
        $this->validate();
        return empty($this->errors);
    }

    public function getFormData(): ?array
    {
        return $this->data;
    }

    private function getFile(string $name): ?array
    {
        return isset($this->files[$name]) && is_array($this->files[$name]) ? $this->files[$name] : null;
    }

    public function setEditMode(bool $editMode): void
    {
        $this->isEditMode = $editMode;
    }

    private function validate()
    {
        $this->validateTitle();
        $this->validateAuthor();
        $this->validateDescription();
        $this->validateImage();
    }

    private function validateTitle()
    {
        if (empty($this->data[self::TITLE_INPUT_NAME])) {
            $this->errors[self::TITLE_INPUT_NAME] = 'Le titre est obligatoire';
        }
        if (mb_strlen($this->data[self::TITLE_INPUT_NAME]) < 2 || mb_strlen($this->data[self::TITLE_INPUT_NAME]) > 100) {
            $this->errors[self::TITLE_INPUT_NAME] = 'Le titre doit contenir entre 2 et 100 caractères';
        }
    }

    private function validateAuthor()
    {
        if (empty($this->data[self::AUTHOR_INPUT_NAME])) {
            $this->errors[self::AUTHOR_INPUT_NAME] = 'L’auteur est requis';
        }
        if (mb_strlen($this->data[self::AUTHOR_INPUT_NAME]) > 100) {
            $this->errors[self::AUTHOR_INPUT_NAME] = 'L’auteur ne doit pas dépasser 100 caractères';
        }
    }

    private function validateDescription()
    {
        if (empty($this->data[self::DESCRIPTION_INPUT_NAME])) {
            $this->errors[self::DESCRIPTION_INPUT_NAME] = 'La description est obligatoire';
        }
        if (mb_strlen($this->data[self::DESCRIPTION_INPUT_NAME]) > 1000) {
            $this->errors[self::DESCRIPTION_INPUT_NAME] = 'La description ne peut pas dépasser 1000 caractères';
        }
    }

    private function validateImage()
    {
        $image = $this->getFile(self::IMAGE_INPUT_NAME);
        if (!$image || empty($image['tmp_name'])) {
            if (!$this->isEditMode) {
                $this->errors[self::IMAGE_INPUT_NAME] = 'Aucune image n’a été envoyée.';
            }
            return; // Pas d'image, mais en mode édition → autorisé
        }
        $error = match ($image['error']) {
            UPLOAD_ERR_INI_SIZE => 'Le fichier est trop lourd',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier est trop lourd',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier envoyé',
            UPLOAD_ERR_PARTIAL, UPLOAD_ERR_EXTENSION, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_NO_TMP_DIR => 'Une erreur est survenue durant le transfert de l\'image',
            default => null
        };

        if ($error) {
            $this->errors[self::IMAGE_INPUT_NAME] = $error;
            return;
        }
        if ($image['size'] > 12 * 1024 * 1024) {
            $this->errors[self::IMAGE_INPUT_NAME] = 'Le fichier est trop lourd';
        }
        // Validation MIME type
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($image['tmp_name']);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $this->errors[self::IMAGE_INPUT_NAME] = 'Le fichier doit être une image valide (jpg, png, gif, webp).';
        }
    }
}
