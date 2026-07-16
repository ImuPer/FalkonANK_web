<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MusicTakeoverMailer
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function sendTakeoverCode(User $user, string $code): void
    {
        $email = (new Email())
            ->from('no-reply@falkon.click')
            ->to($user->getEmail())
            ->subject(' Nouvelle connexion détectée')
            ->html("
                <div style='font-family:Arial'>
                    <h2>Nouvelle connexion détectée</h2>
                    <p>Un nouvel appareil tente d’accéder à votre session musicale.</p>

                    <p><strong>Code de validation :</strong></p>
                    <h1 style='letter-spacing:4px'>{$code}</h1>

                    <p>Si ce n’est pas vous, ignorez cet email.</p>
                </div>
            ");

        $this->mailer->send($email);
    }
}