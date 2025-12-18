<?php

namespace App\Controller\Admin;

use App\Entity\Basket;
use App\Entity\BasketProduct;
use App\Entity\Order;
use Doctrine\ORM\Query\FilterCollection;
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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class OrderCrudController extends AbstractCrudController
{
    private Security $security;

    private ManagerRegistry $doctrine;
    private RequestStack $requestStack;
    private RouterInterface $router;

    public function __construct(Security $security, ManagerRegistry $doctrine, RequestStack $requestStack, RouterInterface $router)
    {
        $this->security = $security;
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
        $this->router = $router;
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


    // Méthode qui surchargera la requête pour filtrer les commandes selon l'utilisateur
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, \EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection $filters): QueryBuilder
    {
        $user = $this->security->getUser();
        $qb = $this->doctrine->getRepository(Order::class)->createQueryBuilder('o');

        if (in_array("ROLE_ADMIN", $user->getRoles())) {
            $qb->orderBy('o.order_date', 'DESC');
            return $qb;
        } elseif (in_array("ROLE_MERCHANT", $user->getRoles())) {

            // Joindre la table BasketProduct pour obtenir les produits
            $qb->join('o.basketProducts', 'bp')
                ->join('bp.product', 'p')
                ->join('p.shop', 's')
                ->where('s.user = :merchant')
                ->setParameter('merchant', $user)
                ->orderBy('o.order_date', 'DESC'); // Tri par date décroissante

            return $qb;
        }

        // Si ce n'est pas un "MERCHANT", renvoie simplement la requête de base
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }


    // Facultatif : Si tu veux ajouter des champs dans l'index
    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser();
        $refundStatus = ([
            'Em curso' => 'Em curso',
            // 'Reembolsado' => 'Reembolsado',
        ]);
        if (in_array("ROLE_ADMIN", $user->getRoles())) {
            $refundStatus = ([
                'Em curso' => 'Em curso',
                'Reembolsado' => 'Reembolsado',
            ]);
        }
        // Vérifier les rôles de l'utilisateur
        // if (in_array("ROLE_ADMIN", $user->getRoles())) {
        //     // Ajouter les champs spécifiques pour les utilisateurs avec les rôles ADMIN ou USER
        //     return [
        //         MoneyField::new('total_amount', 'Total')->setRequired(true)->setCurrency('CVE')->hideOnForm(),
        //MoneyField::new('amountFinal', 'Total Final')->setFormTypeOption('attr', ['readonly' => true])->setCurrency('CVE')->setHelp('👉 CVE (escudos de Cabo Verde)'),
        //         TextField::new('basketProductsList', 'Todos artigos'),

        //         TextField::new('order_status', 'Estatus'),
        //         TextField::new('beneficiary_name', 'Beneficiario')->hideOnForm(),
        //         TextField::new('beneficiary_email', 'Email do Beneficiario')->hideOnForm(),
        //         TextField::new('beneficiary_address', 'adereço do Beneficiario')->hideOnForm(),
        //         TextareaField::new('internal_note', 'Notificação da loja')->hideOnForm(),
        //         TextField::new('customer_note', 'Comentário do cliente')->hideOnForm(),
        //         // Transfert vers BasketProduct
        //         BooleanField::new('refund', 'Reembolso')->hideOnForm(),
        //         TextField::new('refund_status', 'Status do reembolso')->hideOnForm(),
        //         TextareaField::new('refund_note', 'Notificaçõ do reembolso')->hideOnForm(),
        //     ];
        // } else {


        // Champ virtuel pour la classe CSS

        return [

            Field::new('cssClass')->onlyOnIndex()
                ->formatValue(function ($value, $entity) {
                    if ($entity->getMerchantSecretCode() !== null && $entity->getOrderStatus() === 'Entregue e finalizado') {
                        return 'row-entregue-finalizado';
                    }
                    return '';
                })
                ->setCssClass('hidden')
                ->hideOnIndex(),

            TextField::new('ref', 'Referencia')
                ->setFormTypeOption('attr', ['readonly' => true]),

            DateTimeField::new('orderDate', 'Data')
                ->hideOnForm()
                ->formatValue(function ($value) {
                    return $value ? $value->format('d/m/Y') : '';
                }),

            MoneyField::new('totalAmount', 'Total')
                ->setFormTypeOption('attr', ['readonly' => true, 'id' => 'Order_totalAmount'])
                ->setCurrency('CVE')
                ->setHelp('👉 CVE (escudos de Cabo Verde)'),
            MoneyField::new('amountFinal', 'Preço Final (Total + commição plataforma)')
                ->setFormTypeOption('attr', ['readonly' => true, 'id' => 'Order_totalAmount'])
                ->setCurrency('CVE')
                ->hideOnIndex()
                ->setHelp('👉 CVE (escudos de Cabo Verde)'),

            TextEditorField::new('beneficiary_name', 'Beneficiario')->hideOnForm(),
            TextEditorField::new('beneficiary_email', 'Email')->hideOnForm(),
            TextEditorField::new('beneficiary_address', 'adereço')->hideOnForm(),

            TextField::new('basketProductsList', 'Artigos')
                ->setFormTypeOption('attr', ['readonly' => true, 'id' => 'Order_basketProductsList']),

            ChoiceField::new('orderStatus', 'Estado')
                ->setChoices([
                    'Em processamento' =>'Em processamento',
                    'Reenbolso' => 'Reembolso',
                    'Entregue e finalizado' => 'Entregue e finalizado',
                ])
                ->setRequired(true)
                ->setFormTypeOption('attr', ['id' => 'Order_orderStatus']),

            TextareaField::new('internal_note', 'Notificação da loja')
                ->hideOnIndex()
                ->setFormTypeOption('attr', ['id' => 'Order_internal_note']),

            TextField::new('merchantSecretCode', 'Código Secreto')
                ->setHelp('👉 Obrigatório para finalizar a encomenda.')
                ->setRequired(true)
                ->setFormTypeOptions([
                    'required' => true,
                    'constraints' => [],
                    'mapped' => true,
                ])
                ->addFormTheme('@EasyAdmin/crud/form_theme.html.twig')
                ->setFormTypeOption('attr', ['autocomplete' => 'off', 'id' => 'Order_merchantSecretCode']),

            TextEditorField::new('customer_note', 'Comentário do cliente')
                ->hideOnForm(),

            BooleanField::new('refund', 'Reembolso')
                ->hideOnIndex()
                ->setFormTypeOption('attr', ['id' => 'Order_refund']),

            MoneyField::new('refund_amount', 'Montante')
                ->setCurrency('CVE')
                ->hideOnIndex()
                ->setHelp('👉 CVE (escudos de Cabo Verde)')
                ->setFormTypeOption('attr', ['id' => 'Order_refund_amount']),

            ChoiceField::new('refund_status', 'Estado do reembolso')
                ->setChoices($refundStatus)
                ->hideOnIndex()
                ->setFormTypeOption('attr', ['id' => 'Order_refund_status']),

            TextareaField::new('refund_note', 'Notificação - reembolso')
                ->hideOnIndex()
                ->setFormTypeOption('attr', ['id' => 'Order_refund_note']),
        ];

        // }
    }



    public function configureActions(Actions $actions): Actions
    {
        $verRecibo = Action::new('verRecibo', 'Ver Recibo')
        ->linkToRoute('recibo_show', function (Order $order) {
            return ['id' => $order->getId()];
        })
        ->displayIf(function (Order $order) {
            return $order->getOrderStatus() !== 'Em processamento';
        })
        ->setCssClass('btn btn-success');

    return $actions
        ->disable(Action::DELETE)
        ->disable(Action::NEW)
        ->add(Crud::PAGE_INDEX, $verRecibo)
        ->add(Crud::PAGE_DETAIL, $verRecibo);
        

    }


    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Order) {
            return;
        }

        $status = $entityInstance->getOrderStatus();
        $internalNote = $entityInstance->getInternalNote();

        // Si livré, on préremplit la note s'il n'y en a pas
        if ($status === 'Entregue e finalizado') {
            $entityInstance->setRefund(false);
            $entityInstance->setRefundAmount(null);
            $entityInstance->setRefundStatus('');
            if (empty($internalNote)) {
                $entityInstance->setInternalNote("Todos os produtos foram entregues com sucesso, volte sempre. Nossa Equipa agradece!");
             }
        }

        // Si remboursement
        if ($status === 'Reembolso') {
            $entityInstance->setRefund(true);
            $entityInstance->setInternalNote('A encomenda foi cancelada.');

            // Validation obligatoire
            if (empty($entityInstance->getRefundAmount()) || empty($entityInstance->getRefundStatus())) {
                $this->addFlash('danger', '❌ Reembolso: O montante e o estado do reembolso são obrigatórios.');

                $request = $this->requestStack->getCurrentRequest();
                $referer = $request->headers->get('referer') ?? $this->router->generate('admin');

                (new RedirectResponse($referer))->send();
                exit;
            }
            if (empty($entityInstance->getRefundNote())) {
                $entityInstance->setRefundNote('o reenbolso esta en courso.');
            }

            if ($entityInstance->getRefundStatus() === "Reembolsado") {
                $entityInstance->setRefundNote(
                    'O reembolso foi concluído em ' . (new \DateTime())->format('d/m/Y H:i') .
                    '. O valor estará disponível na sua conta entre três e oito dias úteis, conforme os prazos do seu banco.'
                );
            }

        }

        // Vérifie le code secret pour les merchants
        if (in_array('ROLE_MERCHANT', $this->security->getUser()->getRoles())) {
            $merchantCode = $entityInstance->getMerchantSecretCode();
            $autoCode = $entityInstance->getAutoSecretCode();

            if ($merchantCode === null || $merchantCode !== $autoCode) {
                $this->addFlash('danger', '❌ O código secreto está incorreto. Por favor, insira o código correto.');

                $request = $this->requestStack->getCurrentRequest();
                $referer = $request->headers->get('referer') ?? $this->router->generate('admin');

                (new RedirectResponse($referer))->send();
                exit;
            } else {
                $this->addFlash('success', 'Registrado com sucesso');
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }





    // public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
//     {
//         if (!$entityInstance instanceof Order) {
//             return;
//         }

    //         if (in_array('ROLE_MERCHANT', $this->security->getUser()->getRoles())) {
//             $merchantCode = $entityInstance->getMerchantSecretCode();
//             $autoCode = $entityInstance->getAutoSecretCode();

    //             if ($merchantCode === null || $merchaantCode !== $autoCode) {
//                 $this->addFlash('danger', '❌ O código secreto está incorreto. Por favor, insira o código correto.');

    //                 // Redirige proprement vers la même page
//                 $request = $this->requestStack->getCurrentRequest();
//                 $referer = $request->headers->get('referer') ?? $this->router->generate('admin');

    //                 // Envoi d'une réponse de redirection
//                 (new RedirectResponse($referer))->send();
//                 exit;
//             } else {
//                 $this->addFlash('success', ' Registrado com sucesso');

    //             }
//         }

    //         parent::updateEntity($entityManager, $entityInstance);
//     }




    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return parent::createEditFormBuilder($entityDto, $formOptions, $context);
    }



}

