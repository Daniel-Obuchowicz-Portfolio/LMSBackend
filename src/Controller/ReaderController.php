<?php

// src/Controller/ReaderController.php

namespace App\Controller;

use App\Entity\Reader;
use App\Repository\ReaderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/readers', name: 'api_readers_')]
class ReaderController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ReaderRepository $readerRepository): Response
    {
        $readers = $readerRepository->findAll();
        return $this->json($readers);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Reader $reader): Response
    {
        return $this->json($reader);
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        $reader = new Reader();
        $reader->setFirstName($data['firstName']);
        $reader->setLastName($data['lastName']);
        $reader->setEmail($data['email']);
        $reader->setDateOfBirth($data['dateOfBirth']);
        $reader->setGender($data['gender']);
        $reader->setPhoneNumber($data['phoneNumber']);
        $reader->setAddress($data['address']);
        $reader->setPassword($data['password']);
        $reader->setProfilePicture($data['profilePicture']);
        $reader->setRoles($data['roles']);
        $reader->setActive($data['isActive']);
        $reader->setCreatedAt(new \DateTimeImmutable());

        $em->persist($reader);
        $em->flush();

        return $this->json($reader, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Reader $reader, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        $reader->setFirstName($data['firstName']);
        $reader->setLastName($data['lastName']);
        $reader->setEmail($data['email']);
        $reader->setDateOfBirth($data['dateOfBirth']);
        $reader->setGender($data['gender']);
        $reader->setPhoneNumber($data['phoneNumber']);
        $reader->setAddress($data['address']);
        $reader->setPassword($data['password']);
        $reader->setProfilePicture($data['profilePicture']);
        $reader->setRoles($data['roles']);
        $reader->setActive($data['isActive']);
        $reader->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->json($reader);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Reader $reader, EntityManagerInterface $em): Response
    {
        $em->remove($reader);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
