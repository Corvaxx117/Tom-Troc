<?php

namespace App\Security;

/**
 * Représente un utilisateur authentifié dans l'application.
 *
 * Cette classe immuable encapsule les données essentielles d'un utilisateur :
 * - ID unique
 * - nom d'affichage
 * - email
 * - statut administrateur
 * - chemin de l'avatar
 *
 * Elle est principalement utilisée pour stocker les informations de session
 * de l'utilisateur connecté, et fournir un accès typé à ces données
 * via des getters.
 */
final class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $is_admin,
        public ?string $avatar,
        private ?string $hashedPassword = null
    ) {}

    /**
     * Retourne l'identifiant unique de l'utilisateur.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Retourne le nom de l'utilisateur.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retourne l'adresse email de l'utilisateur.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Indique si l'utilisateur a le rôle d'administrateur.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Retourne le chemin vers l'image de l'avatar de l'utilisateur.
     * Si l'utilisateur n'a pas d'avatar, la valeur par défaut est renvoyée.
     */
    public function getAvatar(): string
    {
        return $this->avatar ?: 'uploads/avatars/avatar-placeholder.jpg';
    }

    /**
     * Retourne le mot de passe haché de l'utilisateur, ou null
     * si le mot de passe n'a pas été défini.
     *
     * @return string|null
     */
    public function getHashedPassword(): ?string
    {
        return $this->hashedPassword;
    }

    /**
     * Modifie le mot de passe haché de l'utilisateur.
     *
     * @param string $hash Le mot de passe haché.
     */
    public function setHashedPassword(string $hash): void
    {
        $this->hashedPassword = $hash;
    }
}
