<?php

namespace App\Entity;

use App\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CityRepository::class)]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateUp = null;

    /**
     * @var Collection<int, Shop>
     */
    #[ORM\OneToMany(targetEntity: Shop::class, mappedBy: 'city')]
    private Collection $shops;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'city_beneficiary')]
    private Collection $orders;

    /**
     * @var Collection<int, Merchant>
     */
    #[ORM\OneToMany(targetEntity: Merchant::class, mappedBy: 'city')]
    private Collection $merchants;

    /**
     * @var Collection<int, Carrier>
     */
    #[ORM\OneToMany(targetEntity: Carrier::class, mappedBy: 'city')]
    private Collection $carriers;

    public function __construct()
    {
        $this->shops = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->merchants = new ArrayCollection();
        $this->carriers = new ArrayCollection();
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

    public function getDateAt(): ?\DateTimeImmutable
    {
        return $this->dateAt;
    }

    public function setDateAt(\DateTimeImmutable $dateAt): static
    {
        $this->dateAt = $dateAt;

        return $this;
    }

    public function getDateUp(): ?\DateTimeInterface
    {
        return $this->dateUp;
    }

    public function setDateUp(?\DateTimeInterface $dateUp): static
    {
        $this->dateUp = $dateUp;

        return $this;
    }

    /**
     * @return Collection<int, Shop>
     */
    public function getShops(): Collection
    {
        return $this->shops;
    }

    public function addShop(Shop $shop): static
    {
        if (!$this->shops->contains($shop)) {
            $this->shops->add($shop);
            $shop->setCity($this);
        }

        return $this;
    }

    public function removeShop(Shop $shop): static
    {
        if ($this->shops->removeElement($shop)) {
            // set the owning side to null (unless already changed)
            if ($shop->getCity() === $this) {
                $shop->setCity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setCityBeneficiary($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getCityBeneficiary() === $this) {
                $order->setCityBeneficiary(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Merchant>
     */
    public function getMerchants(): Collection
    {
        return $this->merchants;
    }

    public function addMerchant(Merchant $merchant): static
    {
        if (!$this->merchants->contains($merchant)) {
            $this->merchants->add($merchant);
            $merchant->setCity($this);
        }

        return $this;
    }

    public function removeMerchant(Merchant $merchant): static
    {
        if ($this->merchants->removeElement($merchant)) {
            // set the owning side to null (unless already changed)
            if ($merchant->getCity() === $this) {
                $merchant->setCity(null);
            }
        }

        return $this;
    }

    public function __toString(): string
{
    return $this->name; // ou autre propriété que tu veux afficher
}

    /**
     * @return Collection<int, Carrier>
     */
    public function getCarriers(): Collection
    {
        return $this->carriers;
    }

    public function addCarrier(Carrier $carrier): static
    {
        if (!$this->carriers->contains($carrier)) {
            $this->carriers->add($carrier);
            $carrier->setCity($this);
        }

        return $this;
    }

    public function removeCarrier(Carrier $carrier): static
    {
        if ($this->carriers->removeElement($carrier)) {
            // set the owning side to null (unless already changed)
            if ($carrier->getCity() === $this) {
                $carrier->setCity(null);
            }
        }

        return $this;
    }
}
