<?php

// src/Controller/BookController.php

namespace App\Controller;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class BookController extends AbstractController
{
    #[Route('/api/books', name: 'api_books_get', methods: ['GET'])]
    public function getBooks(EntityManagerInterface $em): JsonResponse
    {
        $books = $em->getRepository(Book::class)->findAll();
        
        $data = [];
        foreach ($books as $book) {
            $data[] = [
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
                'updatedAt' => $book->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/books/{id}', name: 'api_books_get_one', methods: ['GET'])]
    public function getBook(Book $book): JsonResponse
    {
        $data = [
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
            'updatedAt' => $book->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/books/post', name: 'api_books_post', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function createBook(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $book = new Book();
        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setIsbn($data['isbn']);
        $book->setPublicationDate($data['publicationDate']);
        $book->setPublisher($data['publisher']);
        $book->setGenre($data['genre']);
        $book->setSummary($data['summary']);
        $book->setPageCount($data['pageCount']);
        $book->setCoverImage($data['coverImage']);
        $book->setCreatedAt(new \DateTimeImmutable());
        $book->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($book);
        $em->flush();

        return new JsonResponse($book, JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/books/{id}/put', name: 'api_books_put', methods: ['PUT'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateBook(Request $request, Book $book, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setIsbn($data['isbn']);
        $book->setPublicationDate($data['publicationDate']);
        $book->setPublisher($data['publisher']);
        $book->setGenre($data['genre']);
        $book->setSummary($data['summary']);
        $book->setPageCount($data['pageCount']);
        $book->setCoverImage($data['coverImage']);
        $book->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return new JsonResponse($book);
    }

    #[Route('/api/books/{id}/delete', name: 'api_books_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function deleteBook(Book $book, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($book);
        $em->flush();

        return new JsonResponse(['status' => 'Book deleted']);
    }
}
