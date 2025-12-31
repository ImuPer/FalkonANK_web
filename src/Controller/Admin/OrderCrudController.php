<?php

namespace App\Controller\Admin;

use App\Entity\Basket;
use App\Entity\BasketProduct;
use App\Entity\Order;
use App\Repository\UserRepository;
// use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;

class OrderCrudController extends AbstractCrudController
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
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Encomendas')
            ->setEntityLabelInSingular('Encomenda')
            ->setPageTitle(Crud::PAGE_INDEX, 'Encomendas')
            ->setPageTitle(Crud::PAGE_EDIT, 'Finalizar encomenda')
            ->setPageTitle(Crud::PAGE_DETAIL, fn(Order $order) => (string) $order->getRef());
    }

    public function configureAssets(Assets $assets): Assets
    {
        return Assets::new()
            ->addCssFile('build/app.css')  // chemin relatif au dossier public/build
            ->addJsFile('build/refund_toggle.js');
    }

    // Méthode qui surchargera la requête pour filtrer les commandes selon l'utilisateuruse Doctrine\ORM\QueryBuilder;

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $user = $this->security->getUser();
        $qb = $this->doctrine->getRepository(Order::class)->createQueryBuilder('o');

        // ===============================
        // ADMIN
        // ===============================
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $qb->orderBy('o.order_date', 'DESC');

            // 🔴 Filtrage spécial "Reembolso"
            if ($entityDto->getFqcn() === Order::class) {
                // ⚠️ Optionnel : seulement si tu viens du menu "Reembolso"
                $request = $this->requestStack->getCurrentRequest();
                if ($request && $request->query->get('filter') === 'reembolso') {
                    $qb->andWhere('o.order_status = :status')
                        ->andWhere('o.refund_status = :refund')
                        ->setParameter('status', 'Reembolso')
                        ->setParameter('refund', 'Em curso');
                }
            }

            return $qb;
        }

        // ===============================
        // MERCHANT
        // ===============================
        if (in_array('ROLE_MERCHANT', $user->getRoles(), true)) {
            $qb->join('o.basketProducts', 'bp')
                ->join('bp.product', 'p')
                ->join('p.shop', 's')
                ->andWhere('s.user = :merchant')
                ->setParameter('merchant', $user)
                ->orderBy('o.order_date', 'DESC');

            // 🔹 Vérifie le filtre dans l'URL
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $filter = $request->query->get('filter');
                if ($filter === 'Em processamento') {
                    $qb->andWhere('o.order_status = :status')
                        ->setParameter('status', 'Em processamento');
                }elseif ($filter === 'reembolso') {
                    $qb->andWhere('o.order_status = :status')
                        ->andWhere('o.refund_status = :refund')
                        ->setParameter('status', 'Reembolso')
                        ->setParameter('refund', 'Em curso');
                } elseif ($filter === 'Reembolsado') {
                    $qb->andWhere('o.refund_status = :refund')
                        ->setParameter('refund', 'Reembolsado');
                }
            }

            return $qb;
        }


        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }


    // Facultatif : Si tu veux ajouter des champs dans l'index
    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser();
         $orderStatus = ([
            $this->translator->trans('order.status.processing') => 'Em processamento',
            $this->translator->trans('order.status.refund') => 'Reembolso',
            $this->translator->trans('order.status.completed') => 'Entregue e finalizado',
        ]);
        $refundStatus = ([
            'Em curso' => 'Em curso',
            // 'Reembolsado' => 'Reembolsado',
        ]);    

        if (in_array("ROLE_ADMIN", $user->getRoles())) {
            $orderStatus = ([
                $this->translator->trans('order.status.processing') => 'Em processamento',
                $this->translator->trans('order.status.refund') => 'Reembolso',
                // $this->translator->trans('order.status.completed') => 'Entregue e finalizado',
            ]);
            $refundStatus = ([
                'Em curso' => 'Em curso',
                'Reembolsado' => 'Reembolsado',
            ]);
        }

        // Champ virtuel pour la classe CSS
        return [

            // Field::new('statusClass')->onlyOnIndex()
            //     ->formatValue(function ($value, $entity) {
            //         /** @var Order $entity */
            //         if ($entity->getOrderStatus() === 'Em processamento' || $entity->getMerchantSecretCode() === null) {
            //             return 'text-danger'; // Classe CSS pour le texte rouge
            //         }
            //         return '';
            //     })
            //     ->setCssClass('hidden'), // on ne veut pas afficher ce champ


            // ===== INDEX : affichage coloré =====
            TextField::new('orderStatus', $this->translator->trans('order.field.status'))
                ->onlyOnIndex()
                ->formatValue(function ($value, Order $order) {

                    return match ($order->getOrderStatus()) {
                        'Em processamento' => '<span class="text-danger">' . $value . '</span>',
                        'Entregue e finalizado' => '<span class="text-success">' . $value . '</span>',
                        'Reembolso' => '<span class="text-warning">' . $value . '</span>',
                        default => $value,
                    };
                })
                ->renderAsHtml(),


            Field::new('cssClass')->onlyOnIndex()
                ->formatValue(function ($value, $entity) {
                    if ($entity->getMerchantSecretCode() !== null && $entity->getOrderStatus() === 'Entregue e finalizado') {
                        return 'row-entregue-finalizado';
                    }
                    return '';
                })
                ->setCssClass('hidden')
                ->hideOnIndex(),

            TextField::new('ref', $this->translator->trans('order.field.reference'))
                ->setFormTypeOption('attr', ['readonly' => true]),

            TextField::new('basket.user.lastName', $this->translator->trans('order.field.customer'))->hideOnIndex(),
            TextField::new('basket.user.email', $this->translator->trans('order.field.customer_email'))->hideOnIndex()
            ->setFormTypeOption('attr', ['readonly' => true, 'id' => 'v']),

            DateTimeField::new('orderDate', $this->translator->trans('orders.date'))
                ->hideOnForm()
                ->formatValue(function ($value) {
                    return $value ? $value->format('d/m/Y') : '';
                }),

            MoneyField::new('totalAmount', 'order.field.total')
                ->setFormTypeOption('attr', ['readonly' => true, 'id' => 'Order_totalAmount'])
                ->setCurrency('CVE')
                ->setHelp('order.help.currency_cve'),

            MoneyField::new('amountFinal', 'order.field.final_price2')
                ->setFormTypeOption('attr', ['readonly' => true, 'id' => 'Order_totalAmount'])
                ->setCurrency('CVE')
                ->hideOnIndex(),

            MoneyField::new('totalShippingCost', 'order.field.total_shipping')
                ->setCurrency('CVE')
                ->setFormTypeOption('attr', ['readonly' => true])
                ->setColumns(4)
                ->hideOnIndex(),

            TextField::new('deliveryMethods', 'order.field.delivery_method')
                ->setFormTypeOption('mapped', false) // important !
                ->setFormTypeOption('attr', ['readonly' => true])
                ->setColumns(4)
                ->hideOnIndex(),

            MoneyField::new('finalAmountWithDelivery', 'order.field.final_with_delivery')
                ->setCurrency('CVE')
                ->setFormTypeOption('attr', ['readonly' => true])
                ->setColumns(4)
                ->hideOnIndex(),

            TextEditorField::new('beneficiaryName', $this->translator->trans('order.field.beneficiary_name'))->hideOnForm(),
            TextEditorField::new('beneficiary_email', 'Email')->hideOnForm(),
            TextEditorField::new('phone', )->hideOnForm(),
            TextEditorField::new('beneficiary_address', $this->translator->trans('order.field.beneficiary_address'))->hideOnForm(),
            TextEditorField::new('basketProductsList', $this->translator->trans('order.field.items'))->hideOnForm(),
            TextareaField::new('basketProductsList', $this->translator->trans('order.field.items'))->hideOnIndex()
                // ->setFormTypeOption('mapped', false)
                ->setFormTypeOption('attr', ['readonly' => true, 'id' => 'Order_basketProductsList']),

            // ===== FORM (EDIT / NEW) : ChoiceField normal =====
            ChoiceField::new('orderStatus', $this->translator->trans('order.field.status'))
                ->onlyOnForms()
                ->setChoices($orderStatus)
                ->setRequired(true),


            TextareaField::new('internal_note', $this->translator->trans('order.field.internal_note'))
                // ->hideOnIndex()
                  ->setFormTypeOption('attr', ['class' => 'internal-note'])
                ->setFormTypeOption('attr', ['id' => 'Order_internal_note']),

            TextField::new('merchantSecretCode', $this->translator->trans('order.field.merchant_secret_code'))
                ->setHelp($this->translator->trans('order.help.merchant_secret_code'))
                ->setRequired(true)
                ->setFormTypeOptions([
                    'required' => true,
                    'constraints' => [],
                    'mapped' => true,
                ])
                ->addFormTheme('@EasyAdmin/crud/form_theme.html.twig')
                ->setFormTypeOption('attr', ['autocomplete' => 'off', 'id' => 'Order_merchantSecretCode']),

            // TextEditorField::new('customer_note', $this->translator->trans('order.field.customer_note2'))
            //     ->hideOnForm(),

            BooleanField::new('refund', $this->translator->trans('order.field.refund'))
                ->hideOnIndex()
                ->setFormTypeOption('attr', ['id' => 'Order_refund']),

            MoneyField::new('refund_amount', $this->translator->trans('order.field.refund_amount'))
                ->setCurrency('CVE')
                ->hideOnIndex()
                ->setHelp($this->translator->trans('order.help.currency_cve'))
                ->setFormTypeOption('attr', ['id' => 'Order_refund_amount']),

            ChoiceField::new('refund_status', $this->translator->trans('order.field.refund_status'))
                ->setChoices($refundStatus)
                ->hideOnIndex()
                ->setFormTypeOption('attr', ['id' => 'Order_refund_status']),

            TextareaField::new('refund_note', $this->translator->trans('order.field.refund_note'))
                ->hideOnIndex()
                ->setFormTypeOption('attr', ['id' => 'Order_refund_note']),

            TextareaField::new('customer_note', $this->translator->trans('order.field.customer_note2'))
                ->hideOnIndex()->setFormTypeOption('disabled', true),
        ];

    }



    public function configureActions(Actions $actions): Actions
{
    $user = $this->security->getUser();

    // Action existante pour voir le reçu
    $verRecibo = Action::new('verRecibo', $this->translator->trans('order.action.view_receipt'))
        ->linkToRoute('recibo_show', fn(Order $order) => ['id' => $order->getId()])
        ->displayIf(fn(Order $order) => $order->getOrderStatus() !== 'Em processamento')
        ->setCssClass('btn btn-success');

    $actions
        ->disable(Action::DELETE)
        ->disable(Action::NEW)
        ->add(Crud::PAGE_INDEX, $verRecibo)
        ->add(Crud::PAGE_DETAIL, $verRecibo);

    // Désactiver Edit sur INDEX et DETAIL pour merchants si refund_status = Reembolsado
    if (in_array('ROLE_MERCHANT', $user->getRoles(), true)) {
        $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action) => $action->displayIf(fn(Order $order) => $order->getRefundStatus() !== 'Reembolsado'))
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $action) => $action->displayIf(fn(Order $order) => $order->getRefundStatus() !== 'Reembolsado'));
    }

    return $actions;
}




    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Order) {
            return;
        }

        
        $clientName = $entityInstance->getBasket()->getUser()->getFirstName() . " " . $entityInstance->getBasket()->getUser()->getLastName();
        $ref_order = $entityInstance->getRef();
        $clientEmail = $entityInstance->getBasket()->getUser()->getEmail();
        $admins = $this->userRepository->findAdmins();
        $adminEmails = array_map(
            fn($admin) => $admin->getEmail(),
            $admins
        );


        $status = $entityInstance->getOrderStatus();
        $internalNote = $entityInstance->getInternalNote();

        // ===============================
        // ✅ Commande livrée
        // ===============================
        if ($status === 'Entregue e finalizado') {
            $entityInstance->setRefund(false);
            $entityInstance->setRefundAmount(null);
            $entityInstance->setRefundStatus('');

            if (empty($internalNote)) {
                $entityInstance->setInternalNote(
                    $this->translator->trans('order.note.delivered')
                );
            }

            // ------------envoyer email au client----------------------------------------------------------------
            $recapContent = <<<EOD
            <html>
                <body style="font-family: Arial, sans-serif; font-size: 16px; color: #333;">         
                    <p>Olá, <strong>{$clientName}</strong>,</p>
                    <p>A sua encomenda referencia: <strong>{$ref_order}</strong> foi entregue e finalizado. Obrigado e volta sempre.</p>
                    
                    <p style="margin-top: 30px;">
                        Atenciosamente,<br>
                        <strong>FALKON-ANK Alimentason</strong>
                    </p>
                </body>
            </html>
            EOD;
            $emailClient = (new Email())
                ->from(new Address('no-reply@falkonclick.com', 'FalkonANK Alimentason'))
                ->to($clientEmail)
                ->subject('Novo encomenda')
                ->html($recapContent);
            // envoier l'email
            $this->mailer->send($emailClient);
        }

        // ===============================
        // ✅ Remboursement
        // ===============================
        if ($status === 'Reembolso') {
            // $entityInstance->setInternalNote($this->translator->trans('order.note.refund'));
            if (empty($entityInstance->getInternalNote())){
                $this->addFlash(
                    'danger',
                    $this->translator->trans('order.flash.note.refund_required')
                );
                $request = $this->requestStack->getCurrentRequest();
                $referer = $request->headers->get('referer')
                    ?? $this->router->generate('admin');

                (new RedirectResponse($referer))->send();
                exit;
            }

            // 🔴 Validation obligatoire
            if (empty($entityInstance->getRefundAmount()) || empty($entityInstance->getRefundStatus())) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('order.flash.refund_required')
                );

                $request = $this->requestStack->getCurrentRequest();
                $referer = $request->headers->get('referer')
                    ?? $this->router->generate('admin');

                (new RedirectResponse($referer))->send();
                exit;
            }

            // 🟡 Remboursement en cours
            if (empty($entityInstance->getRefundNote())) {
                $entityInstance->setRefundNote(
                    $this->translator->trans('order.note.refund_pending')
                );

                // ------------envoyer email au admin----------------------------------------------------------------
                $recapContent = <<<EOD
                <html>
                    <body style="font-family: Arial, sans-serif; font-size: 16px; color: #333;">         
                        <p>Olá Adim, <strong>{$clientName}</strong>,</p>
                        <p>Uma encomenda a ser reembolsada, referencia: <strong>{$ref_order}</p>
                        
                        <p style="margin-top: 30px;">
                            Atenciosamente,<br>
                            <strong>FALKON-ANK Alimentason</strong>
                        </p>
                    </body>
                </html>
                EOD;
                $emailAdmin = (new Email())
                    ->from(new Address('no-reply@falkonclick.com', 'FalkonANK Alimentason'))
                    ->to(...$adminEmails)
                    ->subject('Novo Reembolso')
                    ->html($recapContent);
                // envoier l'email
                $this->mailer->send($emailAdmin);
            }

            // 🟢 Remboursement terminé
            if ($entityInstance->getRefundStatus() === 'Reembolsado') {
                $amountCVe = $entityInstance->getRefundAmount() / 100;
                $entityInstance->setRefundNote(
                    $this->translator->trans(
                        'order.note.refund_completed',
                        [
                            '%date%' => (new \DateTime())->format('d/m/Y H:i'),
                            '%amountcve%' => $amountCVe,
                        ]
                    )
                );
                $entityInstance->setOrderStatus("Reembolsado");

                // ------------envoyer email au client----------------------------------------------------------------
                $recapContent = <<<EOD
                <html>
                    <body style="font-family: Arial, sans-serif; font-size: 16px; color: #333;">         
                        <p>Olá, <strong>{$clientName}</strong>,</p>
                        <p>A sua encomenda referencia: <strong>{$ref_order}</strong> foi anulada, e reembolsada.</p>
                        <p>{$this->translator->trans('order.note.refund_completed', ['%date%' => (new \DateTime())->format('d/m/Y H:i'), '%amountcve%' => $amountCVe,])}</p>
                        
                        <p style="margin-top: 30px;">
                            Atenciosamente,<br>
                            <strong>FALKON-ANK Alimentason</strong>
                        </p>
                    </body>
                </html>
                EOD;
                $emailClient = (new Email())
                    ->from(new Address('no-reply@falkonclick.com', 'FalkonANK Alimentason'))
                    ->to($clientEmail)
                    ->subject('Encomenda Reembolsada')
                    ->html($recapContent);
                // envoier l'email
                $this->mailer->send($emailClient);
            }
            $entityInstance->setRefund(true);
        }

        // ===============================
        // ✅ Vérification o montante, ou o estado, do Reemboso et o estado da encomenda
        // ===============================
        if (
            (!empty($entityInstance->getRefundAmount()) || !empty($entityInstance->getRefundStatus())) 
            && ($status === 'Entregue e finalizado' || $status === 'Em processamento')
        ) {
            $this->addFlash(
                'danger',
                $this->translator->trans(
                    "Verifica o Montante ou Estado do Reembolso, se corresponde ao 'Estado da Encomenda'."
                )
            );
            $request = $this->requestStack->getCurrentRequest();
            $referer = $request->headers->get('referer')
                ?? $this->router->generate('admin');
            (new RedirectResponse($referer))->send();
            exit;
        }


        // ===============================
        // ✅ Vérification code secret (Merchant)
        // ===============================
        if (in_array('ROLE_MERCHANT', $this->security->getUser()->getRoles(), true)) {
            $merchantCode = $entityInstance->getMerchantSecretCode();
            $autoCode = $entityInstance->getAutoSecretCode();

            if ($merchantCode === null || $merchantCode !== $autoCode) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('order.flash.merchant_invalid_code')
                );

                $request = $this->requestStack->getCurrentRequest();
                $referer = $request->headers->get('referer')
                    ?? $this->router->generate('admin');

                (new RedirectResponse($referer))->send();
                exit;
            }

            $this->addFlash(
                'success',
                $this->translator->trans('order.flash.merchant_success')
            );
        }

        parent::updateEntity($entityManager, $entityInstance);
    }



    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return parent::createEditFormBuilder($entityDto, $formOptions, $context);
    }



}

