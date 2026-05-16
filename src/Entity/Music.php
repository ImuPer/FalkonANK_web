<?php

namespace App\Entity;

use App\Repository\MusicRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: MusicRepository::class)]
#[Vich\Uploadable]
class Music
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $artist = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $album = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $releaseDate = null;

    // =====================
    // IMAGE
    // =====================

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[Vich\UploadableField(mapping: 'music_image', fileNameProperty: 'coverImage')]
    private ?File $coverImageFile = null;

    // =====================
    // AUDIO
    // =====================

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $audioFile = null;

    #[Vich\UploadableField(mapping: 'music_file', fileNameProperty: 'audioFile')]
    private ?File $audioFileFile = null;

    // =====================
    // STATS
    // =====================

    #[ORM\Column]
    private ?int $views = 0;

    #[ORM\Column]
    private ?bool $isPublished = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'music')]
    private ?Product $product = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lyrics = null;

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
    // TITLE
    // =====================

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    // =====================
    // ARTIST
    // =====================

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(string $artist): static
    {
        $this->artist = $artist;
        return $this;
    }

    // =====================
    // ALBUM
    // =====================

    public function getAlbum(): ?string
    {
        return $this->album;
    }

    public function setAlbum(?string $album): static
    {
        $this->album = $album;
        return $this;
    }

    // =====================
    // DURATION
    // =====================

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    // =====================
    // GENRE
    // =====================

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;
        return $this;
    }

    // =====================
    // RELEASE DATE
    // =====================

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;
        return $this;
    }

    // =====================
    // IMAGE FILE
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
    // AUDIO FILE
    // =====================

    public function setAudioFileFile(?File $file = null): void
    {
        $this->audioFileFile = $file;

        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getAudioFileFile(): ?File
    {
        return $this->audioFileFile;
    }

    public function getAudioFile(): ?string
    {
        return $this->audioFile;
    }

    public function setAudioFile(?string $audioFile): static
    {
        $this->audioFile = $audioFile;
        return $this;
    }

    // =====================
    // VIEWS
    // =====================

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(int $views): static
    {
        $this->views = $views;
        return $this;
    }

    // =====================
    // PUBLISHED
    // =====================

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
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

    // =====================
    // PRODUCT
    // =====================

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getLyrics(): ?string
    {
        return $this->lyrics;
    }

    public function setLyrics(?string $lyrics): static
    {
        $this->lyrics = $lyrics;

        return $this;
    }
}