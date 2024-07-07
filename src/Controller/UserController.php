<?php
// src/Controller/UserController.php

namespace App\Controller;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['first_name']) || !isset($data['last_name'])) {
            return new JsonResponse(['status' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setFirstName($data['first_name']);
        $user->setLastName($data['last_name']);

        if (isset($data['date_of_birth'])) {
            $user->setDateOfBirth(($data['date_of_birth']));
        }
        if (isset($data['gender'])) {
            $user->setGender($data['gender']);
        }
        if (isset($data['phone_number'])) {
            $user->setPhoneNumber($data['phone_number']);
        }
        if (isset($data['address'])) {
            $user->setAddress($data['address']);
        }
        if (isset($data['profile_picture'])) {
            $user->setProfilePicture($data['profile_picture']);
        }
        if (isset($data['is_active'])) {
            $user->setActive($data['is_active']);
        }

        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User created'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/users/{id}', name: 'api_user_get', methods: ['GET'])]
    public function getUserinfo(User $user): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['message' => 'Token not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'User not found or not an instance of User'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'date_of_birth' => $user->getDateOfBirth(),
            'gender' => $user->getGender(),
            'phone_number' => $user->getPhoneNumber(),
            'address' => $user->getAddress(),
            'profile_picture' => $user->getProfilePicture(),
            'is_active' => $user->isActive(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/users', name: 'api_users_get_all', methods: ['GET'])]
    public function getAllUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['message' => 'Token not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'User not found or not an instance of User'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $users = $entityManager->getRepository(User::class)->findAll();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'date_of_birth' => $user->getDateOfBirth(),
                'gender' => $user->getGender(),
                'phone_number' => $user->getPhoneNumber(),
                'address' => $user->getAddress(),
                'profile_picture' => $user->getProfilePicture(),
                'is_active' => $user->isActive(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/users/{id}/put', name: 'api_user_update', methods: ['PUT', 'PATCH'])]
    public function updateUser(Request $request, User $user, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['message' => 'Token not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'User not found or not an instance of User'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }
        if (isset($data['first_name'])) {
            $user->setFirstName($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $user->setLastName($data['last_name']);
        }
        if (isset($data['date_of_birth'])) {
            $user->setDateOfBirth($data['date_of_birth']);
        }
        if (isset($data['gender'])) {
            $user->setGender($data['gender']);
        }
        if (isset($data['phone_number'])) {
            $user->setPhoneNumber($data['phone_number']);
        }
        if (isset($data['address'])) {
            $user->setAddress($data['address']);
        }
        if (isset($data['profile_picture'])) {
            $user->setProfilePicture($data['profile_picture']);
        }
        if (isset($data['is_active'])) {
            $user->setActive($data['is_active']);
        }

        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        return new JsonResponse(['status' => 'User updated'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/users/{id}/delete', name: 'api_user_delete', methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['message' => 'Token not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'User not found or not an instance of User'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User deleted'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/user', name: 'api_user', methods: ['GET'])]
    public function getUserInfos(): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['message' => 'Token not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'User not found or not an instance of User'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'date_of_birth' => $user->getDateOfBirth(),
            'gender' => $user->getGender(),
            'phone_number' => $user->getPhoneNumber(),
            'address' => $user->getAddress(),
            'profile_picture' => $user->getProfilePicture(),
            'is_active' => $user->isActive(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/count/users', name: 'api_users_count', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function countUsers(EntityManagerInterface $em): JsonResponse
    {
        $userCount = $em->getRepository(User::class)->count([]);
        return new JsonResponse(['count' => $userCount], JsonResponse::HTTP_OK);
    }

    #[Route('/api/usersearch', name: 'api_users_search', methods: ['GET'])]
    public function searchUsers(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['message' => 'Token not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'User not found or not an instance of User'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $searchTerm = $request->query->get('query'); // Pobieranie parametru 'query' z URL

        // Użycie repozytorium do wyszukiwania użytkowników po imieniu lub nazwisku
        $userRepository = $entityManager->getRepository(User::class);
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.firstName LIKE :searchTerm OR u.lastName LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult();

        // Przygotowanie danych do odpowiedzi
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'date_of_birth' => $user->getDateOfBirth(),
                'gender' => $user->getGender(),
                'phone_number' => $user->getPhoneNumber(),
                'address' => $user->getAddress(),
                'profile_picture' => $user->getProfilePicture(),
                'is_active' => $user->isActive(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        // Zwrócenie wyników jako odpowiedź JSON
        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }


}
