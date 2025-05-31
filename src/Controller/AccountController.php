<?php

namespace App\Controller;

use App\Model\BookModel;
use App\Model\UserModel;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\View\ViewRenderer;
use App\Services\FormValidator;
use App\Services\FileUploaderService;
use Metroid\Services\AuthService;
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

    /**
     * Accès à l'espace Mon Compte avec récupération des informations
     *
     * @param Request $request
     * @return Response
     */
    public function show(Request $request): Response
    {
        // Vérifie que l'utilisateur est connecté
        if (!AuthService::isAuthenticated()) {
            $this->flashMessage->add('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->render('auth/login.phtml', [
                'title' => 'Connexion'
            ], 302);
        }

        // Récupère l'utilisateur connecté
        $user = AuthService::getUser();

        // Récupération des livres
        $sort = $request->get('sort', 'title');
        $dir = strtoupper($request->get('dir', 'asc'));

        $books = $this->bookModel->findBooksByUser($user['id'], $sort, $dir);
        // Compte les livres pour l'affichage
        $user['book_count'] = count($books);

        return $this->render('account/profile.phtml', [
            'title' => 'Mon compte',
            'user' => $user,
            'books' => $books
        ], 200);
    }

    public function update(Request $request): Response
    {
        if (!AuthService::isAuthenticated()) {
            $this->flashMessage->add('error', 'Vous devez être connecté pour modifier votre profil.');
            return $this->render('auth/login.phtml', [
                'title' => 'Connexion'
            ], 302);
        }

        $user = AuthService::getUser();

        if ($request->isPost()) {
            $validator = new FormValidator($this->flashMessage, $this->userModel);
            $result = $validator->validateUserData($request->getAllPost(), 'update', $user);

            if ($result['valid']) {
                $userData = $result['data'];

                // Construction des données à mettre à jour
                $updateData = [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                ];

                if (!empty($userData['password'])) {
                    $updateData['password'] = $userData['password'];
                }

                // GESTION UPLOAD AVATAR
                if (!empty($_FILES['avatar']['tmp_name'])) {
                    $avatarPath = $this->fileUploader->upload($_FILES['avatar'], $user['avatar'] ?? null);
                    if ($avatarPath) {
                        $updateData['avatar'] = $avatarPath;
                    } else {
                        $this->flashMessage->add('error', 'Erreur lors du téléchargement de la photo de profil.');
                    }
                }
                // Mise à jour en base
                $success = $this->userModel->updateUser($user['id'], $updateData);

                if ($success) {
                    $this->flashMessage->add('success', 'Profil mis à jour avec succès.');
                    // Met à jour la session avec les nouvelles données
                    if (!isset($updateData['avatar'])) {
                        $updateData['avatar'] = $user['avatar'] ?? null;
                    }
                    $updatedUser = $this->userModel->findBy(['id' => $user['id']])[0] ?? null;
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
