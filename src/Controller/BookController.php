<?php

namespace App\Controller;

use App\Model\BookModel;
use App\Services\FileUploaderService;
use App\Security\User;
use Metroid\Controller\AbstractController;
use Metroid\FlashMessage\FlashMessage;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\Services\AuthService;
use Metroid\View\ViewRenderer;
use App\Form\Validator\Factory\FormValidatorFactory;

class BookController extends AbstractController
{
    private BookModel $bookModel;
    private FileUploaderService $fileUploader;
    private FormValidatorFactory $validatorFactory;

    public function __construct(
        ViewRenderer $viewRenderer,
        FlashMessage $flashMessage,
        BookModel $bookModel,
        FileUploaderService $fileUploader,
        FormValidatorFactory $validatorFactory
    ) {
        parent::__construct($viewRenderer, $flashMessage);
        $this->bookModel = $bookModel;
        $this->fileUploader = $fileUploader;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * Affiche la page des livres à l'échange.
     *
     * Cette page contient une barre de recherche et une grille de livres.
     * Les livres sont listés dans l'ordre alphabétique (par défaut).
     * Les livres non disponibles ne sont pas listés.
     *
     * @param Request $request La requête HTTP.
     * @return Response La réponse HTTP.
     */
    public function listAll(Request $request): Response
    {
        $searchTerm = $request->get('q', '');
        $books = $this->bookModel->searchAvailableBooks($searchTerm);

        return $this->render('book/list.phtml', [
            'title' => 'Nos livres à l’échange',
            'books' => $books,
            'search' => $searchTerm,
            'user' => AuthService::getUser()
        ]);
    }

    /**
     * Ajoute un livre pour l'utilisateur connecté.
     *
     * La page affiche le formulaire d'ajout de livre.
     * Si le formulaire est envoyé, valide les données, puis les enregistre en base.
     *
     * @param Request $request La requête HTTP.
     * @return Response La réponse HTTP.
     */
    public function create(Request $request): Response
    {
        if ($response = $this->requireAuthentication()) {
            return $response;
        }

        /** @var User $user */
        $user = AuthService::getUser();

        if ($request->isPost()) {
            // Vérifier si le fichier est trop volumineux, pour éviter une erreur php
            if ($this->isUploadTooLarge($request)) {
                $this->flashMessage->add('error', 'Le fichier est trop volumineux. Taille maximale autorisée : 8 Mo.');

                return $this->renderCreateFormWithErrors($user, $request);
            }
            $validator = $this->validatorFactory->make('book', $request);
            $validator->setEditMode(false);

            if (!$validator->isValid()) {
                foreach ($validator->getErrors() as $field => $message) {
                    $this->flashMessage->add('error', $message);
                }

                return $this->renderCreateFormWithErrors($user, $request);
            }

            // Données valides → traitement du fichier
            $data = $validator->getFormData();
            $data['user_id'] = $user->getId();

            $imagePath = $this->fileUploader->upload($request->files['image_url'], null, 'books');

            if (!$imagePath) {
                $this->flashMessage->add('error', 'Erreur lors du téléchargement de l’image.');

                return $this->renderCreateFormWithErrors($user, $request);
            }

            $data['image_url'] = $imagePath;

            $success = $this->bookModel->createBook($data);

            $this->flashMessage->add(
                $success ? 'success' : 'error',
                $success ? 'Livre ajouté avec succès.' : 'Erreur lors de l’ajout.'
            );

            return $this->redirect('account');
        }

        return $this->render('account/profile.phtml', [
            'title' => 'Mon compte',
            'user' => $user,
            'books' => $this->bookModel->findBooksByUser($user->getId()),
        ]);
    }


    /**
     * Affiche la page de détails d'un livre.
     *
     * Si le livre n'existe pas, une erreur est ajoutée et on redirige vers la page des livres.
     *
     * @param Request $request La requête HTTP.
     * @param int $id Identifiant du livre.
     * @return Response La réponse HTTP.
     */
    public function show(Request $request, int $id): Response
    {
        $book = $this->bookModel->findBookById($id);
        if (!$book) {
            $this->flashMessage->add('error', 'Livre introuvable.');
            return $this->redirect('/books');
        }

        $owner = [
            'id' => $book['user_id'],
            'name' => $book['owner_username'],
            'avatar' => $book['avatar'] ?? null
        ];

        $user = AuthService::getUser();

        return $this->render('book/show.phtml', [
            'title' => $book['title'],
            'book' => $book,
            'owner' => $owner,
            'user' => $user,
            'currentUserId' => $user ? $user->getId() : null,
        ]);
    }


    /**
     * Affiche la page de modification d'un livre.
     *
     * Si l'utilisateur n'est pas connecté, redirige vers la page de connexion.
     * Si le livre n'existe pas, une erreur est ajoutée et on redirige vers la page du profil.
     * Si une erreur est levée, affiche un message d'erreur.
     *
     * @param Request $request La requête HTTP.
     * @param int $id Identifiant du livre.
     * @return Response La réponse HTTP.
     */
    public function edit(Request $request, int $id): Response
    {
        if ($response = $this->requireAuthentication()) {
            return $response;
        }

        $book = $this->bookModel->findBookById($id);

        if (!$this->assertBookOwnership($book)) {
            $this->flashMessage->add('error', 'Livre introuvable ou accès refusé.');
            return $this->redirect('/account');
        }

        /** @var User $user */
        $user = AuthService::getUser();

        if ($request->isPost()) {
            if ($this->isUploadTooLarge($request)) {
                $this->flashMessage->add('error', 'Le fichier est trop volumineux. Taille maximale autorisée : 8 Mo.');
                return $this->renderEditFormWithErrors($book, $request);
            }

            $validator = $this->validatorFactory->make('book', $request);
            $validator->setEditMode(true);

            if (!$validator->isValid()) {
                foreach ($validator->getErrors() as $message) {
                    $this->flashMessage->add('error', $message);
                }
                return $this->renderEditFormWithErrors($book, $request);
            }

            $data = $validator->getFormData();
            $data['user_id'] = $user->getId();

            $image = $request->files['image_url'] ?? null;

            if (!empty($image['tmp_name'])) {
                $imagePath = $this->fileUploader->upload($image, $book['image_url'], 'books');
                if (!$imagePath) {
                    $this->flashMessage->add('error', 'Erreur lors du téléchargement de l’image.');
                    return $this->renderEditFormWithErrors($book, $request);
                }
                $data['image_url'] = $imagePath;
            } else {
                $data['image_url'] = $book['image_url'];
            }

            $success = $this->bookModel->updateBook($id, $data);

            $this->flashMessage->add(
                $success ? 'success' : 'error',
                $success ? 'Livre mis à jour avec succès.' : 'Erreur lors de la mise à jour.'
            );

            return $this->redirect('/account');
        }

        return $this->render('book/edit.phtml', [
            'title' => 'Modifier le livre',
            'book' => $book,
            'user' => $user
        ]);
    }


    /**
     * Supprime un livre.
     *
     * Si l'utilisateur n'est pas connecté, redirige vers la page de connexion.
     * Si le livre n'existe pas, affiche un message d'erreur.
     * Si une erreur est levée, affiche un message d'erreur.
     *
     * @param Request $request La requête HTTP.
     * @param int $id Identifiant du livre.
     * @return Response La réponse HTTP.
     */
    public function delete(Request $request, int $id): Response
    {
        if ($response = $this->requireAuthentication()) {
            return $response;
        }

        try {
            $success = $this->bookModel->deleteBook($id);
            $this->flashMessage->add($success ? 'success' : 'error', $success ? 'Livre supprimé avec succès.' : 'Erreur lors de la suppression du livre.');
        } catch (\Throwable $e) {
            $this->flashMessage->add('error', 'Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirect('/account');
    }

    // ----------------------------
    // Méthodes utilitaires privées
    // ----------------------------

    /**
     * Affiche la page de profil de l'utilisateur connecté avec les erreurs de formulaire
     * et les données du formulaire envoyées. Evite les doublons dans la méthode create.
     *
     * @param User $user L'utilisateur connecté.
     * @param Request $request La requête HTTP.
     * @return Response La réponse HTTP.
     */
    private function renderCreateFormWithErrors(User $user, Request $request): Response
    {
        return $this->render('account/profile.phtml', [
            'title' => 'Mon compte',
            'user' => $user,
            'books' => $this->bookModel->findBooksByUser($user->getId()),
            'formData' => $request->getAllPost() ?? [],
            'openModal' => true
        ]);
    }

    /**
     * Affiche la page d'édition d'un livre avec les erreurs de formulaire
     * et les données du formulaire envoyées. Evite les doublons dans la méthode edit.
     *
     * @param array $book Les données du livre.
     * @param Request $request La requête HTTP.
     * @return Response La réponse HTTP.
     */
    private function renderEditFormWithErrors(array $book, Request $request): Response
    {
        return $this->render('book/edit.phtml', [
            'title' => 'Modifier le livre',
            'book' => $book,
            'formData' => $request->getAllPost(),
        ]);
    }

    /**
     * Vérifie si l'envoi du formulaire a échoué à cause d'un fichier trop lourd.
     * Cela se produit lorsque le formulaire est envoyé via la méthode POST,
     * que $_POST est vide (car le fichier est trop lourd) mais que
     * $_SERVER['CONTENT_LENGTH'] est supérieur à 0 (car le fichier a été envoyé).
     *
     * @param Request $request La requête HTTP.
     * @return bool True si l'envoi du formulaire a échoué à cause d'un fichier trop lourd, false sinon.
     */
    private function isUploadTooLarge(Request $request): bool
    {
        return $request->isPost() && empty($_POST) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
    }

    /**
     * Vérifie que l'utilisateur est authentifié. Si ce n'est pas le cas,
     * ajoute un message d'erreur et redirige vers l'URL de connexion spécifiée.
     *
     * @param string $redirectUrl URL de redirection si l'utilisateur n'est pas authentifié.
     * @return Response|null La réponse de redirection si l'utilisateur n'est pas connecté, null sinon.
     */

    private function requireAuthentication(string $redirectUrl = '/auth/login'): ?Response
    {
        if (!AuthService::isAuthenticated()) {
            $this->flashMessage->add('error', 'Vous devez être connecté.');
            return $this->redirect($redirectUrl);
        }

        return null;
    }


    /**
     * Vérifie si l'utilisateur authentifié est le propriétaire du livre donné.
     *
     * @param array|null $book Le livre à vérifier, ou null si non trouvé.
     * @return bool True si l'utilisateur est le propriétaire, false sinon.
     */

    private function assertBookOwnership(?array $book): bool
    {
        return $book && $book['user_id'] === AuthService::getUser()->getId();
    }
}
