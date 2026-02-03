<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UsersController extends AbstractController
{
    #[Route("/v1/api/users", methods: ["GET","POST","PUT","DELETE"])]
    public function handle(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $repository,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        $method = $request->getMethod();
        $current = $this->getUser();

        try
        {
            if($method === "GET")
            {
                if($this->isGranted("ROLE_ROOT"))
                {
                    return $this->json($repository->findAll());
                }
                return $this->json([$current]);
            }

            $data = json_decode($request->getContent(), true);

            if($method === "POST")
            {
                $user = new User();
                
                if(!empty($data["pass"]))
                {
                    $hashedPassword = $passwordHasher->hashPassword(
                        $user,
                        $data["pass"]
                    );
                    $user->setPassword($hashedPassword);
                }

                $this->fill($user, $data);

                $errors = $validator->validate($user);
                if(count($errors))
                {
                    return $this->validationError($errors);
                }

                $em->persist($user);
                $em->flush();

                return $this->json($user, 201);
            }

            if($method === "PUT")
            {
                $user = $this->resolveUser($data, $current, $repository);
                if(!$user)
                {
                    return $this->json(["error"=>"Not found"],404);
                }
                
                if(!empty($data["pass"]))
                {
                    $user->setPassword($passwordHasher->hashPassword($user, $data["pass"]));
                }

                $this->fill($user, $data);

                $errors = $validator->validate($user);
                if(count($errors))
                {
                    return $this->validationError($errors);
                }

                $em->flush();
                return $this->json($user);
            }

            if($method === "DELETE")
            {
                if(!$this->isGranted("ROLE_ROOT"))
                {
                    return $this->json(["error"=>"Forbidden"],403);
                }

                $user = $repository->find($data["id"] ?? null);
                if(!$user)
                {
                    return $this->json(["error"=>"Not found"],404);
                }

                $em->remove($user);
                $em->flush();

                return $this->json(["status"=>"deleted"]);
            }

        }
        catch(\Throwable $e)
        {
            return $this->json(["error" => "Server error"], 500);
        }

        return $this->json(["error"=>"Unsupported method"],405);
    }

    private function fill(User $user, array $data)
    {
        $user->setLogin($data["login"] ?? $user->getLogin());
        $user->setPhone($data["phone"] ?? $user->getPhone());
    }

    private function resolveUser($data, $current, $repo)
    {
        if($this->isGranted("ROLE_ROOT"))
        {
            return $repo->find($data["id"] ?? null);
        }
        return $current;
    }

    private function validationError($errors)
    {
        $list = [];
        foreach($errors as $e)
        {
            $list[] = $e->getPropertyPath()." ".$e->getMessage();
        }
        return $this->json(["errors"=>$list],422);
    }
}
