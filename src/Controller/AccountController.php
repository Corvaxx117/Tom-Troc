<?php

namespace App\Controller;

use App\Security\User;
use App\Model\BookModel;
use App\Model\UserModel;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\View\ViewRenderer;
use App\Form\Validator\Factory\FormValidatorFactory;
use Metroid\Services\AuthService;
use App\Services\FileUploaderService;
use Metroid\FlashMessage\FlashMessage;
use Metroid\Controller\AbstractController;

/**
 * Controleur de gestion de compte utilisateur
 * 
 * @package App\Controller
 */
class AccountController extends AbstractController
{
    private UserModel $userModel;
    private BookModel $bookModel;
    private FileUploaderService $fileUploader;
    private FormValidatorFactory $validatorFactory;

    public function __construct(
        ViewRenderer $viewRenderer,
        FlashMessage $flashMessage,
        FileUploaderService $fileUploader,
        FormValidatorFactory $validatorFactory,
        BookModel $bookModel,
        UserModel $userModel
    ) {
        parent::__construct($viewRenderer, $flashMessage);
        $this->userModel = $userModel;
        $this->bookModel = $bookModel;
        $this->fileUploader = $fileUploader;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * Redirige vers la page de connexion si l'utilisateur n'est pas connecté.
     * Si l'utilisateur est connecté, ne fait rien.
     *
     * @return Response|null La réponse de redirection si l'utilisateur n'est pas connecté, null sinon.
     */
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

    /**
     * Affiche la page de profil de l'utilisateur connecté.
     *
     * La page affiche les informations de l'utilisateur ainsi que la liste des
     * livres qu'il a ajoutés.
     *
     * @param Request $request
     * @return Response
     */
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

    /**
     * Met à jour le profil de l'utilisateur connecté.
     *
     * Valide et traite les données du formulaire de mise à jour de profil.
     * Si le formulaire est soumis et valide, met à jour les informations 
     * de l'utilisateur, y compris le mot de passe et l'avatar si modifiés.
     * En cas de succès, un message de confirmation est affiché et l'utilisateur
     * est rechargé dans la session. Si des erreurs surviennent, elles sont 
     * ajoutées aux messages flash.
     * 
     * @param Request $request La requête HTTP contenant les données du formulaire.
     * @return Response La réponse HTTP affichant le profil de l'utilisateur.
     */
    public function update(Request $request): Response
    {
        if ($response = $this->redirectIfNotAuthenticated()) {
            return $response;
        }

        /** @var User $user */
        $user = AuthService::getUser();

        if ($request->isPost()) {
            /** @var UserFormValidator $validator */
            $validator = $this->validatorFactory->make('user', $request);
            $validator->setEditMode(true);
            $validator->setCurrentUser($user);

            if ($validator->isValid()) {
                $formData = $validator->getFormData();

                // Gestion du mot de passe
                if (!empty($formData['password'])) {
                    $formData['password'] = password_hash($formData['password'], PASSWORD_BCRYPT);
                } else {
                    // On garde le mot de passe actuel depuis la base
                    $dbUser = $this->userModel->findUserById($user->getId());
                    $formData['password'] = $dbUser['password'] ?? null;
                }

                // Gestion de l'avatar
                $avatar = $request->files['avatar'] ?? null;
                if (!empty($avatar['tmp_name'])) {
                    $avatarPath = $this->fileUploader->upload($avatar, $user->getAvatar(), 'avatars');
                    if ($avatarPath) {
                        $formData['avatar'] = $avatarPath;
                    } else {
                        $this->flashMessage->add('error', 'Erreur lors du téléchargement de la photo de profil.');
                    }
                } else {
                    $formData['avatar'] = $user->getAvatar(); // Garde l’ancien avatar
                }

                // Mise à jour de l’utilisateur
                if ($this->userModel->updateUser($user->getId(), $formData)) {
                    $this->flashMessage->add('success', 'Profil mis à jour avec succès.');

                    // Recharge l'utilisateur et met à jour la session
                    $updatedUser = $this->userModel->getUserObjectById($user->getId());
                    AuthService::login($updatedUser);

                    return $this->show($request);
                } else {
                    $this->flashMessage->add('error', 'Erreur lors de la mise à jour du profil.');
                }
            } else {
                foreach ($validator->getErrors() as $error) {
                    $this->flashMessage->add('error', $error);
                }
            }
        }

        return $this->show($request);
    }


    /**
     * Affiche le profil public d'un utilisateur.
     *
     * Si l'utilisateur n'existe pas, une erreur est ajoutée à la flashMessage
     * et la page est redirigée vers la page des livres.
     *
     * @param Request $request
     * @param int $id l'ID de l'utilisateur
     * @return Response
     */
    public function publicProfile(Request $request, int $id): Response
    {
        $owner = $this->userModel->findUserById($id);
        if (!$owner) {
            $this->flashMessage->add('error', 'Profil introuvable.');

            return $this->redirect('/books');
        }

        $books = $this->bookModel->findBooksByUser($id);
        $user = AuthService::getUser();

        return $this->render('account/public-profile.phtml', [
            'owner' => $owner,
            'books' => $books,
            'user' => $user,
            'currentUserId' => $user ? $user->getId() : null,
        ], 200);
    }
}
