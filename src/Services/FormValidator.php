<?php

namespace App\Services;

use App\Security\User;
use App\Model\UserModel;
use Metroid\FlashMessage\FlashMessage;

class FormValidator
{
    public function __construct(
        private FlashMessage $flashMessage,
        private UserModel $userModel
    ) {}

    /**
     * Valide les données utilisateur pour l'inscription ou la mise à jour.
     *
     * @param array $data Données POST
     * @param string $context 'registration' ou 'update'
     * @param User|null $currentUser L'utilisateur connecté (pour update uniquement)
     * @return array Tableau ['valid' => bool, 'data' => données nettoyées, 'errors' => tableau d'erreurs]
     */
    public function validateUserData(array $data, string $context = 'registration', ?User $currentUser = null): array
    {
        $username = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $errors = [];

        if ($context === 'registration') {
            if (empty($username) || empty($email) || empty($password)) {
                $errors[] = 'Tous les champs sont requis.';
            }
        } elseif ($context === 'update') {
            if (empty($username) || empty($email)) {
                $errors[] = 'Le nom et l’email sont requis.';
            }
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }

        // Vérifie si un utilisateur avec cet email existe déjà (autre que soi-même)
        if (!empty($email)) {
            $existingUser = $this->userModel->findUserByEmail($email);
            if ($existingUser && (!$currentUser || $existingUser['id'] !== $currentUser->getId())) {
                $errors[] = 'Un utilisateur avec cet email existe déjà.';
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->flashMessage->add('error', $error);
            }

            return ['valid' => false, 'data' => [], 'errors' => $errors];
        }

        return [
            'valid' => true,
            'data' => [
                'name' => $username,
                'email' => $email,
                'password' => !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : null
            ],
            'errors' => []
        ];
    }
}
