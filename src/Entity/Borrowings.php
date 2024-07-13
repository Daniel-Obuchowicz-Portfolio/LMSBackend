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

    #[ORM\OneToOne(inversedBy: 'BookID', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $Book = null;

    #[ORM\OneToOne(inversedBy: 'UserID', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $User = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $borrowing_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $realreturndate = null;

    #[ORM\Column(length: 255)]
    private ?string $comments = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->Book;
    }

    public function setBook(Book $Book): static
    {
        $this->Book = $Book;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(User $User): static
    {
        $this->User = $User;

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

    public function getRealreturndate(): ?\DateTimeInterface
    {
        return $this->realreturndate;
    }

    public function setRealreturndate(\DateTimeInterface $realreturndate): static
    {
        $this->realreturndate = $realreturndate;

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
}
