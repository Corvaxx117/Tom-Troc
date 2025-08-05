<?php

namespace App\Form\Validator\Factory;

use App\Form\Validator\FormValidatorInterface;
use App\Form\Validator\UserFormValidator;
use App\Form\Validator\BookFormValidator;
use Metroid\Http\Request;

class FormValidatorFactory
{
    public function make(string $type, Request $request): FormValidatorInterface
    {
        return match ($type) {
            'book' => new BookFormValidator($request),
            'user' => new UserFormValidator($request),
            default => throw new \InvalidArgumentException("Validateur de formulaire de type [$type] non trouvé."),
        };
    }
}
