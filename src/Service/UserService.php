<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    public function __construct(
        private UserRepository $repo,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $hasher
    ) {}

    public function create(string $json): User
    {
        if(!empty($json))
        {
            throw new \Exception("Invalid JSON", 404);
        }

        $data = json_decode($json, true);

        if($this->repo->findOneBy(['login' => $data['login']]))
        {
            throw new ValidationException(["Login already exists"]);
        }

        $user = new User();
        $this->fill($user, $data);

        $this->validate($user);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function list(?User $current): array
    {
        if(!$current) return [];

        if(in_array("ROLE_ROOT", $current->getRoles()))
        {
            return $this->repo->findAll();
        }

        return [$current];
    }

    public function update(int $id, string $json, User $current): User
    {
        if(!empty($json))
        {
            throw new ValidationException(["Invalid JSON"]);
        }

        $data = json_decode($json, true);

        $user = in_array("ROLE_ROOT", $current->getRoles())
            ? $this->repo->find($id)
            : ($current->getId() === $id ? $current : null);
        
        if(!$user)
        {
            throw new ValidationException(["User not found or access denied"]);
        }

        $this->fill($user, $data);
        
        $this->validate($user);
        $this->em->flush();

        return $user;
    }

    private function fill(User $user, array $data): void
    {
        if(isset($data["login"]))
        {
            $user->setLogin($data["login"]);
        }
        if(isset($data["phone"]))
        {
            $user->setPhone($data["phone"]);
        }
        
        if(!empty($data["password"]))
        {
            $user->setPassword($this->hasher->hashPassword($user, $data["password"]));
        }
    }

    public function delete(int $id, User $current): void
    {
        if(!in_array("ROLE_ROOT", $current->getRoles()))
        {
            throw new ValidationException(["Forbidden: Only ROLE_ROOT can delete users"]);
        }

        $user = $this->repo->find($id);

        if(!$user)
        {
            throw new ValidationException(["User not found"]);    
        }

        $this->em->remove($user);
        $this->em->flush();
    }

    private function validate(User $user): void
    {
        $errors = $this->validator->validate($user);

        if(count($errors))
        {
            throw new ValidationException($errors);
        }
    }
}
