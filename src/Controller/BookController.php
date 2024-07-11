<?php

// src/Controller/BookController.php

namespace App\Controller;


use App\Entity\User;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\BookRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class BookController extends AbstractController
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

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
        // $book->setCoverImage($data['coverImage']);

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
                    $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/book', $filename);
                    $baseUrl = $this->getParameter('kernel.environment') === 'dev' ? $this->getParameter('DEV_BASE_URL') : $this->getParameter('PROD_BASE_URL');
                    $book->setCoverImage($baseUrl . '/uploads/book/' . $filename);
                } catch (FileException $e) {
                    unlink($tempFilePath); // Clean up the temporary file
                    return new JsonResponse(['message' => 'File could not be saved'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return new JsonResponse(['message' => 'Invalid image data format'], Response::HTTP_BAD_REQUEST);
            }
        }
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
                    $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/book', $filename);
                    $baseUrl = $this->getParameter('kernel.environment') === 'dev' ? $this->getParameter('DEV_BASE_URL') : $this->getParameter('PROD_BASE_URL');
                    $book->setCoverImage($baseUrl . '/uploads/book/' . $filename);
                } catch (FileException $e) {
                    unlink($tempFilePath); // Clean up the temporary file
                    return new JsonResponse(['message' => 'File could not be saved'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return new JsonResponse(['message' => 'Invalid image data format'], Response::HTTP_BAD_REQUEST);
            }
        }
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

    #[Route('/api/count/books', name: 'api_books_count', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function countBooks(EntityManagerInterface $em): JsonResponse
    {
        $bookCount = $em->getRepository(Book::class)->count([]);
        return new JsonResponse(['count' => $bookCount], JsonResponse::HTTP_OK);
    }

    #[Route('/api/booksearch', name: 'api_books_search', methods: ['GET'])]
    public function searchBooks(Request $request, EntityManagerInterface $entityManager): JsonResponse
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
        $boorRepository = $entityManager->getRepository(Book::class);
        $books = $boorRepository->createQueryBuilder('u')
            ->where('u.title LIKE :searchTerm OR u.author LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult();

        // Przygotowanie danych do odpowiedzi
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

        // Zwrócenie wyników jako odpowiedź JSON
        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }
}