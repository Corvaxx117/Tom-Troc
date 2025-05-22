<?php

namespace App\Controller;

use App\Model\BookModel;
use App\Model\UserModel;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\View\ViewRenderer;
use App\Services\FormValidator;
use Metroid\Services\AuthService;
use Metroid\FlashMessage\FlashMessage;
use Metroid\Controller\AbstractController;

class AccountController extends AbstractController
{
    private UserModel $userModel;

    public function __construct(
        ViewRenderer $viewRenderer,
        FlashMessage $flashMessage
    ) {
        parent::__construct($viewRenderer, $flashMessage);
        $this->userModel = new UserModel();
    }

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
        $books = (new BookModel())->findBooksByUser($user['id']);
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
                // Exécution de la mise à jour
                $success = $this->userModel->updateUser($user['id'], $updateData);

                if ($success) {
                    $this->flashMessage->add('success', 'Profil mis à jour avec succès.');
                    // Met à jour les infos en session
                    AuthService::login(array_merge($user, $updateData));

                    return $this->show($request);
                } else {
                    $this->flashMessage->add('error', 'Erreur lors de la mise à jour.');
                }
            }
        }

        return $this->show($request);
    }
}
