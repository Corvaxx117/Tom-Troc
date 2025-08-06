<?php

namespace App\Model;

use Metroid\Database\Model\TableAbstractModel;

class ThreadModel extends TableAbstractModel
{
    protected string $table = 'threads';

    /**
     * Retourne la liste des threads de l'utilisateur connecté
     * avec le dernier message et les infos du participant.
     */
    public function findThreadsForUser(int $userId, int $limit = 10, int $offset = 0): array
    {
        // PAGINATION
        // modifier la requete avec des Clause Limit, evenements qui écoutent le scrolling 
        // lancer le fetch en fonction du scroll pour ameliorer les performances
        // Comment gerer le chargement auto sur un evenement ?
        // Trouver le bon moment de lancement entre la position du scroll sur la taille totale du conteneur
        // Prendre en compte le scroll vers le bas a l'ouverture d'un thread deja implémenté

        $sql = "
            SELECT 
                t.id AS thread_id,
                u.id AS participant_id,
                u.name AS participant_name,
                COALESCE(u.avatar, 'uploads/avatars/avatar-placeholder.jpg') AS participant_avatar,
                m.content AS last_message,
                m.sent_at AS last_message_date,
                m.is_deleted AS last_message_deleted
            FROM threads t
            JOIN thread_participant tp1 ON t.id = tp1.thread_id
            JOIN thread_participant tp2 ON t.id = tp2.thread_id AND tp2.user_id != :userId
            JOIN users u ON u.id = tp2.user_id
            LEFT JOIN (
                SELECT thread_id, MAX(sent_at) AS max_date
                FROM messages sm
                WHERE is_deleted = 0
                GROUP BY thread_id
            ) latest ON latest.thread_id = t.id
            LEFT JOIN messages m ON m.thread_id = latest.thread_id AND m.sent_at = latest.max_date
            WHERE tp1.user_id = :userId
            ORDER BY last_message_date DESC
            LIMIT :limit OFFSET :offset
            ";
        // La requete imbriquée permet de récupérer le dernier message non supprimé de chaque thread

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findThreadBetweenUsers(int $user1, int $user2): ?array
    {
        $sql = "
        SELECT t.*
        FROM threads t
        JOIN thread_participant tp1 ON tp1.thread_id = t.id AND tp1.user_id = :user1
        JOIN thread_participant tp2 ON tp2.thread_id = t.id AND tp2.user_id = :user2
        LIMIT 1
    ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['user1' => $user1, 'user2' => $user2]);
        return $stmt->fetch() ?: null;
    }

    public function createThreadWithUsers(int $user1, int $user2): ?int
    {
        try {
            $this->connection->beginTransaction();

            // 1. Crée un thread vide avec TableAbstractModel
            $this->create([]); // Pas besoin de champs

            // 2. Récupère l'ID du thread créé
            $threadId = (int) $this->connection->lastInsertId();

            // 3. Insère les deux utilisateurs dans thread_participant
            $sql = "INSERT INTO thread_participant (thread_id, user_id) VALUES (:threadId, :userId)";
            $stmt = $this->connection->prepare($sql);

            $stmt->execute(['threadId' => $threadId, 'userId' => $user1]);
            $stmt->execute(['threadId' => $threadId, 'userId' => $user2]);

            $this->connection->commit();
            return $threadId;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            return null;
        }
    }
}
