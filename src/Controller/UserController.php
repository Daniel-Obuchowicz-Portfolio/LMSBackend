<?php
namespace App\Controller;

use App\Entity\User;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserController extends AbstractController
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    private function validateToken(): ?JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return new JsonResponse(['message' => 'Token not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        return null;
    }

    private function formatUserData(User $user): array
    {
        return [
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

    private function handleImageUpload(?string $base64Content): ?string
    {
        if ($base64Content && preg_match('/^data:image\/\w+;base64,/', $base64Content)) {
            $base64Content = substr($base64Content, strpos($base64Content, ',') + 1);
            $base64Content = base64_decode($base64Content, true);

            if ($base64Content === false) {
                return null;
            }

            $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '.png';
            file_put_contents($tempFilePath, $base64Content);

            $file = new UploadedFile($tempFilePath, 'profile_picture.png', 'image/png', null, true);
            $filename = md5(uniqid()) . '.png';
            try {
                $file->move($this->getParameter('kernel.project_dir') . '/public/uploads', $filename);
                $baseUrl = $this->getParameter('kernel.environment') === 'dev' ? $this->getParameter('DEV_BASE_URL') : $this->getParameter('PROD_BASE_URL');
                return $baseUrl . '/uploads/' . $filename;
            } catch (FileException $e) {
                unlink($tempFilePath);
                return null;
            }
        }
        return null;
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
        $user->setDateOfBirth($data['date_of_birth'] ?? null);
        $user->setGender($data['gender'] ?? null);
        $user->setPhoneNumber($data['phone_number'] ?? null);
        $user->setAddress($data['address'] ?? null);
        $user->setActive($data['is_active'] ?? true);

        $profilePictureUrl = $this->handleImageUpload($data['profile_picture_p'] ?? null);
        if ($profilePictureUrl) {
            $user->setProfilePicture($profilePictureUrl);
        }

        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User created'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/users/{id}', name: 'api_user_get', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getUserinfo(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->formatUserData($user), JsonResponse::HTTP_OK);
    }

    #[Route('/api/users', name: 'api_users_get_all', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getAllUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $users = $entityManager->getRepository(User::class)->findAll();
        $data = array_map([$this, 'formatUserData'], $users);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/fiveusers', name: 'api_users_get_five', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getFiveUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $users = $entityManager->getRepository(User::class)->findBy([], null, 5);
        $data = array_map([$this, 'formatUserData'], $users);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/users/{id}/put', name: 'api_user_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateUser(int $id, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $user->setEmail($data['email'] ?? $user->getEmail());
        $user->setFirstName($data['first_name'] ?? $user->getFirstName());
        $user->setLastName($data['last_name'] ?? $user->getLastName());
        $user->setPhoneNumber($data['phone_number'] ?? $user->getPhoneNumber());
        $user->setAddress($data['address'] ?? $user->getAddress());
        $user->setDateOfBirth($data['date_of_birth'] ?? $user->getDateOfBirth());
        $user->setGender($data['gender'] ?? $user->getGender());
        $user->setActive($data['is_active'] ?? $user->isActive());

        if (!empty($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $profilePictureUrl = $this->handleImageUpload($data['profile_picture_p'] ?? null);
        if ($profilePictureUrl) {
            $user->setProfilePicture($profilePictureUrl);
        }

        $entityManager->flush();

        return new JsonResponse(['status' => 'User updated successfully', 'profilePicture' => $user->getProfilePicture()], JsonResponse::HTTP_OK);
    }

    #[Route('/api/users/{id}/delete', name: 'api_user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User deleted'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/user', name: 'api_user', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getUserInfos(): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'User not found or not an instance of User'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse($this->formatUserData($user), JsonResponse::HTTP_OK);
    }

    #[Route('/api/count/users', name: 'api_users_count', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function countUsers(EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $userCount = $em->getRepository(User::class)->count([]);
        return new JsonResponse(['count' => $userCount], JsonResponse::HTTP_OK);
    }

    #[Route('/api/usersearch', name: 'api_users_search', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function searchUsers(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $searchTerm = $request->query->get('query');

        $users = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.firstName LIKE :searchTerm OR u.lastName LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult();

        $data = array_map([$this, 'formatUserData'], $users);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/usersmonthly', name: 'api_users_monthly', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getMonthlyUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $users = $entityManager->getRepository(User::class)->findAll();

        $monthlyUsers = array_fill(1, 12, 0);

        foreach ($users as $user) {
            $month = (int) $user->getCreatedAt()->format('n');
            $monthlyUsers[$month]++;
        }

        return new JsonResponse(array_values($monthlyUsers), JsonResponse::HTTP_OK);
    }
}
