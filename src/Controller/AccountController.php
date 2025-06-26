<?php

namespace App\Controller;

use App\Security\User;
use App\Model\BookModel;
use App\Model\UserModel;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\View\ViewRenderer;
use App\Services\FormValidator;
use Metroid\Services\AuthService;
use App\Services\FileUploaderService;
use Metroid\FlashMessage\FlashMessage;
use Metroid\Controller\AbstractController;

class AccountController extends AbstractController
{
    private UserModel $userModel;
    private BookModel $bookModel;
    private FileUploaderService $fileUploader;

    public function __construct(
        ViewRenderer $viewRenderer,
        FlashMessage $flashMessage
    ) {
        parent::__construct($viewRenderer, $flashMessage);
        $this->userModel = new UserModel();
        $this->bookModel = new BookModel();
        $this->fileUploader = new FileUploaderService();
    }

    private function redirectIfNotAuthenticated(): ?Response
    {
        if (!AuthService::isAuthenticated()) {
            $this->flashMessage->add('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->render('auth/login.phtml', [
                'title' => 'Connexion'
            ], 302);
        }
        return null;
    }

    public function show(Request $request): Response
    {
        if ($response = $this->redirectIfNotAuthenticated()) {
            return $response;
        }

        /** @var User */
        $user = AuthService::getUser();
        $sort = $request->get('sort', 'title');
        $dir = strtoupper($request->get('dir', 'asc'));

        $books = $this->bookModel->findBooksByUser($user->getId(), $sort, $dir);

        return $this->render('account/profile.phtml', [
            'title' => 'Mon compte',
            'user' => $user,
            'books' => $books
        ], 200);
    }

    public function update(Request $request): Response
    {
        if ($response = $this->redirectIfNotAuthenticated()) {
            return $response;
        }

        /** @var User */
        $user = AuthService::getUser();

        if ($request->isPost()) {
            $validator = new FormValidator($this->flashMessage, $this->userModel);
            $result = $validator->validateUserData($request->getAllPost(), 'update', $user);

            if ($result['valid']) {
                $updateData = [
                    'name' => $result['data']['name'],
                    'email' => $result['data']['email'],
                ];

                if (!empty($result['data']['password'])) {
                    $updateData['password'] = $result['data']['password'];
                }

                if (!empty($_FILES['avatar']['tmp_name'])) {
                    $avatarPath = $this->fileUploader->upload($_FILES['avatar'], $user->getAvatar() ?? null);
                    if ($avatarPath) {
                        $updateData['avatar'] = $avatarPath;
                    } else {
                        $this->flashMessage->add('error', 'Erreur lors du téléchargement de la photo de profil.');
                    }
                }

                if ($this->userModel->updateUser($user->getId(), $updateData)) {
                    $this->flashMessage->add('success', 'Profil mis à jour avec succès.');
                    $updatedUser = $this->userModel->getUserObjectById($user->getId());
                    AuthService::login($updatedUser);
                    return $this->show($request);
                } else {
                    $this->flashMessage->add('error', 'Erreur lors de la mise à jour.');
                }
            }
        }

        return $this->show($request);
    }
}
