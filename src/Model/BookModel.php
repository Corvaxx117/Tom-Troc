<?php

namespace App\Model;

use Metroid\Database\Model\TableAbstractModel;

/**
 * Classe qui gère les livres.
 */
class BookModel extends TableAbstractModel
{
    protected string $table = 'books';

    /**
     * Trouve un livre par son ID.
     * @param int $id L'identifiant du livre
     * @return array|null Le livre trouvé ou null si aucun livre n'a été trouvé
     */
    public function findBookById(int $id): ?array
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Récupère tous les livres d’un utilisateur donné.
     *
     * @param int $userId L'ID de l'utilisateur
     * @return array Liste des livres
     */
    public function findBooksByUser(int $userId): array
    {
        return $this->findBy(['user_id' => $userId]);
    }

    /**
     * Créé un nouveau livre.
     * @param array $data Les données du livre
     */
    public function createBook(array $data): bool
    {
        return $this->create($data);
    }

    /**
     * Modifie les données du livre.
     * @param int $id L'identifiant du livre
     * @param array $data Les nouvelles données du livre
     */
    public function updateBook(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Supprime un livre par son ID.
     * @param int $id L'identifiant du livre à supprimer
     * @return bool : true si la suppression a réussi, false sinon.
     */
    public function deleteBook(int $id): bool
    {
        return $this->delete(['id' => $id]);
    }
}
