<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {}

    public function register(string $json): User
    {
        $data = json_decode($json, true);

        if(!$data)
        {
            throw new ValidationException(["Invalid JSON"]);
        }

        if(empty($data["login"]) || empty($data["password"]))
        {
            throw new ValidationException(["login and password required"]);
        }

        if($this->em->getRepository(User::class)->findOneBy(["login" => $data["login"]]))
        {
            throw new ValidationException(["login already exists"]);
        }

        $user = new User();
        $user->setLogin($data["login"]);

        $user->setPassword($this->hasher->hashPassword($user, $data["password"]));

        $user->setRoles(["ROLE_USER"]);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
