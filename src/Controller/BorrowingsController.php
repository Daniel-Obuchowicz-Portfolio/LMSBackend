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
        $borrowing->setRealReturnDate(new \DateTime($data['realreturndate']));
        $borrowing->setComments($data['comments'] ?? '');

        $em->persist($borrowing);
        $em->flush();

        return new JsonResponse(['message' => 'Borrowing record added successfully'], JsonResponse::HTTP_CREATED);
    }
}