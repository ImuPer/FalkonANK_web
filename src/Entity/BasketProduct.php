<?php

namespace App\Entity;

use App\Repository\BasketProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BasketProductRepository::class)]
class BasketProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'basketProducts')]
    private ?Basket $basket = null;

    #[ORM\ManyToOne(inversedBy: 'basketProducts')]
    private ?Product $product = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?bool $payment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_pay = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $payment_method = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $payment_status = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'basketProducts')]
    #[ORM\JoinColumn(name: 'order_c_id', referencedColumnName: 'id', nullable: true)]  // Le nom correct dans la base de données
    private ?Order $orderC = null;

    // #[ORM\ManyToOne(inversedBy: 'basketProducts')]
    // private ?Order $orderC = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBasket(): ?Basket
    {
        return $this->basket;
    }

    public function setBasket(?Basket $basket): static
    {
        $this->basket = $basket;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function isPayment(): ?bool
    {
        return $this->payment;
    }

    public function setPayment(bool $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function getDatePay(): ?\DateTimeInterface
    {
        return $this->date_pay;
    }

    public function setDatePay(?\DateTimeInterface $date_pay): static
    {
        $this->date_pay = $date_pay;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(?string $payment_method): static
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->payment_status;
    }

    public function setPaymentStatus(?string $payment_status): static
    {
        $this->payment_status = $payment_status;

        return $this;
    }

    public function getOrderC(): ?Order
    {
        return $this->orderC;
    }

    public function setOrderC(?Order $orderC): static
    {
        $this->orderC = $orderC;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->quantity;
    }

   
    public function getTotal(): float
    {
        if (!$this->product) {
            return 0.0;
        }
        return $this->product->getPrice() * $this->quantity;
    }
}
