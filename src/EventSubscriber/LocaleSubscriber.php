<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private string $defaultLocale = 'pt') {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) return;

        // 1. Priorité à l'URL si passée : /fr/about
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
            return;
        }

        // 2. Sinon, si déjà en session, on l'utilise
        if ($sessionLocale = $request->getSession()->get('_locale')) {
            $request->setLocale($sessionLocale);
            return;
        }

        // 3. Enfin, on détecte via le navigateur
        $preferred = $request->getPreferredLanguage(['fr', 'pt', 'en', 'es']);
        $request->setLocale($preferred ?: $this->defaultLocale);
        $request->getSession()->set('_locale', $request->getLocale());
    }
}
