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

    public function __construct(ViewRenderer $viewRenderer, FlashMessage $flashMessage)
    {
        parent::__construct($viewRenderer, $flashMessage);
        $this->messageModel = new MessageModel();
        $this->threadModel = new ThreadModel();
    }

    /**
     * Affiche la liste des threads de l'utilisateur connecté.
     */
    public function index(Request $request): Response
    {
        if ($response = $this->checkIfUserIsConnected()) {
            return $response;
        }

        $user = AuthService::getUser();
        $threads = $this->threadModel->findThreadsForUser($user->getId());

        return $this->render('message/index.phtml', [
            'title' => 'Messagerie',
            'threads' => $threads,
            'currentUser' => $user
        ]);
    }

    /**
     * Récupère les messages d'un thread (appelé via AJAX).
     */
    public function thread(Request $request, int $threadId): Response
    {
        if ($response = $this->checkIfUserIsConnected()) {
            return $response;
        }

        $user = AuthService::getUser();
        $messages = $this->messageModel->findBy(['thread_id' => $threadId], '', '*', 'sent_at ASC');

        return new JsonResponse(['messages' => $messages]);
    }

    /**
     * Envoie un nouveau message dans un thread donné.
     */
    public function send(Request $request, int $threadId): Response
    {
        if ($response = $this->checkIfUserIsConnected()) {
            return $response;
        }

        $user = AuthService::getUser();
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
    }

    public function delete(Request $request, int $messageId): JsonResponse
    {
        if ($response = $this->checkIfUserIsConnected()) {
            return $response;
        }

        $message = $this->messageModel->findOneMessage($messageId);
        $user = AuthService::getUser();

        if (!$message || $message['auteur'] != $user->getId()) {
            return new JsonResponse(['success' => false, 'error' => 'Non autorisé.'], 403);
        }

        $this->messageModel->deleteMessage($messageId, $user->getId());

        return new JsonResponse(['success' => true]);
    }



    public function checkIfUserIsConnected(string $redirectUrl = '/auth/login'): ?Response
    {
        if (!AuthService::isAuthenticated()) {
            $this->flashMessage->add('error', 'Vous devez être connecté.');
            return $this->redirect($redirectUrl);
        }

        return null;
    }
}
