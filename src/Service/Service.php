<?php

namespace App\Service;
use App\Repository\BasketProductRepository;
use App\Repository\BasketRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class Service
{

    private $userRepository;
    private $validator;
    private $basketRepository;
    private $basketProductRepository;
     private $cacheTotalQuantity = [];

    public function __construct(UserRepository $userRepository, ValidatorInterface $validator,
     BasketRepository $basketRepository, BasketProductRepository $basketProductRepository)
    {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->basketRepository = $basketRepository;
        $this->basketProductRepository = $basketProductRepository;
    }

    public function validateEmail( string $newEmail): array
    {
        // Define the email constraints
        $constraints = [
            new NotBlank(),
            new Email()
        ];

        // Validate the email
        $violations = $this->validator->validate($newEmail, $constraints);

        // Check if there are violations
        if (count($violations) > 0) {
            // There are validation errors
            $errorMessages = [];
            foreach ($violations as $violation) {
                $errorMessages[] = $violation->getMessage();
            }

            return $errorMessages;
        }

        // If no violations, return an empty array
        return [];
    }


    public function validatePassword(string $password): array
    {
        $errors = [];

        // Check for at least one number
        if (!preg_match('@[0-9]@', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }

        // Check for at least one uppercase letter
        if (!preg_match('@[A-Z]@', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }

        // Check for at least one lowercase letter
        if (!preg_match('@[a-z]@', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }

        // Check for at least one special character
        if (!preg_match('@[^\w]@', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        // Check for minimum length of 8 characters
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        return $errors;
    }

    // public function getTotalQuantityForUserWherePaymentFalse($userId): int
    // {
    //     $user = $this->userRepository->find($userId);
    //     $basketId = $this->basketRepository->findOneBy(['user' => $user]); //recuperer le basket user
    //     return $this->basketProductRepository->getTotalQuantityForBasketWherePaymentFalse($basketId);
    // }


      public function getTotalQuantityForUserWherePaymentFalse($userId): int
    {
        // Cache simple par userId
        if (isset($this->cacheTotalQuantity[$userId])) {
            return $this->cacheTotalQuantity[$userId];
        }

        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return 0;
            }

            $basket = $this->basketRepository->findOneBy(['user' => $user]);
            if (!$basket) {
                return 0;
            }

            $total = $this->basketProductRepository->getTotalQuantityForBasketWherePaymentFalse($basket);
            
            // Mise en cache
            $this->cacheTotalQuantity[$userId] = $total;

            return $total;

        } catch (\Exception $e) {
            // Log l'erreur ici si tu as un logger (optionnel)
            // Par exemple : $this->logger->error($e->getMessage());

            // En cas d'erreur, renvoyer 0 pour ne pas bloquer le template
            return 0;
        }
    }
}