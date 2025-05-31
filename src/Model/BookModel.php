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
    public function findBooksByUser(int $userId, string $sort = 'title', string $dir = 'ASC'): array
    {
        $allowedSorts = ['title', 'author', 'description', 'is_available'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'title';
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "SELECT * FROM books WHERE user_id = :user_id ORDER BY $sort $dir";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function searchAvailableBooks(string $term = ''): array
    {
        $term = trim($term);

        if ($term === '') {
            // Si aucun terme de recherche, on retourne tous les livres disponibles avec leur propriétaire
            return $this->findAvailableBooksWithOwner();
        }

        // Requête avec recherche par titre ou auteur
        $sql = "
        SELECT books.*, users.name AS owner_username
        FROM books
        JOIN users ON books.user_id = users.id
        WHERE books.is_available = 1
          AND (books.title LIKE :term OR books.author LIKE :term)
        ORDER BY books.title ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['term' => '%' . $term . '%']);
        return $stmt->fetchAll();
    }


    public function findAvailableBooksWithOwner(): array
    {
        return $this->findBy(
            ['is_available' => 1],
            'JOIN users ON books.user_id = users.id',
            'books.*, users.name AS owner_username',
            'books.title ASC'
        );
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
