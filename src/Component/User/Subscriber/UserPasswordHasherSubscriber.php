<?php

declare(strict_types=1);

namespace App\Component\User\Subscriber;

use App\Component\User\Modifier\UserPasswordModifierInterface;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Event\AbstractLifecycleEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class UserPasswordHasherSubscriber implements EventSubscriberInterface
{
    public function __construct(private UserPasswordModifierInterface $userPasswordModifier)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['updatePassword'],
            BeforeEntityUpdatedEvent::class => ['updatePassword'],
        ];
    }

    public function updatePassword(AbstractLifecycleEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if (!($entity instanceof User)) {
            return;
        }

        $this->userPasswordModifier->modify($entity);
    }
}
