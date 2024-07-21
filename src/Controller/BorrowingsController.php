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

class BorrowingsController extends AbstractController
{
    private $tokenStorage;

    private function setDateOrDefault(?string $date, \DateTime $default): \DateTime
    {
        return $date ? new \DateTime($date) : $default;
    }

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/api/borrowings', name: 'getallBorrowings', methods: ['GET'])]
    public function getallBorrowings(EntityManagerInterface $em): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['message' => 'Token not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $borrowings = $em->getRepository(Borrowings::class)->findAll();

        $data = [];
        foreach ($borrowings as $borrowing) {
            $book = $borrowing->getBook();
            $bookDetails = [
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

            $borrower = $borrowing->getUser();
            $borrowerDetails = [
                'id' => $borrower->getId(),
                'email' => $borrower->getEmail(),
                'first_name' => $borrower->getFirstName(),
                'last_name' => $borrower->getLastName(),
                'date_of_birth' => $borrower->getDateOfBirth(),
                'gender' => $borrower->getGender(),
                'phone_number' => $borrower->getPhoneNumber(),
                'address' => $borrower->getAddress(),
                'profile_picture' => $borrower->getProfilePicture(),
                'is_active' => $borrower->isActive(),
                'created_at' => $borrower->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $borrower->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];

            $data[] = [
                'id' => $borrowing->getId(),
                'book' => $bookDetails,
                'user' => $borrowerDetails,
                'borrowing_date' => $borrowing->getBorrowingDate()->format('Y-m-d'),
                'realreturndate' => $borrowing->getRealReturnDate()->format('Y-m-d'),
                'comments' => $borrowing->getComments(),
                'status' => $borrowing->getStatus(),
                'prolongation' => $borrowing->getProlongation()?->format('Y-m-d') ?? '0000-00-00',
            ];
        }

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }


    #[Route('/api/borrowings/book/{id}', name: 'getBorrowingsByBook', methods: ['GET'])]
    public function getBorrowingsByBook(int $id, EntityManagerInterface $em): JsonResponse
    {
        $borrowings = $em->getRepository(Borrowings::class)->findBy(['book' => $id]);

        $data = [];
        foreach ($borrowings as $borrowing) {
            $book = $borrowing->getBook();
            $bookDetails = [
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

            $borrower = $borrowing->getUser();
            $borrowerDetails = [
                'id' => $borrower->getId(),
                'email' => $borrower->getEmail(),
                'first_name' => $borrower->getFirstName(),
                'last_name' => $borrower->getLastName(),
                'date_of_birth' => $borrower->getDateOfBirth(),
                'gender' => $borrower->getGender(),
                'phone_number' => $borrower->getPhoneNumber(),
                'address' => $borrower->getAddress(),
                'profile_picture' => $borrower->getProfilePicture(),
                'is_active' => $borrower->isActive(),
                'created_at' => $borrower->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $borrower->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];

            $data[] = [
                'id' => $borrowing->getId(),
                'book' => $bookDetails,
                'user' => $borrowerDetails,
                'borrowing_date' => $borrowing->getBorrowingDate()->format('Y-m-d'),
                'realreturndate' => $borrowing->getRealReturnDate()->format('Y-m-d'),
                'comments' => $borrowing->getComments(),
                'status' => $borrowing->getStatus(),
                'prolongation' => $borrowing->getProlongation()?->format('Y-m-d') ?? '0000-00-00',
            ];
        }

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/borrowings/user/{id}', name: 'getBorrowingsByUser', methods: ['GET'])]
    public function getBorrowingsByUser(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $sortField = $request->query->get('sortField', 'id'); // Default sort field
        $sortOrder = $request->query->get('sortOrder', 'DESC'); // Default sort order

        $borrowings = $em->getRepository(Borrowings::class)->findBy(
            ['user' => $id],
            [$sortField => $sortOrder]
        );

        $data = [];
        foreach ($borrowings as $borrowing) {
            $book = $borrowing->getBook();
            $bookDetails = [
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

            $borrower = $borrowing->getUser();
            $borrowerDetails = [
                'id' => $borrower->getId(),
                'email' => $borrower->getEmail(),
                'first_name' => $borrower->getFirstName(),
                'last_name' => $borrower->getLastName(),
                'date_of_birth' => $borrower->getDateOfBirth(),
                'gender' => $borrower->getGender(),
                'phone_number' => $borrower->getPhoneNumber(),
                'address' => $borrower->getAddress(),
                'profile_picture' => $borrower->getProfilePicture(),
                'is_active' => $borrower->isActive(),
                'created_at' => $borrower->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $borrower->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];

            $data[] = [
                'id' => $borrowing->getId(),
                'book' => $bookDetails,
                'user' => $borrowerDetails,
                'borrowing_date' => $borrowing->getBorrowingDate()->format('Y-m-d'),
                'realreturndate' => $borrowing->getRealReturnDate()->format('Y-m-d'),
                'comments' => $borrowing->getComments(),
                'status' => $borrowing->getStatus(),
                'prolongation' => $borrowing->getProlongation()?->format('Y-m-d') ?? '0000-00-00',
            ];
        }

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }
    #[Route('/api/readerdetails/{id}/borrow', name: 'addBorrowing', methods: ['POST'])]
    public function addBorrowing(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
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
        $borrowing->setRealReturnDate($data['realreturndate'] ? new \DateTime($data['realreturndate']) : new \DateTime('0000-00-00'));
        $borrowing->setProlongation(null);
        $borrowing->setComments($data['comments'] ?? '');
        $borrowing->setStatus('pending');

        $em->persist($borrowing);
        $em->flush();

        return new JsonResponse(['message' => 'Borrowing record added successfully'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/borrowings/{id}/prolongation', name: 'updateProlongation', methods: ['POST'])]
    public function updateProlongation(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
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
    public function updateRealReturnDate(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
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
    public function getBorrowingsByUserByStatus(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $sortField = $request->query->get('sortField', 'id'); // Default sort field
        $sortOrder = $request->query->get('sortOrder', 'DESC'); // Default sort order
        $status = $request->query->get('status'); // Get status from query

        // Validate sortField and sortOrder values
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

        $data = [];
        foreach ($borrowings as $borrowing) {
            $book = $borrowing->getBook();
            $bookDetails = [
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

            $borrower = $borrowing->getUser();
            $borrowerDetails = [
                'id' => $borrower->getId(),
                'email' => $borrower->getEmail(),
                'first_name' => $borrower->getFirstName(),
                'last_name' => $borrower->getLastName(),
                'date_of_birth' => $borrower->getDateOfBirth(),
                'gender' => $borrower->getGender(),
                'phone_number' => $borrower->getPhoneNumber(),
                'address' => $borrower->getAddress(),
                'profile_picture' => $borrower->getProfilePicture(),
                'is_active' => $borrower->isActive(),
                'created_at' => $borrower->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $borrower->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];

            $data[] = [
                'id' => $borrowing->getId(),
                'book' => $bookDetails,
                'user' => $borrowerDetails,
                'borrowing_date' => $borrowing->getBorrowingDate()->format('Y-m-d'),
                'realreturndate' => $borrowing->getRealReturnDate() ? $borrowing->getRealReturnDate()->format('Y-m-d') : 'N/A',
                'comments' => $borrowing->getComments(),
                'status' => $borrowing->getStatus(),
                'prolongation' => $borrowing->getProlongation() ? $borrowing->getProlongation()->format('Y-m-d') : '0000-00-00',
            ];
        }

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }




}
