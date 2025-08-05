<?php

namespace App\Controller;

use Metroid\View\ViewRenderer;
use Metroid\Http\Request;
use Metroid\Http\Response;
use Metroid\FlashMessage\FlashMessage;
use Metroid\Controller\AbstractController;
use Metroid\Services\AuthService;
use App\Model\BookModel;


class HomeController extends AbstractController
{
    private BookModel $bookModel;
    public function __construct(
        ViewRenderer $viewRenderer,
        FlashMessage $flashMessage,
        BookModel $bookModel
    ) {
        parent::__construct($viewRenderer, $flashMessage);
        $this->bookModel = $bookModel;
    }
    public function index(Request $request): Response
    {
        /** @var User */
        $user = AuthService::getUser();
        $latestBooks = $this->bookModel->findLatestBooks(4);
        return $this->render('home.phtml', [
            'title' => 'Accueil',
            'user' => $user,
            'latestBooks' => $latestBooks
        ]);
    }
}
