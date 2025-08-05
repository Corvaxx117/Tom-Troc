<?php

namespace App\Form\Validator;

interface FormValidatorInterface
{
    public function isValid(): bool;

    public function getErrors(): ?array;

    public function getFormData(): ?array;

    public function setEditMode(bool $editMode): void;
}
