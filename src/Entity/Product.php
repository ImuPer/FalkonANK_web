<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $update_at = null;

    #[ORM\Column(length: 255)]
    private ?string $user_email = null;

    #[ORM\Column(length: 255)]
    private ?string $img = null;

    // 💰 Prix (DECIMAL → string en PHP)
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: BasketProduct::class)]
    private Collection $basketProducts;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Shop $shop = null;

    // ⚖️ Poids (ex: "1.5 kg")
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $weight = null;

    // 📐 Dimensions en cm (DECIMAL)
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $dimensionW = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $dimensionH = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $dimensionL = null;

    public function __construct()
    {
        $this->basketProducts = new ArrayCollection();
    }

    // =====================
    // Getters / Setters
    // =====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateAt(): ?\DateTimeImmutable
    {
        return $this->date_at;
    }

    public function setDateAt(\DateTimeImmutable $date_at): static
    {
        $this->date_at = $date_at;
        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->update_at;
    }

    public function setUpdateAt(?\DateTimeImmutable $update_at): static
    {
        $this->update_at = $update_at;
        return $this;
    }

    public function getUserEmail(): ?string
    {
        return $this->user_email;
    }

    public function setUserEmail(string $user_email): static
    {
        $this->user_email = $user_email;
        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(string $img): static
    {
        $this->img = $img;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    public function setShop(?Shop $shop): static
    {
        $this->shop = $shop;
        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): static
    {
        $this->weight = $weight;
        return $this;
    }

    public function getDimensionW(): ?string
    {
        return $this->dimensionW;
    }

    public function setDimensionW(?string $dimensionW): static
    {
        $this->dimensionW = $dimensionW;
        return $this;
    }

    public function getDimensionH(): ?string
    {
        return $this->dimensionH;
    }

    public function setDimensionH(?string $dimensionH): static
    {
        $this->dimensionH = $dimensionH;
        return $this;
    }

    public function getDimensionL(): ?string
    {
        return $this->dimensionL;
    }

    public function setDimensionL(?string $dimensionL): static
    {
        $this->dimensionL = $dimensionL;
        return $this;
    }

    // =====================
    // Prix final (exemple)
    // =====================
    public function getFinalPrice(
        float $platformRate = 0.05,
        float $stripeRate = 0.029,
        float $stripeFixed = 0.25
    ): float {
        $basePrice = (float) $this->price;
        $platformCommission = $basePrice * $platformRate;
        $subtotal = $basePrice + $platformCommission;
        $stripeCommission = ($subtotal * $stripeRate) + $stripeFixed;

        return round($subtotal + $stripeCommission, 2);
    }
}
