<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Model\UserModel;
use App\Security\User;
use Metroid\Http\Request;

/**
 * Valide les données du formulaire d'inscription ou de modification d'utilisateur.
 * @package App\Form\Validator
 */
class UserFormValidator implements FormValidatorInterface
{
    private const NAME_INPUT_NAME = 'name';
    private const EMAIL_INPUT_NAME = 'email';
    private const PASSWORD_INPUT_NAME = 'password';
    private const IS_ADMIN_INPUT_NAME = 'is_admin';
    private const AVATAR_INPUT_NAME = 'avatar';

    private UserModel $userModel;
    private array $errors = [];
    private array $data = [];
    private array $files = [];
    private bool $isEditMode = false;
    private ?User $currentUser = null;

    public function __construct(
        private Request $request
    ) {
        $this->userModel = new UserModel();
        $this->data = $request->getAllPost();
        $this->files = $request->files;
    }

    public function isValid(): bool
    {
        $this->validate();
        return empty($this->errors);
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function getFormData(): ?array
    {
        return $this->data;
    }

    public function setEditMode(bool $editMode): void
    {
        $this->isEditMode = $editMode;
    }

    private function getFile(string $name): ?array
    {
        return isset($this->files[$name]) && is_array($this->files[$name]) ? $this->files[$name] : null;
    }

    public function setCurrentUser(?User $user): void
    {
        $this->currentUser = $user;
    }

    /**
     * Vérifie que le nom d'utilisateur, l'adresse e-mail, le mot de passe,
     * le statut administrateur et l'image sont valides.
     */
    private function validate(): void
    {
        $this->validateName();
        $this->validateEmail();
        $this->validatePassword();
        $this->validateIsAdmin();
        $this->validateAvatar();
    }

    /**
     * Vérifie que le nom d'utilisateur est valide.
     *
     * Un nom d'utilisateur doit contenir entre 3 et 50 caractères.
     * Si le champ est vide, une erreur est enregistrée.
     */
    private function validateName(): void
    {
        $value = trim($this->data[self::NAME_INPUT_NAME] ?? '');

        if ($value === '') {
            $this->errors[self::NAME_INPUT_NAME] = 'Le nom d’utilisateur est requis.';
        } elseif (mb_strlen($value) < 3 || mb_strlen($value) > 25) {
            $this->errors[self::NAME_INPUT_NAME] = 'Le nom d’utilisateur doit contenir entre 3 et 25 caractères.';
        }

        $this->data[self::NAME_INPUT_NAME] = $value;
    }

    /**
     * Vérifie que l'adresse e-mail est valide.
     *
     * Une adresse e-mail doit :
     * - contenir entre 1 et 100 caractères
     * - être dans un format valide (par exemple : exemple@domaine.fr)
     * - ne pas être déjà utilisée par un autre utilisateur (sauf si mode édition et que l'adresse e-mail n'a pas changé)
     *
     * Si le champ est vide, une erreur est enregistrée.
     */
    private function validateEmail(): void
    {
        $value = trim($this->data[self::EMAIL_INPUT_NAME] ?? '');

        if ($value === '') {
            $this->errors[self::EMAIL_INPUT_NAME] = 'L’adresse e-mail est requise.';
        } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[self::EMAIL_INPUT_NAME] = 'Adresse e-mail invalide.';
        } elseif (mb_strlen($value) > 100) {
            $this->errors[self::EMAIL_INPUT_NAME] = 'L’adresse e-mail ne peut pas dépasser 100 caractères.';
        } elseif (!$this->isEditMode) {
            $existingUser = $this->userModel->findUserByEmail($value);
            if ($existingUser) {
                $this->errors[self::EMAIL_INPUT_NAME] = 'Un compte existe déjà avec cette adresse e-mail.';
            }
        } elseif ($this->isEditMode && $this->currentUser) {
            $existingUser = $this->userModel->findUserByEmail($value);
            if ($existingUser && $existingUser['id'] !== $this->currentUser->getId()) {
                $this->errors[self::EMAIL_INPUT_NAME] = 'Cette adresse e-mail est déjà utilisée par un autre utilisateur.';
            }
        }

        $this->data[self::EMAIL_INPUT_NAME] = $value;
    }

    /**
     * Vérifie que le mot de passe est valide.
     *
     * Si le champ est vide, il n'y a pas d'erreur.
     * Sinon, le mot de passe doit contenir entre 6 et 100 caractères.
     */
    private function validatePassword(): void
    {
        $password = $this->data[self::PASSWORD_INPUT_NAME] ?? '';

        if ($password !== '') {
            if (mb_strlen($password) < 6 || mb_strlen($password) > 100) {
                $this->errors[self::PASSWORD_INPUT_NAME] = 'Le mot de passe doit contenir entre 6 et 100 caractères.';
            }
        }

        $this->data[self::PASSWORD_INPUT_NAME] = $password;
    }

    /**
     * Vérifie que le statut d'administrateur est valide.
     *
     * Si le champ est vide, il n'y a pas d'erreur.
     * Sinon, si le champ est coché, il est mis à 1, sinon il est mis à 0.
     */
    private function validateIsAdmin(): void
    {
        $this->data[self::IS_ADMIN_INPUT_NAME] = isset($this->data[self::IS_ADMIN_INPUT_NAME]) ? 1 : 0;
    }

    /**
     * Vérifie que l’avatar est valide.
     *
     * Si le champ est vide, il n'y a pas d'erreur.
     * Sinon, le fichier doit peser moins de 8 Mo et être une image valide (jpg, png, gif, webp).
     */
    private function validateAvatar(): void
    {
        $image = $this->getFile(self::AVATAR_INPUT_NAME);

        if (!$image || empty($image['tmp_name'])) {
            // Avatar non fourni → aucun problème, non obligatoire même à l’inscription
            return;
        }

        $error = match ($image['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier est trop lourd.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier envoyé.',
            UPLOAD_ERR_PARTIAL, UPLOAD_ERR_EXTENSION, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_NO_TMP_DIR => 'Une erreur est survenue pendant l’envoi.',
            default => null
        };

        if ($error) {
            $this->errors[self::AVATAR_INPUT_NAME] = $error;
            return;
        }

        if ($image['size'] > 8 * 1024 * 1024) {
            $this->errors[self::AVATAR_INPUT_NAME] = 'Le fichier ne doit pas dépasser 8 Mo.';
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($image['tmp_name']);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $this->errors[self::AVATAR_INPUT_NAME] = 'Le fichier doit être une image (jpg, png, gif, webp).';
        }
    }
}
