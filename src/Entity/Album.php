<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
#[Vich\Uploadable]
#[UniqueEntity(fields: ['name'], message: 'Un album avec ce nom existe déjà.')]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column(length: 255)]
    private ?string $recordLabel = null;

    // =====================
    // IMAGE
    // =====================

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[Vich\UploadableField(mapping: 'album_image', fileNameProperty: 'coverImage')]
    private ?File $coverImageFile = null;

    // =====================
    // DATES
    // =====================

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // =====================
    // ID
    // =====================

    public function getId(): ?int
    {
        return $this->id;
    }

    // =====================
    // NAME
    // =====================

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    // =====================
    // RELEASE DATE
    // =====================

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(\DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    // =====================
    // RECORD LABEL
    // =====================

    public function getRecordLabel(): ?string
    {
        return $this->recordLabel;
    }

    public function setRecordLabel(string $recordLabel): static
    {
        $this->recordLabel = $recordLabel;

        return $this;
    }

    // =====================
    // COVER IMAGE
    // =====================

    public function setCoverImageFile(?File $file = null): void
    {
        $this->coverImageFile = $file;

        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getCoverImageFile(): ?File
    {
        return $this->coverImageFile;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    // =====================
    // CREATED AT
    // =====================

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    // =====================
    // UPDATED AT
    // =====================

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}