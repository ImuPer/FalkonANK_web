<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class MusicTakeoverMailer
{
    public function __construct(
        private TranslatorInterface $translator,
        private MailerInterface $mailer
    ) {
    }

    public function sendTakeoverCode(User $user, string $code): void
    {
        $subject = sprintf(
            '%s %s',
            $this->translator->trans('music.email.takeover.description'),
            $this->translator->trans('music.email.takeover.subject')
        );

        $email = (new Email())
            ->from('no-reply@falkon.click')
            ->to($user->getEmail())
            ->subject($subject)
            ->html("
            <div style='font-family:Arial'>
                <h2>{$this->translator->trans('music.email.takeover.title')}</h2>

                <p>{$this->translator->trans('music.email.takeover.description')}</p>

                <p><strong>{$this->translator->trans('music.email.takeover.code_label')}</strong></p>

                <h1 style='letter-spacing:4px'>{$code}</h1>

                <p>{$this->translator->trans('music.email.takeover.ignore')}</p>
            </div>
        ");

        $this->mailer->send($email);
    }
}