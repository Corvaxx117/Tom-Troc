<?php

namespace App\Model;

use Metroid\Database\Model\TableAbstractModel;

class MessageModel extends TableAbstractModel
{
    protected string $table = 'messages';

    /**
     * Trouve un message par son ID.
     * @param int $id L'identifiant du message
     * @return array|null Le message trouvé ou null si aucun message n'a été trouvé.
     */
    public function findOneMessage(int $id): ?array
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Récupère tous les messages d'un thread donné.
     * @param int $threadId L'identifiant du thread
     * @return array Les messages trouvés
     */
    public function findMessagesForThread(int $threadId): array
    {
        return $this->findBy(
            ['thread_id' => $threadId],
            '',
            'id, auteur, content, sent_at, thread_id, is_deleted',
            'sent_at ASC'
        );
    }

    /**
     * Crée un message dans un thread.
     * @param int $threadId L'identifiant du thread
     * @param int $authorId L'identifiant de l'auteur
     * @param string $content Le contenu du message
     * @return bool : true si la création a réussi, false sinon
     */
    public function createMessage(int $threadId, int $authorId, string $content): bool
    {
        return $this->create([
            'thread_id' => $threadId,
            'auteur' => $authorId,
            'content' => $content
        ]);
    }

    /**
     * Modifie les données d'un message.
     * @param int $id L'identifiant du message
     * @param array $data Les nouvelles données du message
     * @return bool : true si la mise à jour a réussi, false sinon.
     */
    public function updateMessage(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Supprime un message donné s'il appartient à l'utilisateur.
     * @param int $messageId L'identifiant du message
     * @param int $userId L'identifiant de l'utilisateur
     * @return bool : true si la suppression a réussi, false sinon
     */
    public function deleteMessage(int $messageId, int $userId): bool
    {
        $message = $this->findOneBy(['id' => $messageId]);

        if (!$message || (int)$message['auteur'] !== $userId) {
            return false;
        }

        return $this->update($messageId, ['is_deleted' => 1]);
    }
}
