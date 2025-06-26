<?php

namespace App\Controller;

use App\Security\User;
use App\Model\UserModel;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\View\ViewRenderer;
use App\Services\FormValidator;
use Metroid\Services\AuthService;
use Metroid\FlashMessage\FlashMessage;
use Metroid\Controller\AbstractController;

class AuthController extends AbstractController
{
    private UserModel $userModel;

    public function __construct(
        ViewRenderer $viewRenderer,
        FlashMessage $flashMessage
    ) {
        parent::__construct($viewRenderer, $flashMessage);
        $this->userModel = new UserModel();
    }

    /**
     * Affiche le formulaire de connexion ou traite l'envoi du formulaire.
     *
     * @param Request $request
     * @return Response
     */
    public function login(Request $request): Response
    {
        if ($request->isPost()) {
            $email = trim($request->getPost('email'));
            $password = $request->getPost('password');
            $user = $this->userModel->findUserByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                AuthService::login($this->mapToUser($user));
                $this->flashMessage->add('success', 'Connexion réussie !');

                return $this->render('home.phtml', ['title' => 'Accueil'], 200);
            }

            $this->flashMessage->add('error', 'Identifiants invalides.');
        }

        return $this->render('auth/login.phtml', ['title' => 'Connexion'], 200);
    }

    /**
     * Déconnecte l'utilisateur courant.
     *
     * @return Response
     */
    public function logout(): Response
    {
        AuthService::logout();
        $this->flashMessage->add('success', 'Vous êtes déconnecté(e).');

        return $this->render('auth/login.phtml', ['title' => 'Connexion'], 200);
    }

    /**
     * Gère l'inscription d'un nouvel utilisateur.
     *
     * @param Request $request
     * @return Response
     */
    public function register(Request $request): Response
    {
        if ($request->isPost()) {
            $validator = new FormValidator($this->flashMessage, $this->userModel);
            $result = $validator->validateUserData($request->getAllPost(), 'registration');

            if ($result['valid']) {
                $userData = $result['data'];
                $success = $this->userModel->createUser([
                    'name'     => $userData['name'],
                    'email'    => $userData['email'],
                    'password' => $userData['password'],
                ]);

                if ($success) {
                    $this->flashMessage->add('success', 'Inscription réussie !');
                    return $this->render('auth/login.phtml', ['title' => 'Connexion'], 200);
                }

                $this->flashMessage->add('error', "Une erreur est survenue lors de l'inscription.");
            }
        }

        return $this->render('auth/registration.phtml', ['title' => 'Inscription'], 200);
    }

    /**
     * Convertit un tableau utilisateur (depuis la base) en instance User.
     * Evite une requête SQL suplementaire.
     *
     * @param array $data
     * @return User
     */
    private function mapToUser(array $data): User
    {
        return new User(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['is_admin'],
            $data['avatar']
        );
    }
}
