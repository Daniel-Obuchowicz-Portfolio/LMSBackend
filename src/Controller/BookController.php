<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BookController extends AbstractController
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
            'updatedAt' => $book->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    private function handleImageUpload(string $base64Content): ?string
    {
        if (preg_match('/^data:image\/\w+;base64,/', $base64Content)) {
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
                $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/book', $filename);
                $baseUrl = $this->getParameter('kernel.environment') === 'dev' ? $this->getParameter('DEV_BASE_URL') : $this->getParameter('PROD_BASE_URL');
                return $baseUrl . '/uploads/book/' . $filename;
            } catch (FileException $e) {
                unlink($tempFilePath); // Clean up the temporary file
                return null;
            }
        }
        return null;
    }

    #[Route('/api/books', name: 'api_books_get', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getBooks(EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $books = $em->getRepository(Book::class)->findAll();
        $data = array_map([$this, 'formatBookData'], $books);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/fivebooks', name: 'api_books_get_five', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getBooksFive(EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $books = $em->getRepository(Book::class)->findBy([], null, 5);
        $data = array_map([$this, 'formatBookData'], $books);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/books/{id}', name: 'api_books_get_one', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function getBook(Book $book): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        return new JsonResponse($this->formatBookData($book), JsonResponse::HTTP_OK);
    }

    #[Route('/api/books/post', name: 'api_books_post', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function createBook(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

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

        if ($coverImage = $this->handleImageUpload($data['profile_picture_p'] ?? '')) {
            $book->setCoverImage($coverImage);
        } else if (isset($data['profile_picture_p'])) {
            return new JsonResponse(['message' => 'Invalid image data'], Response::HTTP_BAD_REQUEST);
        }

        $book->setCreatedAt(new \DateTimeImmutable());
        $book->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($book);
        $em->flush();

        return new JsonResponse($this->formatBookData($book), JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/books/{id}/put', name: 'api_books_put', methods: ['PUT'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateBook(Request $request, Book $book, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $data = json_decode($request->getContent(), true);

        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setIsbn($data['isbn']);
        $book->setPublicationDate($data['publicationDate']);
        $book->setPublisher($data['publisher']);
        $book->setGenre($data['genre']);
        $book->setSummary($data['summary']);
        $book->setPageCount($data['pageCount']);

        if ($coverImage = $this->handleImageUpload($data['profile_picture_p'] ?? '')) {
            $book->setCoverImage($coverImage);
        } else if (isset($data['profile_picture_p'])) {
            return new JsonResponse(['message' => 'Invalid image data'], Response::HTTP_BAD_REQUEST);
        }

        $book->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return new JsonResponse($this->formatBookData($book));
    }

    #[Route('/api/books/{id}/delete', name: 'api_books_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function deleteBook(Book $book, EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $em->remove($book);
        $em->flush();

        return new JsonResponse(['status' => 'Book deleted']);
    }

    #[Route('/api/count/books', name: 'api_books_count', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function countBooks(EntityManagerInterface $em): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $bookCount = $em->getRepository(Book::class)->count([]);
        return new JsonResponse(['count' => $bookCount], JsonResponse::HTTP_OK);
    }

    #[Route('/api/booksearch', name: 'api_books_search', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function searchBooks(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($errorResponse = $this->validateToken()) {
            return $errorResponse;
        }

        $searchTerm = $request->query->get('query');
        $boorRepository = $entityManager->getRepository(Book::class);
        $books = $boorRepository->createQueryBuilder('u')
            ->where('u.title LIKE :searchTerm OR u.author LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult();

        $data = array_map([$this, 'formatBookData'], $books);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }
}
