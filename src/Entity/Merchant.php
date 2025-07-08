<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MerchantRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: MerchantRepository::class)]
class Merchant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le nom de la boutique est obligatoire.")]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Veuillez télécharger une licence légale.")]
    private ?string $licenseFile = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'merchants')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isApproved = false; // Par défaut, le marchand n'est pas approuvé

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\OneToMany(targetEntity: Merchant::class, mappedBy: 'user')]
    private Collection $merchants;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reponse = null;

    #[ORM\ManyToOne(inversedBy: 'merchants')]
    private ?City $city = null;

    // bank infos   

    #[ORM\Column(nullable: true)]
    private ?string $bankHolder = null;
    
    #[ORM\Column(nullable: true)]
    private ?string $bankName = null;
    
    #[ORM\Column(nullable: true)]
    private ?string $iban = null;
    
    #[ORM\Column(nullable: true)]
    private ?string $swift = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nifManeger = null;

     
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->shops = new ArrayCollection();
    }

    // Getters et setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getLicenseFile(): ?string
    {
        return $this->licenseFile;
    }

    public function setLicenseFile(string $licenseFile): self
    {
        $this->licenseFile = $licenseFile;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): self
    {
        $this->isApproved = $isApproved;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(?string $reponse): static
    {
        $this->reponse = $reponse;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

        return $this;
    }

    
    public function getBankHolder(): ?string { return $this->bankHolder; }
    public function setBankHolder(?string $value): self { $this->bankHolder = $value; return $this; }
    
    public function getBankName(): ?string { return $this->bankName; }
    public function setBankName(?string $value): self { $this->bankName = $value; return $this; }
    
    public function getIban(): ?string { return $this->iban; }
    public function setIban(?string $value): self { $this->iban = $value; return $this; }
    
    public function getSwift(): ?string { return $this->swift; }
    public function setSwift(?string $value): self { $this->swift = $value; return $this; }

    public function getnifManeger(): ?string
    {
        return $this->nifManeger;
    }

    public function setnifManeger(?string $nifManeger): static
    {
        $this->nifManeger = $nifManeger;

        return $this;
    }   


}