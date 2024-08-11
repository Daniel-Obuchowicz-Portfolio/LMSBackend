<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Borrowings;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BorrowingsController extends AbstractController
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

    private function formatBookData(Book $book): array
    {
        return [
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'isbn' => $book->getIsbn(),
            'publicationDate' => $book->getPublicationDate(),
            'publisher' => $book->getPublisher(),
            'genre' => $book->getGenre(),
            'summary' => $book->getSummary(),
            'pageCount' => $book->getPageCount(),
            'coverImage' => $book->getCoverImage(),
            'createdAt' => $book->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $book->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
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

    private function formatBorrowingData(Borrowings $borrowing): array
    {
        return [
            'id' => $borrowing->getId(),
            'book' => $this->formatBookData($borrowing->getBook()),
            'user' => $this->formatUserData($borrowing->getUser()),
            'borrowing_date' => $borrowing->getBorrowingDate()->format('Y-m-d'),
            'realreturndate' => $borrowing->getRealReturnDate() ? $borrowing->getRealReturnDate()->format('Y-m-d') : 'N/A',
            'comments' => $borrowing->getComments(),
            'status' => $borrowing->getStatus(),
            'prolongation' => $borrowing->getProlongation()?->format('Y-m-d') ?? '0000-00-00',
        ];
    }

    // Here is the method that was missing
    private function setDateOrDefault(?string $date, \DateTime $default): \DateTime
    {
        return $date ? new \DateTime($date) : $default;
    }

    #[Route('/api/borrowings', name: 'getallBorrowings', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getallBorrowings(EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $borrowings = $em->getRepository(Borrowings::class)->findAll();
        $data = array_map([$this, 'formatBorrowingData'], $borrowings);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/borrowings/book/{id}', name: 'getBorrowingsByBook', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getBorrowingsByBook(int $id, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $borrowings = $em->getRepository(Borrowings::class)->findBy(['book' => $id]);
        $data = array_map([$this, 'formatBorrowingData'], $borrowings);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/borrowings/user/{id}', name: 'getBorrowingsByUser', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getBorrowingsByUser(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $sortField = $request->query->get('sortField', 'id');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        $borrowings = $em->getRepository(Borrowings::class)->findBy(
            ['user' => $id],
            [$sortField => $sortOrder]
        );

        $data = array_map([$this, 'formatBorrowingData'], $borrowings);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/readerdetails/{id}/borrow', name: 'addBorrowing', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function addBorrowing(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['book_id'], $data['borrowing_date'], $data['realreturndate'])) {
            return new JsonResponse(['message' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->find($id);
        $book = $em->getRepository(Book::class)->find($data['book_id']);

        if (!$user || !$book) {
            return new JsonResponse(['message' => 'User or Book not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $borrowing = new Borrowings();
        $borrowing->setUser($user);
        $borrowing->setBook($book);
        $borrowing->setBorrowingDate(new \DateTime($data['borrowing_date']));
        $borrowing->setRealReturnDate($this->setDateOrDefault($data['realreturndate'], new \DateTime('0000-00-00')));
        $borrowing->setProlongation(null);
        $borrowing->setComments($data['comments'] ?? '');
        $borrowing->setStatus('pending');

        $em->persist($borrowing);
        $em->flush();

        return new JsonResponse(['message' => 'Borrowing record added successfully'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/borrowings/{id}/prolongation', name: 'updateProlongation', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateProlongation(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['prolongation'])) {
            return new JsonResponse(['message' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $borrowing = $em->getRepository(Borrowings::class)->find($id);

        if (!$borrowing) {
            return new JsonResponse(['message' => 'Borrowing record not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $borrowing->setProlongation($this->setDateOrDefault($data['prolongation'], new \DateTime('0000-00-00')));

        $em->persist($borrowing);
        $em->flush();

        return new JsonResponse(['message' => 'Prolongation updated successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/borrowings/{id}/realreturndate', name: 'updateRealReturnDate', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateRealReturnDate(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['realreturndate'])) {
            return new JsonResponse(['message' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $borrowing = $em->getRepository(Borrowings::class)->find($id);

        if (!$borrowing) {
            return new JsonResponse(['message' => 'Borrowing record not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $borrowing->setRealReturnDate($this->setDateOrDefault($data['realreturndate'], new \DateTime('0000-00-00')));
        $borrowing->setStatus('returned');

        $em->persist($borrowing);
        $em->flush();

        return new JsonResponse(['message' => 'Real return date updated successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/borrowingsbystatus/user/{id}', name: 'getBorrowingsByUserByStatus', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getBorrowingsByUserByStatus(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $sortField = $request->query->get('sortField', 'id');
        $sortOrder = $request->query->get('sortOrder', 'DESC');
        $status = $request->query->get('status');

        $validSortFields = ['id', 'borrowing_date', 'realreturndate', 'status'];
        if (!in_array($sortField, $validSortFields)) {
            return new JsonResponse(['message' => 'Invalid sort field'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $validSortOrders = ['ASC', 'DESC'];
        if (!in_array($sortOrder, $validSortOrders)) {
            return new JsonResponse(['message' => 'Invalid sort order'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $criteria = ['user' => $id];
        if ($status) {
            $criteria['status'] = $status;
        }

        $borrowings = $em->getRepository(Borrowings::class)->findBy(
            $criteria,
            [$sortField => $sortOrder]
        );

        $data = array_map([$this, 'formatBorrowingData'], $borrowings);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/borrowings/5/user/{id}', name: 'getBorrowings5ByUser', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getBorrowings5ByUser(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $sortField = $request->query->get('sortField', 'id');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        $borrowings = $em->getRepository(Borrowings::class)->findBy(
            ['user' => $id],
            [$sortField => $sortOrder],
            5
        );

        $data = array_map([$this, 'formatBorrowingData'], $borrowings);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/filteredBorrowings', name: 'getFilteredBorrowings', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getFilteredBorrowings(EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $borrowings = $em->getRepository(Borrowings::class)->findAll();
        $currentDate = new \DateTime();
        $thresholdDate = $currentDate->modify('-30 days');

        $data = array_filter(array_map(function (Borrowings $borrowing) use ($currentDate, $thresholdDate) {
            $borrowingDate = $borrowing->getBorrowingDate();
            $status = $borrowing->getStatus();
            $prolongation = $borrowing->getProlongation();

            if ($borrowingDate < $thresholdDate && $status === 'pending') {
                if ($prolongation === null || $prolongation > $currentDate) {
                    return $this->formatBorrowingData($borrowing);
                }
            }
            return null;
        }, $borrowings));

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/filteredBorrowingsfive', name: 'getFilteredBorrowingsfive', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getFilteredBorrowingsfive(EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $currentDate = new \DateTime();
        $thresholdDate = (clone $currentDate)->modify('-30 days');

        $borrowings = $em->getRepository(Borrowings::class)->findAll();

        $data = [];
        $count = 0;
        foreach ($borrowings as $borrowing) {
            $borrowingDate = $borrowing->getBorrowingDate();
            $status = $borrowing->getStatus();
            $prolongation = $borrowing->getProlongation();

            if ($borrowingDate < $thresholdDate && $status === 'pending') {
                if ($prolongation === null || $prolongation > $currentDate) {
                    $data[] = $this->formatBorrowingData($borrowing);
                    $count++;
                }
                if ($count >= 5) {
                    break;
                }
            }
        }

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/borrowings/monthly', name: 'api_borrowings_monthly', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getMonthlyBorrowings(EntityManagerInterface $em): JsonResponse
    {
        $borrowings = $em->getRepository(Borrowings::class)->findAll();

        $monthlyBorrowings = array_fill(1, 12, 0);

        foreach ($borrowings as $borrowing) {
            $month = (int) $borrowing->getBorrowingDate()->format('n');
            $monthlyBorrowings[$month]++;
        }

        $result = array_values($monthlyBorrowings);

        return new JsonResponse($result, JsonResponse::HTTP_OK);
    }
}
