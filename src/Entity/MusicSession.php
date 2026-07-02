<?php

namespace App\Entity;

use App\Repository\MusicSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MusicSessionRepository::class)]
class MusicSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'musicSessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'musicSessions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Album $album = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $token = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    // =========================
    // SESSION STATE
    // =========================

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $isLocked = false; // 🔐 takeover pending

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $takeoverCode = null; // email code

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $takeoverRequestedAt = null;

    // =========================
    // TIMESTAMPS
    // =========================

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastActivity = null;

    #[ORM\Column(length: 64, nullable: true)]

    // =========================
    // Fingerprint
    // =========================
    private ?string $deviceFingerprint = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastActivity = new \DateTimeImmutable();
        $this->isActive = true;
        $this->isLocked = false;
    }

    // =========================
    // GETTERS / SETTERS
    // =========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): static
    {
        $this->album = $album;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName): static
    {
        $this->deviceName = $deviceName;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(bool $isLocked): static
    {
        $this->isLocked = $isLocked;
        return $this;
    }

    public function getTakeoverCode(): ?string
    {
        return $this->takeoverCode;
    }

    public function setTakeoverCode(?string $takeoverCode): static
    {
        $this->takeoverCode = $takeoverCode;
        return $this;
    }

    public function getTakeoverRequestedAt(): ?\DateTimeImmutable
    {
        return $this->takeoverRequestedAt;
    }

    public function setTakeoverRequestedAt(?\DateTimeImmutable $date): static
    {
        $this->takeoverRequestedAt = $date;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastActivity(): ?\DateTimeImmutable
    {
        return $this->lastActivity;
    }

    public function setLastActivity(?\DateTimeImmutable $lastActivity): static
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }

    public function getDeviceFingerprint(): ?string
    {
        return $this->deviceFingerprint;
    }

    public function setDeviceFingerprint(?string $deviceFingerprint): static
    {
        $this->deviceFingerprint = $deviceFingerprint;

        return $this;
    }
}