<?php

namespace App\Entity;

use App\Repository\DeliveryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeliveryRepository::class)]
class Delivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deliveries')]
    private ?Order $order_customer = null;

    #[ORM\Column(length: 255)]
    private ?string $delivery_status = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $tracking_number = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $shipment_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $estimated_delivery_date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $shipping_cost = null;

    #[ORM\Column(length: 2550, nullable: true)]
    private ?string $full_address = null;

    #[ORM\ManyToOne(inversedBy: 'deliveries')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Carrier $carrier = null;

    #[ORM\Column(length: 255)]
    private ?string $delivery_method = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderCustomer(): ?Order
    {
        return $this->order_customer;
    }

    public function setOrderCustomer(?Order $order_customer): static
    {
        $this->order_customer = $order_customer;

        return $this;
    }

    public function getDeliveryStatus(): ?string
    {
        return $this->delivery_status;
    }

    public function setDeliveryStatus(string $delivery_status): static
    {
        $this->delivery_status = $delivery_status;

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->tracking_number;
    }

    public function setTrackingNumber(string $tracking_number): static
    {
        $this->tracking_number = $tracking_number;

        return $this;
    }

    public function getShipmentDate(): ?\DateTimeInterface
    {
        return $this->shipment_date;
    }

    public function setShipmentDate(\DateTimeInterface $shipment_date): static
    {
        $this->shipment_date = $shipment_date;

        return $this;
    }

    public function getEstimatedDeliveryDate(): ?\DateTimeInterface
    {
        return $this->estimated_delivery_date;
    }

    public function setEstimatedDeliveryDate(\DateTimeInterface $estimated_delivery_date): static
    {
        $this->estimated_delivery_date = $estimated_delivery_date;

        return $this;
    }

    public function getShippingCost(): ?string
    {
        return $this->shipping_cost;
    }

    public function setShippingCost(string $shipping_cost): static
    {
        $this->shipping_cost = $shipping_cost;

        return $this;
    }

    public function getFullAddress(): ?string
    {
        return $this->full_address;
    }

    public function setFullAddress(?string $full_address): static
    {
        $this->full_address = $full_address;

        return $this;
    }

    public function getCarrier(): ?Carrier
    {
        return $this->carrier;
    }

    public function setCarrier(?Carrier $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function getDeliveryMethod(): ?string
    {
        return $this->delivery_method;
    }

    public function setDeliveryMethod(string $delivery_method): static
    {
        $this->delivery_method = $delivery_method;

        return $this;
    }
}
