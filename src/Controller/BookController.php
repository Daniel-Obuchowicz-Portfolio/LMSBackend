<?php

// src/Controller/BookController.php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/books', name: 'api_books_')]
class BookController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        $books = $bookRepository->findAll();
        return $this->json($books);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->json($book);
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
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

        return $this->json($book, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Book $book, EntityManagerInterface $em): Response
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

        return $this->json($book);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Book $book, EntityManagerInterface $em): Response
    {
        $em->remove($book);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
