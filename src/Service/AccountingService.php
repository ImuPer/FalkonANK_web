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
            s.id AS shop_id,
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
            s.id AS shop_id,  -- Ajout de l'ID de la boutique
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
        GROUP BY month, s.id, s.name, u.email
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
    // --------------------------------------------------------------------------------------------------------------


    //Tout les commandes finalisé d'un shop par mois
    public function getFinalizedOrdersByShopGroupedByMonth(int $shopId): array
    {
        $conn = $this->em->getConnection();

        $sql = "
        SELECT 
            DATE_FORMAT(o.order_date, '%Y-%m') AS month,
            o.ref AS order_ref,
            o.stripe_pay_id AS stripe_pay_id,
            o.amount_final AS amount_final,
            o.order_date,
            o.order_status,
            o.refund_amount,
            SUM(bp.quantity * p.price) AS total_amount,
            u.email AS customer_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Entregue e finalizado'
          AND s.id = :shopId
        GROUP BY month, o.id, o.order_date, o.order_status, o.refund_amount, u.email
        ORDER BY month DESC, o.order_date DESC
    ";

        return $conn->executeQuery($sql, ['shopId' => $shopId])->fetchAllAssociative();
    }

    //Tout les commandes finalisé d'un shop par semainne
    public function getFinalizedOrdersByShopGroupedByWeek(int $shopId): array
    {
        $conn = $this->em->getConnection();

        $sql = "
        SELECT 
            YEAR(o.order_date) AS year,
            WEEK(o.order_date, 1) AS week,
            CONCAT(YEAR(o.order_date), '-W', LPAD(WEEK(o.order_date, 1), 2, '0')) AS year_week,
            o.ref AS order_ref,
            o.stripe_pay_id AS stripe_pay_id,
            o.amount_final AS amount_final,
            o.order_date,
            o.order_status,
            o.refund_amount,
            SUM(bp.quantity * p.price) AS total_amount,
            u.email AS customer_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Entregue e finalizado'
          AND s.id = :shopId
        GROUP BY year_week, o.id, o.order_date, o.order_status, o.refund_amount, u.email
        ORDER BY year DESC, week DESC, o.order_date DESC
    ";

        return $conn->executeQuery($sql, ['shopId' => $shopId])->fetchAllAssociative();
    }


    //Tout les commandes remboursé d'un shop par mois
    public function getRembursedOrdersByShopGroupedByMonth(int $shopId): array
    {
        $conn = $this->em->getConnection();

        $sql = "
        SELECT 
            DATE_FORMAT(o.order_date, '%Y-%m') AS month,
            o.ref AS order_ref,
            o.stripe_pay_id AS stripe_pay_id,
            o.amount_final AS amount_final,
            o.order_date,
            o.order_status,
            o.refund_amount,
            SUM(bp.quantity * p.price) AS total_amount,
            u.email AS customer_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Reembolso'
          AND o.refund_status = 'Reembolsado'
          AND s.id = :shopId
        GROUP BY month, o.id, o.order_date, o.order_status, o.refund_amount, u.email
        ORDER BY month DESC, o.order_date DESC
    ";

        return $conn->executeQuery($sql, ['shopId' => $shopId])->fetchAllAssociative();
    }

    //Tout les commandes remboursé d'un shop par mois
    public function getCourRembursOrdersByShopGroupedByMonth(int $shopId): array
    {
        $conn = $this->em->getConnection();

        $sql = "
        SELECT 
            DATE_FORMAT(o.order_date, '%Y-%m') AS month,
            o.ref AS order_ref,
            o.stripe_pay_id AS stripe_pay_id,
            o.amount_final AS amount_final,
            o.order_date,
            o.order_status,
            o.refund_amount,
            SUM(bp.quantity * p.price) AS total_amount,
            u.email AS customer_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Reembolso'
          AND o.refund_status = 'Em curso'
          AND s.id = :shopId
        GROUP BY month, o.id, o.order_date, o.order_status, o.refund_amount, u.email
        ORDER BY month DESC, o.order_date DESC
    ";

        return $conn->executeQuery($sql, ['shopId' => $shopId])->fetchAllAssociative();
    }

    //Tout les commandes non finalisé d'un shop par mois
    public function getNonFinalizedOrdersByShopGroupedByMonth(int $shopId): array
    {
        $conn = $this->em->getConnection();

        $sql = "
        SELECT 
            DATE_FORMAT(o.order_date, '%Y-%m') AS month,
            o.ref AS order_ref,
            o.stripe_pay_id AS stripe_pay_id,
            o.amount_final AS amount_final,
            o.order_date,
            o.order_status,
            o.refund_amount,
            SUM(bp.quantity * p.price) AS total_amount,
            u.email AS customer_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Em processamento'
          AND s.id = :shopId
        GROUP BY month, o.id, o.order_date, o.order_status, o.refund_amount, u.email
        ORDER BY month DESC, o.order_date DESC
    ";

        return $conn->executeQuery($sql, ['shopId' => $shopId])->fetchAllAssociative();
    }



    //function que retourne les commandes livrées et finalisées (Entregue e finalizado) pour une boutique spécifique
    public function getOrdersByShop(int $shopId): array
    {
        $conn = $this->em->getConnection();

        $sql = "
        SELECT 
            o.ref AS order_ref,
            o.order_date,
            o.refund_amount,
            SUM(bp.quantity * p.price) AS total_amount,
            u.email AS customer_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Entregue e finalizado'
          AND s.id = :shopId
        GROUP BY o.id, o.order_date, o.refund_amount, u.email
        ORDER BY o.order_date DESC
    ";

        return $conn->executeQuery($sql, ['shopId' => $shopId])->fetchAllAssociative();
    }

    /**
     * Retourne toutes les commandes remboursées d'une boutique donnée.
     * (order_status = 'Reembolso' ET refund_status = 'Reembolsado')
     */
    public function getRembursedOrdersByShop(int $shopId): array
    {
        $conn = $this->em->getConnection();

        $sql = "
        SELECT 
            o.ref AS order_ref,
            o.order_date,
            o.order_status,
            o.refund_amount,
            SUM(bp.quantity * p.price) AS total_amount,
            u.email AS customer_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Reembolso'
          AND o.refund_status = 'Reembolsado'
          AND s.id = :shopId
        GROUP BY o.id, o.ref, o.order_date, o.order_status, o.refund_amount, u.email
        ORDER BY o.order_date DESC
    ";

        return $conn->executeQuery($sql, ['shopId' => $shopId])->fetchAllAssociative();
    }


    // Funtion retourner toutes les commandes d'une boutique donnée, sauf celles qui sont finalisées (order_status != 'Entregue e finalizado'), 
    public function getNonFinalizedOrdersByShop(int $shopId): array
    {
        $conn = $this->em->getConnection();

        $sql = "
        SELECT 
            o.ref AS order_ref,
            o.order_date,
            o.order_status,
            o.refund_amount,
            SUM(bp.quantity * p.price) AS total_amount,
            u.email AS customer_email
        FROM basket_product bp
        JOIN product p ON bp.product_id = p.id
        JOIN shop s ON p.shop_id = s.id
        JOIN `user` u ON s.user_id = u.id
        JOIN `order` o ON bp.order_c_id = o.id
        WHERE o.order_status = 'Em processamento'
          AND s.id = :shopId
        GROUP BY o.id, o.order_date, o.order_status, o.refund_amount, u.email
        ORDER BY o.order_date DESC
    ";

        return $conn->executeQuery($sql, ['shopId' => $shopId])->fetchAllAssociative();
    }

}
