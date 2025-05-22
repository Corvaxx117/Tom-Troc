<?php

namespace App\Controller;

use Metroid\Controller\AbstractController;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\View\ViewRenderer;
use Metroid\FlashMessage\FlashMessage;
use App\Model\BookModel;
use Metroid\Services\AuthService;

class BookController extends AbstractController
{
    public function create(Request $request): Response
    {
        if (!AuthService::isAuthenticated()) {
            $this->flashMessage->add('error', 'Vous devez être connecté.');
            return $this->render('auth/login.phtml', ['title' => 'Connexion'], 302);
        }

        if ($request->isPost()) {
            $user = AuthService::getUser();

            $data = [
                'user_id' => $user['id'],
                'title' => trim($request->getPost('title')),
                'author' => trim($request->getPost('author')),
                'description' => trim($request->getPost('description')),
                'is_available' => $request->getPost('is_available') ? 1 : 0,
            ];

            // Traitement de l'image si fournie
            if (!empty($_FILES['image']['tmp_name'])) {
                $image = $_FILES['image'];
                $uploadDir = __DIR__ . '/../../public/uploads/books/';
                $filename = uniqid() . '-' . basename($image['name']);
                $targetPath = $uploadDir . $filename;

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                if (move_uploaded_file($image['tmp_name'], $targetPath)) {
                    $data['image_url'] = '/uploads/books/' . $filename;
                } else {
                    $this->flashMessage->add('error', 'Erreur lors du téléchargement de l’image.');
                }
            } else {
                $data['image_url'] = null;
            }

            $success = (new BookModel())->createBook($data);

            if ($success) {
                $this->flashMessage->add('success', 'Livre ajouté avec succès.');
            } else {
                $this->flashMessage->add('error', 'Erreur lors de l’ajout.');
            }

            $user = AuthService::getUser();
            $bookModel = new BookModel();
            $books = $bookModel->findBooksByUser($user['id']);

            return $this->render('account/profile.phtml', [
                'title' => 'Mon compte',
                'user' => $user,
                'books' => $books
            ], 200);
        }

        return $this->render('account/create_book.phtml', ['title' => 'Ajouter un livre']);
    }
}
