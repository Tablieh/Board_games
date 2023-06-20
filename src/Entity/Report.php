<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $choix = null;

   /*  #[ORM\Column(nullable: true)]
    private ?bool $no = null; */

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $issueName = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $issueDescription = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isChoix(): ?bool
    {
        return $this->choix;
    }

    public function setChoix(?bool $choix): self
    {
        $this->choix = $choix;

        return $this;
    }

    /* public function isNo(): ?bool
    {
        return $this->no;
    }

    public function setNo(?bool $no): self
    {
        $this->no = $no;

        return $this;
    } */

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getIssueName(): ?string
    {
        return $this->issueName;
    }

    public function setIssueName(string $issueName): self
    {
        $this->issueName = $issueName;

        return $this;
    }

    public function getIssueDescription(): ?string
    {
        return $this->issueDescription;
    }

    public function setIssueDescription(string $issueDescription): self
    {
        $this->issueDescription = $issueDescription;

        return $this;
    }
}
