<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ref = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $order_date = null;


    #[ORM\Column]
    private ?float $total_amount = null;

    #[ORM\Column(length: 255)]
    private ?string $order_status = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customer_note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $internal_note = null;

    #[ORM\Column]
    private ?bool $refund = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refund_status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $refund_note = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Basket $Basket = null;

    #[ORM\OneToMany(mappedBy: 'orderC', targetEntity: BasketProduct::class)]
    private Collection $basketProducts;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $beneficiaryName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $beneficiaryAddress = null;
    private ?string $phone = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?City $city_beneficiary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $beneficiary_email = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $refund_amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount_final = null;


    public function __construct()
    {
        $this->basketProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function setRef(string $ref): static
    {
        $this->ref = $ref;

        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->order_date;
    }

    public function setOrderDate(\DateTimeInterface $order_date): static
    {
        $this->order_date = $order_date;

        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->total_amount;
    }

    public function setTotalAmount(float $total_amount): static
    {
        $this->total_amount = $total_amount;

        return $this;
    }

    public function getOrderStatus(): ?string
    {
        return $this->order_status;
    }

    public function setOrderStatus(string $order_status): static
    {
        $this->order_status = $order_status;

        return $this;
    }

    public function getCustomerNote(): ?string
    {
        return $this->customer_note;
    }

    public function setCustomerNote(?string $customer_note): static
    {
        $this->customer_note = $customer_note;

        return $this;
    }

    public function getInternalNote(): ?string
    {
        return $this->internal_note;
    }

    public function setInternalNote(?string $internal_note): static
    {
        $this->internal_note = $internal_note;

        return $this;
    }

    public function isRefund(): ?bool
    {
        return $this->refund;
    }

    public function setRefund(bool $refund): static
    {
        $this->refund = $refund;

        return $this;
    }

    public function getRefundStatus(): ?string
    {
        return $this->refund_status;
    }

    public function setRefundStatus(?string $refund_status): static
    {
        $this->refund_status = $refund_status;

        return $this;
    }

    public function getRefundNote(): ?string
    {
        return $this->refund_note;
    }

    public function setRefundNote(?string $refund_note): static
    {
        $this->refund_note = $refund_note;

        return $this;
    }

    public function getBasket(): ?Basket
    {
        return $this->Basket;
    }

    public function setBasket(?Basket $Basket): static
    {
        $this->Basket = $Basket;

        return $this;
    }


    public function getBeneficiaryName(): ?string
{
    return $this->beneficiaryName;
}

public function setBeneficiaryName(?string $beneficiaryName): self
{
    $this->beneficiaryName = $beneficiaryName;

    return $this;
}

public function getBeneficiaryAddress(): ?string
{
    return $this->beneficiaryAddress;
}

public function setBeneficiaryAddress(?string $beneficiaryAddress): self
{
    $this->beneficiaryAddress = $beneficiaryAddress;

    return $this;
}

public function getPhone(): ?string
{
    return $this->phone;
}

public function setPhone(?string $phone): self
{
    $this->phone = $phone;

    return $this;
}

public function getCityBeneficiary(): ?City
{
    return $this->city_beneficiary;
}

public function setCityBeneficiary(?City $city_beneficiary): static
{
    $this->city_beneficiary = $city_beneficiary;

    return $this;
}

public function getBeneficiaryEmail(): ?string
{
    return $this->beneficiary_email;
}

public function setBeneficiaryEmail(string $beneficiary_email): static
{
    $this->beneficiary_email = $beneficiary_email;

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
        $this->basketProducts[] = $basketProduct;
        $basketProduct->setOrderC($this); // Très important pour garder la relation synchronisée
    }

    return $this;
}

public function removeBasketProduct(BasketProduct $basketProduct): static
{
    if ($this->basketProducts->removeElement($basketProduct)) {
        // Set the owning side to null (unless already changed)
        if ($basketProduct->getOrderC() === $this) {
            $basketProduct->setOrderC(null);
        }
    }

    return $this;
}

public function getBasketProductsList(): string
{
    return implode(';  ', $this->basketProducts->map(function ($bp) {
        return sprintf('%s x %d', $bp->getProduct()?->getName() ?? 'Inconnu', $bp->getQuantity());
    })->toArray());
}

public function getRefundAmount(): ?string
{
    return $this->refund_amount;
}

public function setRefundAmount(?string $refund_amount): static
{
    $this->refund_amount = $refund_amount;

    return $this;
}

public function getAmountFinal(): ?string
{
    return $this->amount_final;
}

public function setAmountFinal(?string $amount_final): static
{
    $this->amount_final = $amount_final;

    return $this;
}



}
