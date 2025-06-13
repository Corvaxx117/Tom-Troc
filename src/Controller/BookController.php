<?php

namespace App\Controller;

use Metroid\Controller\AbstractController;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\View\ViewRenderer;
use Metroid\FlashMessage\FlashMessage;
use App\Model\BookModel;
use Metroid\Services\AuthService;
use App\Services\FileUploaderService;

class BookController extends AbstractController
{
    private BookModel $bookModel;
    private FileUploaderService $fileUploader;

    public function __construct(
        ViewRenderer $viewRenderer,
        FlashMessage $flashMessage
    ) {
        parent::__construct($viewRenderer, $flashMessage);
        $this->bookModel = new BookModel();
        $this->fileUploader = new FileUploaderService();
    }

    /**
     * Liste tous les livres disponibles à l'échange.
     *
     * Les livres sont triés par ordre alphabétique et les livres non disponibles sont
     * exclus. La recherche est sensible à la casse.
     * @param Request $request
     * @return Response
     */
    public function listAll(Request $request): Response
    {
        $searchTerm = $request->get('q', '');
        $books = $this->bookModel->searchAvailableBooks($searchTerm);

        return $this->render('book/list.phtml', [
            'title' => 'Nos livres à l’échange',
            'books' => $books,
            'search' => $searchTerm
        ]);
    }


    /**
     * Prépare les données du livre à partir de la requête.
     * Récupère les informations de l'utilisateur connecté et les données POST.
     * Gère également le téléchargement de l'image du livre si elle est présente.
     *
     * @param Request $request La requête contenant les données du livre.
     * @return array Les données du livre formatées, prêtes à être sauvegardées.
     */

    private function prepareBookData(Request $request): array
    {
        $user = AuthService::getUser();

        $data = [
            'user_id' => $user['id'],
            'title' => trim($request->getPost('title')),
            'author' => trim($request->getPost('author')),
            'description' => trim($request->getPost('description')),
            'is_available' => $request->getPost('is_available') ? 1 : 0,
            'image_url' => $request->getPost('image_url')
        ];

        // Gestion de l'image via le FileUploaderService
        $imagePath = $this->fileUploader->upload($_FILES['image'] ?? [], null, 'books');

        if ($imagePath) {
            $data['image_url'] = $imagePath;
        } elseif (!empty($_FILES['image']['tmp_name'])) {
            $this->flashMessage->add('error', 'Erreur lors du téléchargement de l’image.');
        }

        return $data;
    }

    /**
     * Crée un nouveau livre.
     * Si le formulaire est envoyé  via POST, on enregistre le livre.
     * On redirige vers la page Mon Compte.
     * Sinon, on affiche le formulaire de création de livre.
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        if (!AuthService::isAuthenticated()) {
            $this->flashMessage->add('error', 'Vous devez être connecté.');

            return $this->redirect('/auth/login');
        }

        if ($request->isPost()) {

            $data = $this->prepareBookData($request);

            $success = $this->bookModel->createBook($data);

            if ($success) {
                $this->flashMessage->add('success', 'Livre ajouté avec succès.');
            } else {
                $this->flashMessage->add('error', 'Erreur lors de l’ajout.');
            }

            return $this->redirect('/account');
        }

        return $this->render('book/new', [
            'title' => 'Ajouter un livre'
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $book = $this->bookModel->findBookById($id);
        // dd($book);

        if (!$book) {
            $this->flashMessage->add('error', 'Livre introuvable.');
            return $this->redirect('/books');
        }

        return $this->render('book/show.phtml', [
            'title' => $book['title'],
            'book' => $book
        ]);
    }

    /**
     * Édite un livre existant.
     * Si l'utilisateur n'est pas connecté, il est redirigé vers la page de connexion.
     * Si le livre n'existe pas ou si l'utilisateur n'est pas propriétaire, il est redirigé vers la page Mon Compte.
     * Les erreurs sont gérées via les messages flash.
     *
     * @param Request $request
     * @param int $id ID du livre à éditer
     * @return Response
     */
    public function edit(Request $request, int $id): Response
    {
        if (!AuthService::isAuthenticated()) {
            $this->flashMessage->add('error', 'Vous devez être connecté.');
            return $this->redirect('/auth/login');
        }

        $book = $this->bookModel->findBookById($id);

        if (!$book || $book['user_id'] !== AuthService::getUser()['id']) {
            $this->flashMessage->add('error', 'Livre introuvable ou accès refusé.');
            return $this->redirect('/account');
        }

        if ($request->isPost()) {

            $data = $this->prepareBookData($request);

            $success = $this->bookModel->updateBook($id, $data);

            if ($success) {
                $this->flashMessage->add('success', 'Livre mis à jour avec succès.');
                return $this->redirect('/account');
            } else {
                $this->flashMessage->add('error', 'Erreur lors de la mise à jour du livre.');
            }

            return $this->redirect('/account');
        }

        return $this->render('book/edit.phtml', [
            'title' => 'Modifier le livre',
            'book' => $book
        ]);
    }


    /**
     * Supprime un livre via son ID.
     * Si l'utilisateur n'est pas connecté, il est redirigé vers la page de connexion.
     * Si la requête est faite via GET, une erreur 404 est levée.
     * Les erreurs sont gérées via les messages flash.
     *
     * @param Request $request
     * @param int $id ID du livre à supprimer
     * @return Response
     */
    public function delete(Request $request, int $id): Response
    {
        if (!AuthService::isAuthenticated()) {
            $this->flashMessage->add('error', 'Vous devez être connecté.');

            return $this->redirect('/auth/login');
        }

        try {
            if ($this->bookModel->deleteBook($id)) {
                $this->flashMessage->add('success', 'Livre supprimé avec succès.');
            } else {
                $this->flashMessage->add('error', 'Erreur lors de la suppression du livre.');
            }

            return $this->redirect('/account');
        } catch (\Throwable $e) {
            $this->flashMessage->add('error', 'Une erreur est survenue : ' . $e->getMessage());

            return $this->redirect('/account');
        }
    }
}
