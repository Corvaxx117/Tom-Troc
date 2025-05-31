<?php

namespace App\Controller;

use App\Model\UserModel;
use Metroid\Http\Request;
use Metroid\Http\Response;
use App\Services\FormValidator;
use Metroid\View\ViewRenderer;
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
     * Connexion d'un utilisateur.
     * Si le formulaire est envoyé  via POST, on vérifie l'email et le mot de passe.
     * Si les informations sont correctes, on stocke l'utilisateur en session.
     * On redirige vers la page d'accueil.
     * Sinon, on affiche le formulaire de connexion.
     *
     * @return Response La réponse HTTP avec redirection.
     */
    public function login(Request $request): Response
    {
        if ($request->isPost()) {
            $email = trim($request->getPost('email'));
            $password = $request->getPost('password');
            $user = $this->userModel->findUserByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $this->flashMessage->add('error', 'Identifiants invalides.');
            } else {
                AuthService::login($user);
                $this->flashMessage->add('success', 'Connexion réussie !');

                return $this->render('home.phtml', [
                    'title' => 'Accueil'
                ], 200);
            }
        }

        return $this->render('auth/login.phtml', [
            'title' => 'Connexion'
        ], 200);
    }

    /**
     * Déconnecte l'utilisateur en supprimant les informations de session.
     * Affiche un message de succès et redirige vers la page d'accueil.
     *
     * @return Response La réponse HTTP avec redirection.
     */

    public function logout(): Response
    {
        AuthService::logout();
        $this->flashMessage->add('success', 'Vous êtes déconnecté(e).');

        return $this->render('auth/login.phtml', [
            'title' => 'Connexion'
        ], 200);
    }

    /**
     * Gère l'inscription d'un nouvel utilisateur.
     * Vérifie si la requête est de type POST, puis valide les données fournies.
     * Si les données sont valides et qu'aucun utilisateur n'existe déjà avec l'email fourni,
     * crée un nouvel utilisateur et affiche un message de succès.
     * Sinon, affiche les erreurs correspondantes.
     * Retourne la page d'inscription avec le formulaire et les messages flash.
     *
     * @param Request $request Requête HTTP contenant les données d'inscription.
     * @return Response La réponse HTTP avec le formulaire d'inscription ou une redirection.
     */

    public function register(Request $request): Response
    {
        if ($request->isPost()) {
            $validator = new FormValidator($this->flashMessage, $this->userModel);
            $result = $validator->validateUserData($request->getAllPost(), 'registration');

            if ($result['valid']) {
                $userData = $result['data'];
                $success = $this->userModel->createUser([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                ]);

                if ($success) {
                    $this->flashMessage->add('success', 'Inscription réussie !');

                    return $this->render('auth/login.phtml', [
                        'title' => 'Connexion'
                    ], 200);
                } else {
                    $this->flashMessage->add('error', "Une erreur est survenue lors de l'inscription.");
                }
            }
        }

        return $this->render('auth/registration.phtml', [
            'title' => 'Inscription'
        ], 200);
    }
}
