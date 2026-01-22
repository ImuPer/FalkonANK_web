<?php

namespace App\Controller\Admin;

use App\Entity\Delivery;
use App\Entity\Carrier;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeliveryCrudController extends AbstractCrudController
{
    private Security $security;

    private ManagerRegistry $doctrine;
    private RequestStack $requestStack;
    private RouterInterface $router;

    public function __construct(private UserRepository $userRepository, Security $security, ManagerRegistry $doctrine, RequestStack $requestStack, RouterInterface $router, TranslatorInterface $translator, MailerInterface $mailer)
    {
        $this->security = $security;
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->translator = $translator;
        $this->mailer = $mailer;
    }
    public static function getEntityFqcn(): string
    {
        return Delivery::class;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var Delivery|null $delivery */
        $delivery = $this->getContext()?->getEntity()?->getInstance();

        if ($delivery instanceof Delivery && null === $delivery->getShipmentDate()) {
            $this->addFlash(
                'danger',
                'La commande doit être finalisée par le merchant.'
            );
        }
        return [
            IdField::new('id')->onlyOnIndex(),

            TextField::new('fullAddress', 'delivery.address')
                ->setRequired(true)
                ->setColumns(4)
            ,

            TextField::new('orderCustomer.beneficiaryAddress', 'checkout.address')
                ->setFormTypeOption('disabled', true)
                ->hideOnIndex()
                ->setColumns(4)
            ,

            TextField::new('trackingNumber', 'delivery.tracking_number')
                ->setRequired(true)
                ->setFormTypeOption('disabled', true)
                ->setColumns(4)
            ,

            MoneyField::new('shippingCost', 'delivery.shipping_cost')
                ->setNumDecimals(2)
                ->setCurrency('CVE')
                ->setFormTypeOption('disabled', true)
                ->setColumns(4)
            ,

            TextField::new('deliveryMethod', 'delivery.method')
                ->setFormTypeOption('disabled', true)
                ->setColumns(4)
            ,

            DateTimeField::new('shipmentDate', 'delivery.shipment_date')
                ->setFormat('d/M/Y H:mm')
                ->setFormTypeOption('disabled', true)
                ->setColumns(4)
            ,

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
                ->renderAsNativeWidget()
            ,

            AssociationField::new('carrier', 'delivery.carrier')
                ->setRequired(false)
                ->setFormTypeOption('choice_label', 'name')
            ,

            DateTimeField::new('estimatedDeliveryDate', 'delivery.estimated_delivery')
                ->setFormType(DateTimeType::class)
                ->setFormTypeOptions([
                    'widget' => 'single_text',
                    'html5' => true,
                ])
                ->setRequired(false),

            AssociationField::new('order_customer', 'order')
                ->hideOnForm(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Vérifier si l'entité est bien une livraison
        if (!$entityInstance instanceof Delivery) {
            return;
        }

        $orderCustomerAddress = $entityInstance->getOrderCustomer()?->getBeneficiaryAddress();
        $deliveryAddress = $entityInstance->getFullAddress(); // suppose que Delivery a getAddress()
        $shipmentDate = $entityInstance->getShipmentDate();

        // Comparaison des adresses
        if ($orderCustomerAddress !== $deliveryAddress) {
            // Ajouter un message de flash pour avertissement
            $this->addFlash('warning', sprintf(
                'Attention : L’adresse du bénéficiaire (%s) est différente de l’adresse de livraison (%s).',
                $orderCustomerAddress,
                $deliveryAddress
            ));

            //stopper la persistance pour que l'utilisateur confirme manuellement
            throw new \Exception('Adresse de livraison différente du bénéficiaire.');
        }

        if (empty($shipmentDate)) {
            // Ajouter un message de flash pour le commerçant doit d'abord finalizé le commande
            $this->addFlash('danger', "le commerçant doit d'abord finalizé le commande");
            $request = $this->requestStack->getCurrentRequest();
            $referer = $request->headers->get('referer')
                ?? $this->router->generate('admin');

            (new RedirectResponse($referer))->send();
            exit;
        }

        // Persister l'entité
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}
