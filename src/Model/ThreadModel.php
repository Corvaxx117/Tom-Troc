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
    public function findThreadsForUser(int $userId): array
    {
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
                FROM messages
                GROUP BY thread_id
            ) latest ON latest.thread_id = t.id
            LEFT JOIN messages m ON m.thread_id = latest.thread_id AND m.sent_at = latest.max_date
            WHERE tp1.user_id = :userId
            ORDER BY last_message_date DESC
            ";
        // La requete imbriquée permet de récupérer le dernier message de chaque thread

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['userId' => $userId]);

        return $stmt->fetchAll();
    }
}
