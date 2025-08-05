<?php

namespace App\Model;

use Metroid\Database\Model\TableAbstractModel;

/**
 * Classe qui gère les livres.
 */
class BookModel extends TableAbstractModel
{
    protected string $table = 'books';

    public function findBookById(int $id): ?array
    {
        $result = $this->findBy(
            ['books.id' => $id],
            'JOIN users ON books.user_id = users.id',
            'books.*, users.name AS owner_username, users.avatar AS avatar'
        );
        // Renvoie le premier livre trouvé
        return $result[0] ?? null;
    }


    public function findBooksByUser(int $userId, string $sort = 'title', string $dir = 'ASC'): array
    {
        $allowedSorts = ['title', 'author', 'description', 'is_available'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'title';
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        return $this->findBy(
            ['user_id' => $userId],
            joinClause: '',
            select: '*',
            orderBy: "$sort $dir"
        );
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

    public function findLatestBooks(int $limit = 4): array
    {
        return $this->findBy(
            criteria: [],
            joinClause: '',
            select: '*',
            orderBy: 'created_at DESC',
            limit: $limit
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
