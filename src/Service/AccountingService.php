<?php

namespace App\Service;

use App\Entity\Merchant;
use App\Repository\BasketProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class AccountingService
{
    private EntityManagerInterface $em;
    private BasketProductRepository $basketProductRepository;

    public function __construct(EntityManagerInterface $em, BasketProductRepository $basketProductRepository)
    {
        $this->em = $em;
        $this->basketProductRepository = $basketProductRepository;
    }

    /**
     * Chiffre d'affaires mensuel pour un marchand (avec nom & email shop).
     */
    public function getMonthlyRevenu(Merchant $merchant): array
{
    $conn = $this->em->getConnection();

    $sql = "
        SELECT 
            DATE_FORMAT(o.order_date, '%Y-%m') AS month,
            SUM(bp.quantity * p.price) AS revenue,
            IFNULL(SUM(o.refund_amount), 0) AS refund_amount,  -- Ajout du montant du remboursement
            s.name AS shop_name,
            u.email AS shop_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE u.id = :merchantUserId
          AND o.order_status = 'Entregue e finalizado'
        GROUP BY month, s.id, u.email
        ORDER BY month DESC
    ";

    return $conn->executeQuery($sql, [
        'merchantUserId' => $merchant->getUser()->getId()
    ])->fetchAllAssociative();
}


    /**
     * Chiffre d'affaires par produit pour un marchand (avec nom & email shop).
     */
    public function getRevenueByProduct(Merchant $merchant): array
{
    $conn = $this->em->getConnection();

    $sql = "
        SELECT 
            p.name AS product,
            SUM(bp.quantity * p.price) AS revenue,
            IFNULL(SUM(o.refund_amount), 0) AS refund_amount,  -- Ajout du montant du remboursement
            s.name AS shop_name,
            u.email AS shop_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE u.id = :merchantUserId
          AND o.order_status = 'Entregue e finalizado'
        GROUP BY p.id, s.id, u.email
        ORDER BY revenue DESC
    ";

    return $conn->executeQuery($sql, [
        'merchantUserId' => $merchant->getUser()->getId()
    ])->fetchAllAssociative();
}


    /**
     * Chiffre d'affaires par catégorie pour un marchand (avec nom & email shop).
     */
    public function getRevenueByCategory(Merchant $merchant): array
{
    $conn = $this->em->getConnection();

    $sql = "
        SELECT 
            c.name AS category,
            SUM(bp.quantity * p.price) AS revenue,
            IFNULL(SUM(o.refund_amount), 0) AS refund_amount,  -- Ajout du montant du remboursement
            s.name AS shop_name,
            u.email AS shop_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN category c ON p.category_id = c.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE u.id = :merchantUserId
          AND o.order_status = 'Entregue e finalizado'
        GROUP BY c.id, s.id, u.email
        ORDER BY revenue DESC
    ";

    return $conn->executeQuery($sql, [
        'merchantUserId' => $merchant->getUser()->getId()
    ])->fetchAllAssociative();
}


    // -------------------- ADMIN (GLOBAL) --------------------

    /**
     * Chiffre d'affaires mensuel global (avec nom & email shop).
     */
//    public function getGlobalMonthlyRevenue(): array
// {
//     $conn = $this->em->getConnection();

//     $sql = "
//         SELECT 
//             DATE_FORMAT(o.order_date, '%Y-%m') AS month,
//             SUM(bp.quantity * p.price) AS revenue,
//             IFNULL(SUM(o.refund_amount), 0) AS refund_amount,  -- Ajout du montant du remboursement
//             s.name AS shop_name,
//             u.email AS shop_email
//         FROM basket_product bp
//         JOIN product p ON bp.product_id = p.id
//         JOIN shop s ON p.shop_id = s.id
//         JOIN `user` u ON s.user_id = u.id
//         JOIN `order` o ON bp.order_c_id = o.id
//         WHERE o.order_status = 'Entregue e finalizado'
//         GROUP BY month, s.id, u.email
//         ORDER BY month DESC
//     ";

//     return $conn->executeQuery($sql)->fetchAllAssociative();
// }

    public function getGlobalMonthlyRevenue(): array
{
    $conn = $this->em->getConnection();

    $sql = "
        SELECT 
            DATE_FORMAT(o.order_date, '%Y-%m') AS month,
            SUM(bp.quantity * p.price) AS revenue,
            IFNULL(SUM(o.refund_amount), 0) AS refund_amount,
            s.name AS shop_name,
            u.email AS shop_email,
            ROUND(SUM(bp.quantity * p.price) * 0.10, 2) AS commission  -- Commission de 10%
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Entregue e finalizado'
        GROUP BY month, s.id, u.email
        ORDER BY month DESC
    ";

    return $conn->executeQuery($sql)->fetchAllAssociative();
}


    /**
     * Chiffre d'affaires global par produit (avec nom & email shop).
     */
    public function getGlobalRevenueByProduct(): array
{
    $conn = $this->em->getConnection();

    $sql = "
        SELECT 
            p.name AS product,
            SUM(bp.quantity * p.price) AS revenue,
            IFNULL(SUM(o.refund_amount), 0) AS refund_amount,  -- Ajout du montant du remboursement
            s.name AS shop_name,
            u.email AS shop_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Entregue e finalizado'
        GROUP BY p.id, s.id, u.email
        ORDER BY revenue DESC
    ";

    return $conn->executeQuery($sql)->fetchAllAssociative();
}


    /**
     * Chiffre d'affaires global par catégorie (avec nom & email shop).
     */
    public function getGlobalRevenueByCategory(): array
{
    $conn = $this->em->getConnection();

    $sql = "
        SELECT 
            c.name AS category,
            SUM(bp.quantity * p.price) AS revenue,
            IFNULL(SUM(o.refund_amount), 0) AS refund_amount,  -- Ajout du montant du remboursement
            s.name AS shop_name,
            u.email AS shop_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN category c ON p.category_id = c.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Entregue e finalizado'
        GROUP BY c.id, s.id, u.email
        ORDER BY revenue DESC
    ";

    return $conn->executeQuery($sql)->fetchAllAssociative();
}

}
