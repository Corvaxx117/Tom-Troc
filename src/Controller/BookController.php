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
     * Affiche la liste des livres disponibles à l’échange, avec possibilité de recherche.
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
     * Crée un nouveau livre depuis le formulaire.
     */
    public function create(Request $request): Response
    {
        if ($response = $this->requireAuthentication()) {
            return $response;
        }

        if ($request->isPost()) {
            $data = $this->prepareBookData($request);

            if ($response = $this->checkUploadTooLarge($request, '/account')) {
                return $response;
            }

            $success = $this->bookModel->createBook($data);
            $this->flashMessage->add($success ? 'success' : 'error', $success ? 'Livre ajouté avec succès.' : 'Erreur lors de l’ajout.');

            return $this->redirect('/account');
        }

        return $this->render('book/new', [
            'title' => 'Ajouter un livre'
        ]);
    }

    /**
     * Affiche un livre en lecture seule.
     */
    public function show(Request $request, int $id): Response
    {
        $book = $this->bookModel->findBookById($id);

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
     * Permet à l'utilisateur connecté d'éditer l'un de ses livres.
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

        if ($request->isPost()) {
            $data = $this->prepareBookData($request, $book);

            if ($response = $this->checkUploadTooLarge($request, "/account/book/edit/$id")) {
                return $response;
            }

            $success = $this->bookModel->updateBook($id, $data);
            $this->flashMessage->add($success ? 'success' : 'error', $success ? 'Livre mis à jour avec succès.' : 'Erreur lors de la mise à jour.');

            return $this->redirect('/account');
        }

        return $this->render('book/edit.phtml', [
            'title' => 'Modifier le livre',
            'book' => $book
        ]);
    }

    /**
     * Supprime un livre de la base s'il appartient à l'utilisateur.
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
     * Prépare les données du livre depuis le formulaire.
     */
    private function prepareBookData(Request $request, array $existingBook = []): array
    {
        /** @var User $user */
        $user = AuthService::getUser();

        $data = [
            'user_id' => $user->getId(),
            'title' => trim($request->getPost('title')),
            'author' => trim($request->getPost('author')),
            'description' => trim($request->getPost('description')),
            'is_available' => $request->getPost('is_available') ? 1 : 0,
            'image_url' => $this->handleBookImageUpload($existingBook)
        ];

        return $data;
    }

    /**
     * Gère l'upload ou le fallback de l'image du livre.
     */
    private function handleBookImageUpload(array $existingBook = []): ?string
    {
        $image = $_FILES['image'] ?? [];

        if (!empty($image['tmp_name'])) {
            $imagePath = $this->fileUploader->upload($image, $existingBook['image_url'] ?? null, 'books');

            if (!$imagePath) {
                $this->flashMessage->add('error', 'Erreur lors du téléchargement de l’image.');
            }

            return $imagePath;
        }

        return $existingBook['image_url'] ?? null;
    }

    /**
     * Vérifie que l'utilisateur est connecté. Sinon redirige.
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
     * Vérifie que le livre appartient à l'utilisateur connecté.
     */
    private function assertBookOwnership(?array $book): bool
    {
        return $book && $book['user_id'] === AuthService::getUser()->getId();
    }
}
