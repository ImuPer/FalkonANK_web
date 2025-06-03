<?php

namespace App\EventSubscriber;

use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    private Packages $packages;
    private RequestStack $requestStack;

    public function __construct(Packages $packages, RequestStack $requestStack)
    {
        $this->packages = $packages;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCrudActionEvent::class => 'onBeforeCrudAction',
        ];
    }

    public function onBeforeCrudAction(BeforeCrudActionEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $request->attributes->set('_easyadmin_assets', [
                [
                    'type' => 'js',
                    'path' => $this->packages->getUrl('build/refund_toggle.js'),
                ],
            ]);
        }
    }
}
