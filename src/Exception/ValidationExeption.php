<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use RuntimeException;

class ValidationException extends RuntimeException
{
    private array $errors = [];

    public function __construct(ConstraintViolationListInterface|array $violationList)
    {
        parent::__construct("Validation failed");

        foreach($violationList as $violation)
        {
            $this->errors[] = [
                "field"   => $violation->getPropertyPath(),
                "message" => $violation->getMessage()
            ];
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
