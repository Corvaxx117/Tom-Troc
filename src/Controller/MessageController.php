<?php

namespace App\Controller;

use App\Model\MessageModel;
use App\Model\ThreadModel;
use App\Model\UserModel;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\Http\JsonResponse;
use Metroid\View\ViewRenderer;
use Metroid\FlashMessage\FlashMessage;
use Metroid\Services\AuthService;
use Metroid\Controller\AbstractController;

class MessageController extends AbstractController
{
    private MessageModel $messageModel;
    private ThreadModel $threadModel;
    private UserModel $userModel;

    public function __construct(
        ViewRenderer $viewRenderer,
        FlashMessage $flashMessage,
        MessageModel $messageModel,
        ThreadModel $threadModel,
        UserModel $userModel
    ) {
        parent::__construct($viewRenderer, $flashMessage);
        $this->messageModel = $messageModel;
        $this->threadModel = $threadModel;
        $this->userModel = $userModel;
    }

    /**
     * Affiche la page de messagerie.
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if ($response = $this->requireAuthentication()) {
            return $response;
        }

        $user = AuthService::getUser();
        $limit = 10;
        $page = max(1, (int) $request->get('page', 1));
        $offset = ($page - 1) * $limit;

        $threads = $this->threadModel->findThreadsForUser($user->getId(), $limit, $offset);

        return $this->render('message/index.phtml', [
            'title' => 'Messagerie',
            'threads' => $threads,
            'user' => $user,
            'page' => $page
        ]);
    }

    /**
     * Récupère les messages d'un thread (appelé via AJAX) ou envoie un nouveau message.
     * @param Request $request
     * @param int $threadId Identifiant du thread
     * @return JsonResponse
     */
    public function threadMessages(Request $request, int $threadId): Response
    {
        if ($response = $this->requireAuthentication()) {
            return $response;
        }
        /** @var User */
        $user = AuthService::getUser();

        if ($request->isPost()) {
            $content = trim($request->getPost('content'));

            if (!$content) {
                return new JsonResponse(['error' => 'Message vide.'], 400);
            }

            $this->messageModel->create([
                'thread_id' => $threadId,
                'auteur' => $user->getId(),
                'content' => $content
            ]);

            return new JsonResponse(['success' => true]);
        } else {
            $messages = $this->messageModel->findBy(['thread_id' => $threadId], '', '*', 'sent_at ASC');

            return new JsonResponse(['messages' => $messages]);
        }

        return new JsonResponse(['error' => 'Méthode non autorisée'], 405);
    }


    /**
     * Crée une nouvelle conversation si elle n'existe pas déjà.
     * Reçoit l'ID de l'utilisateur cible en paramètre.
     * Redirige vers la page de messagerie en cas d'erreur.
     * Redirige vers la page de messagerie avec l'ID du thread en cas de succès.
     * @param Request $request
     * @return Response
     */
    public function startNewConversation(Request $request): Response
    {
        if ($response = $this->requireAuthentication()) {
            return $response;
        }

        $currentUser = AuthService::getUser();
        $targetId = (int) $request->get('to');
        $targetUser = $this->userModel->findUserById($targetId);
        // On s'assure que l'utilisateur existe vraiment 
        if (!$targetUser) {
            $this->flashMessage->add('error', 'Utilisateur introuvable.');
            return $this->redirect('/thread');
        }

        // Ne pas envoyer de message à soi-même
        if (!$targetId || $targetId === $currentUser->getId()) {
            $this->flashMessage->add('error', 'Impossible de démarrer cette conversation.');
            return $this->redirect('/thread');
        }
        // Vérifie si une conversation existe déjà
        $existingThread = $this->threadModel->findThreadBetweenUsers($currentUser->getId(), $targetId);

        if ($existingThread) {
            return $this->redirect('/thread#thread-' . $existingThread['id']);
        }

        // Sinon, créer une nouvelle conversation
        $threadId = $this->threadModel->createThreadWithUsers($currentUser->getId(), $targetId);

        if (!$threadId) {
            $this->flashMessage->add('error', 'Erreur lors de la création de la conversation.');
            return $this->redirect('/thread');
        }

        return $this->redirect('/thread#thread-' . $threadId);
    }

    /**
     * Supprime un message.
     * @param Request $request
     * @param int $messageId Identifiant du message
     * @return JsonResponse
     */
    public function delete(Request $request, int $messageId): JsonResponse
    {
        if ($response = $this->requireAuthentication()) {
            return $response;
        }

        if (!$request->isDelete()) {
            return new JsonResponse(['error' => 'Méthode non autorisée.'], 405);
        }

        $message = $this->messageModel->findOneMessage($messageId);
        $user = AuthService::getUser();

        if (!$message || $message['auteur'] != $user->getId()) {
            return new JsonResponse(['success' => false, 'error' => 'Non autorisé.'], 403);
        }

        $this->messageModel->deleteMessage($messageId, $user->getId());

        return new JsonResponse(['success' => true]);
    }
}
