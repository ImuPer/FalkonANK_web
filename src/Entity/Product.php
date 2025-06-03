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

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column]
    private ?bool $active = null;

    /**
     * @var Collection<int, BasketProduct>
     */
    #[ORM\OneToMany(targetEntity: BasketProduct::class, mappedBy: 'product')]
    private Collection $basketProducts;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Shop $shop = null;

    
    public function __construct()
    {
        $this->basketProducts = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, BasketProduct>
     */
    public function getBasketProducts(): Collection
    {
        return $this->basketProducts;
    }

    public function addBasketProduct(BasketProduct $basketProduct): static
    {
        if (!$this->basketProducts->contains($basketProduct)) {
            $this->basketProducts->add($basketProduct);
            $basketProduct->setProduct($this);
        }

        return $this;
    }

    public function removeBasketProduct(BasketProduct $basketProduct): static
    {
        if ($this->basketProducts->removeElement($basketProduct)) {
            // set the owning side to null (unless already changed)
            if ($basketProduct->getProduct() === $this) {
                $basketProduct->setProduct(null);
            }
        }

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

    public function getFinalPrice(float $platformRate = 0.10, float $stripeRate = 0.029, float $stripeFixed = 0.25): float
{
    // Prix de base (depuis la BDD)
    $basePrice = (float) $this->price;

    // Commission de la plateforme (ex : 10%)
    $platformCommission = $basePrice * $platformRate;

    // Prix temporaire avant Stripe
    $subtotal = $basePrice + $platformCommission;

    // Commission Stripe (ex : 2.9% + 0.25€)
    $stripeCommission = ($subtotal * $stripeRate) + $stripeFixed;

    // Prix total que le client doit payer
    return round($subtotal + $stripeCommission, 2);
}

// public function getFinalPriceDetails(float $platformRate = 0.10, float $stripeRate = 0.029, float $stripeFixed = 0.25): array
// {
//     // Prix de base (ex : prix du produit depuis la BDD)
//     $basePrice = (float) $this->price;

//     // Commission de la plateforme
//     $platformCommission = $basePrice * $platformRate;

//     // Prix après ajout de la commission de la plateforme
//     $subtotal = $basePrice + $platformCommission;

//     // Commission Stripe
//     $stripeCommission = ($subtotal * $stripeRate) + $stripeFixed;

//     // Prix final payé par le client
//     $finalPrice = round($subtotal + $stripeCommission, 2);

//     // Retour sous forme de tableau associatif
//     return [
//         'base_price' => round($basePrice, 2),
//         'platform_commission' => round($platformCommission, 2),
//         'stripe_commission' => round($stripeCommission, 2),
//         'final_price' => $finalPrice
//     ];
// }

}
