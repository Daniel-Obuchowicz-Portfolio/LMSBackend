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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;


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

        if (!empty($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        // Handling profile picture upload
        $base64Content = $data['profile_picture_p'] ?? null;
        if ($base64Content) {
            // Validate Base64 format
            if (preg_match('/^data:image\/\w+;base64,/', $base64Content)) {
                $base64Content = substr($base64Content, strpos($base64Content, ',') + 1);
                $base64Content = base64_decode($base64Content, true);

                if ($base64Content === false) {
                    return new JsonResponse(['message' => 'Invalid image data'], Response::HTTP_BAD_REQUEST);
                }

                // Save the image to a temporary file
                $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '.png';
                file_put_contents($tempFilePath, $base64Content);

                // Create UploadedFile object to manage file operations
                $file = new UploadedFile($tempFilePath, 'profile_picture.png', 'image/png', null, true);

                // Move the file to the uploads directory
                $filename = md5(uniqid()) . '.png';
                try {
                    $file->move($this->getParameter('kernel.project_dir') . '/public/uploads', $filename);
                    $baseUrl = $this->getParameter('kernel.environment') === 'dev' ? $this->getParameter('DEV_BASE_URL') : $this->getParameter('PROD_BASE_URL');
                    $user->setProfilePicture($baseUrl . '/uploads/' . $filename);
                } catch (FileException $e) {
                    unlink($tempFilePath); // Clean up the temporary file
                    return new JsonResponse(['message' => 'File could not be saved'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return new JsonResponse(['message' => 'Invalid image data format'], Response::HTTP_BAD_REQUEST);
            }
        }

        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User created'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/users/{id}', name: 'api_user_get', methods: ['GET'])]
    public function getUserinfo(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['message' => 'Token not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
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
    public function updateUser(int $id, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        // Retrieve the user
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Decode JSON data
        $data = json_decode($request->getContent(), true);

        // Update basic user information
        $user->setEmail($data['email'] ?? $user->getEmail());
        $user->setFirstName($data['first_name'] ?? $user->getFirstName());
        $user->setLastName($data['last_name'] ?? $user->getLastName());
        $user->setPhoneNumber($data['phone_number'] ?? $user->getPhoneNumber());
        $user->setAddress($data['address'] ?? $user->getAddress());
        $user->setDateOfBirth($data['date_of_birth'] ?? $user->getDateOfBirth());
        $user->setGender($data['gender'] ?? $user->getGender());
        $user->setActive($data['is_active'] ?? $user->isActive());

        // Handle password update
        if (!empty($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        // Handling profile picture upload
        $base64Content = $data['profile_picture_p'] ?? null;
        if ($base64Content) {
            // Validate Base64 format
            if (preg_match('/^data:image\/\w+;base64,/', $base64Content)) {
                $base64Content = substr($base64Content, strpos($base64Content, ',') + 1);
                $base64Content = base64_decode($base64Content, true);

                if ($base64Content === false) {
                    return new JsonResponse(['message' => 'Invalid image data'], Response::HTTP_BAD_REQUEST);
                }

                // Save the image to a temporary file
                $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '.png';
                file_put_contents($tempFilePath, $base64Content);

                // Create UploadedFile object to manage file operations
                $file = new UploadedFile($tempFilePath, 'profile_picture.png', 'image/png', null, true);

                // Move the file to the uploads directory
                $filename = md5(uniqid()) . '.png';
                try {
                    $file->move($this->getParameter('kernel.project_dir') . '/public/uploads', $filename);
                    $baseUrl = $this->getParameter('kernel.environment') === 'dev' ? $this->getParameter('DEV_BASE_URL') : $this->getParameter('PROD_BASE_URL');
                    $user->setProfilePicture($baseUrl . '/uploads/' . $filename);
                } catch (FileException $e) {
                    unlink($tempFilePath); // Clean up the temporary file
                    return new JsonResponse(['message' => 'File could not be saved'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return new JsonResponse(['message' => 'Invalid image data format'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Persist changes to the database
        $entityManager->flush();

        return new JsonResponse(['status' => 'User updated successfully', 'profilePicture' => $user->getProfilePicture()], JsonResponse::HTTP_OK);
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
