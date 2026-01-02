<?php

namespace App\Controller\Admin;

use App\Entity\Delivery;
use App\Entity\Carrier;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class DeliveryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Delivery::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),

            TextField::new('fullAddress', 'delivery.address')
                ->setRequired(true)
                ->setFormTypeOption('disabled', true)
                ->setColumns(4),
            
            TextField::new('trackingNumber', 'delivery.tracking_number')
                ->setRequired(true)
                ->setFormTypeOption('disabled', true)
                ->setColumns(4),

            MoneyField::new('shippingCost', 'delivery.shipping_cost')
                ->setNumDecimals(2)
                ->setCurrency('CVE')
                ->setFormTypeOption('disabled', true)
                ->setColumns(4),

            TextField::new('deliveryMethod', 'delivery.method')
                ->setFormTypeOption('disabled', true)
                ->setColumns(4),

            ChoiceField::new('deliveryStatus', 'delivery.status.title')
                ->setChoices([
                    'delivery.status.pending' => 'pending',
                    'delivery.status.processing' => 'processing',
                    'delivery.status.shipped' => 'shipped',
                    'delivery.status.in_transit' => 'in_transit',
                    'delivery.status.out_for_delivery' => 'out_for_delivery',
                    'delivery.status.delivered' => 'delivered',
                    'delivery.status.failed' => 'failed',
                    'delivery.status.cancelled' => 'cancelled',
                    'delivery.status.returned' => 'returned',
                ])
                ->renderAsNativeWidget(),

            AssociationField::new('carrier', 'delivery.carrier')
                ->setRequired(false)
                ->setFormTypeOption('choice_label', 'name'),
            
            DateTimeField::new('shipmentDate', 'delivery.shipment_date')
                ->setFormat('d/M/Y H:mm')
                ->setRequired(false),

            DateTimeField::new('estimatedDeliveryDate', 'delivery.estimated_delivery')
                ->setFormat('d/M/Y H:mm')
                ->setRequired(false),
        ];
    }
}
