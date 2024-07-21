<?php

namespace App\Entity;

use App\Repository\BorrowingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BorrowingsRepository::class)]
class Borrowings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'borrowings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'borrowings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[ORM\JoinColumn(nullable: true)]
    private ?\DateTimeInterface $borrowing_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[ORM\JoinColumn(nullable: true)]
    private ?\DateTimeInterface $real_return_date = null;

    #[ORM\Column(length: 255)]
    private ?string $comments = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $prolongation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(Book $book): static
    {
        $this->book = $book;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getBorrowingDate(): ?\DateTimeInterface
    {
        return $this->borrowing_date;
    }

    public function setBorrowingDate(\DateTimeInterface $borrowing_date): static
    {
        $this->borrowing_date = $borrowing_date;
        return $this;
    }

    public function getRealReturnDate(): ?\DateTimeInterface
    {
        return $this->real_return_date;
    }

    public function setRealReturnDate(\DateTimeInterface $real_return_date): static
    {
        $this->real_return_date = $real_return_date;
        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;
        return $this;
    }

    public function getProlongation(): ?\DateTimeInterface
    {
        return $this->prolongation;
    }

    public function setProlongation(?\DateTimeInterface $prolongation): static
    {
        $this->prolongation = $prolongation;

        return $this;
    }
}
