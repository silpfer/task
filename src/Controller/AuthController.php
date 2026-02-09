<?php

namespace App\Controller;

use App\Exception\ValidationException;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/v1/api/auth")]
class AuthController extends AbstractController
{
    public function __construct(private AuthService $auth) {}

    #[Route("/register", methods: ["POST"])]
    public function register(Request $request): JsonResponse
    {
        try
        {
            $user = $this->auth->register($request->getContent());
            return $this->json($user, 201);
        }
        catch(ValidationException $e)
        {
            return $this->json([
                "error" => $e->getErrors()
            ], 422);
        }
    }

    
    #[Route("/me", methods: ["GET"])]
    public function me(): JsonResponse
    {
        return $this->json($this->getUser());
    }
}
