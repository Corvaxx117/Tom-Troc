<?php

namespace App\Controller;

use App\Security\User;
use App\Model\UserModel;
use App\Model\BookModel;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\View\ViewRenderer;
use App\Services\UserFormValidator;
use Metroid\Services\AuthService;
use Metroid\FlashMessage\FlashMessage;
use Metroid\Controller\AbstractController;
use App\Form\Validator\Factory\FormValidatorFactory;
use App\Services\FileUploaderService;

/**
 * Controleur de gestion de connexion et d'inscription
 * @package App\Controller
 */
class AuthController extends AbstractController
{
    private UserModel $userModel;
    private BookModel $bookModel;
    private FormValidatorFactory $validatorFactory;
    private FileUploaderService $fileUploader;

    public function __construct(
        ViewRenderer $viewRenderer,
        FlashMessage $flashMessage,
        UserModel $userModel,
        BookModel $bookModel,
        FileUploaderService $fileUploader,
        FormValidatorFactory $validatorFactory
    ) {
        parent::__construct($viewRenderer, $flashMessage);
        $this->userModel = $userModel;
        $this->validatorFactory = $validatorFactory;
        $this->fileUploader = $fileUploader;
        $this->bookModel = $bookModel;
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
                $userObject = $this->mapToUser($user);
                AuthService::login($userObject);
                $this->flashMessage->add('success', 'Connexion réussie !');

                return $this->render('home.phtml', [
                    'title' => 'Accueil',
                    'user' => AuthService::getUser(),
                    'latestBooks' => $this->bookModel->findLatestBooks()
                ], 200);
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

        return $this->redirect('/auth/login');
    }

    /**
     * Affiche le formulaire d'inscription ou traite l'envoi du formulaire.
     *
     * Si le formulaire est envoyé, valide les données, puis les enregistre en base.
     * Si l'enregistrement est réussi, redirige vers la page de connexion.
     * Si une erreur est levée, affiche un message d'erreur.
     *
     * @param Request $request La requête HTTP.
     * @return Response La réponse HTTP.
     */
    public function register(Request $request): Response
    {
        if ($request->isPost()) {
            /** @var UserFormValidator $validator */
            $validator = $this->validatorFactory->make('user', $request);
            $validator->setEditMode(false);

            if ($validator->isValid()) {
                $data = $validator->getFormData();

                // Hash du mot de passe
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

                // Gestion de l’avatar (optionnel à l’inscription)
                $avatar = $request->files['avatar'] ?? null;
                if (!empty($avatar['tmp_name'])) {
                    $avatarPath = $this->fileUploader->upload($avatar, null, 'avatars');
                    if ($avatarPath) {
                        $data['avatar'] = $avatarPath;
                    } else {
                        $this->flashMessage->add('error', 'Erreur lors du téléchargement de l’avatar.');
                    }
                }

                $success = $this->userModel->createUser($data);

                if ($success) {
                    $this->flashMessage->add('success', 'Inscription réussie !');
                    return $this->render('auth/login.phtml', ['title' => 'Connexion']);
                }

                $this->flashMessage->add('error', "Une erreur est survenue lors de l'inscription.");
            } else {
                foreach ($validator->getErrors() as $error) {
                    $this->flashMessage->add('error', $error);
                }
            }
        }

        return $this->render('auth/registration.phtml', ['title' => 'Inscription']);
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
        $user = new User(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['is_admin'],
            $data['avatar'] ?? null
        );

        if (!empty($data['password'])) {
            $user->setHashedPassword($data['password']);
        }

        return $user;
    }
}
