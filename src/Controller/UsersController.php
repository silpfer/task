<?php

namespace App\Controller;

use App\Exception\ValidationException;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/v1/api/users")]
final class UsersController extends AbstractController
{
    public function __construct(
        private UserService $userService
    )
    {}

    #[Route("", methods: ["GET"])]
    public function getUserList(): JsonResponse
    {
        $users = $this->userService->list($this->getUser());
        return $this->json($users);
    }

    #[Route("", methods: ["POST"])]
    public function postUser(Request $request): JsonResponse
    {
        try
        {
            $user = $this->userService->create($request->getContent());
            return $this->json($user, 201);
        }
        catch(ValidationException $e)
        {
            return $this->json(["error" => $e->getErrors()], 422);
        }
    }

    #[Route("/{id}", methods: ["PUT"])]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        try
        {
            $user = $this->userService->update($id, $request->getContent(), $this->getUser());
            return $this->json($user);
        }
        catch(ValidationException $e)
        {
            return $this->json(["error" => $e->getErrors()], 422);
        }
    }

    #[Route("/{id}", methods: ["DELETE"])]
    public function deleteUser(int $id): JsonResponse
    {
        try
        {
            $this->userService->delete($id, $this->getUser());
            return $this->json(["status" => "deleted"]);
        }
        catch(ValidationException $e)
        {
            return $this->json(["error" => $e->getErrors()], 422);
        }
    }
}
