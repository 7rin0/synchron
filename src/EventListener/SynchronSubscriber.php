<?php

  namespace Drupal\synchron\EventListener;

  use Symfony\Component\HttpFoundation\RedirectResponse;
  use Symfony\Component\HttpKernel\KernelEvents;
  use Symfony\Component\HttpKernel\Event\GetResponseEvent;
  use Symfony\Component\EventDispatcher\EventSubscriberInterface;
  use Drupal\Core\Database\Database;

  class SynchronSubscriber implements EventSubscriberInterface {

    public function checkForSynchronization(GetResponseEvent $event) {
      if ($event->getRequest()->query->get('redirect-me')) {
      }
    }

    /**
     * {@inheritdoc}
     */
    static function getSubscribedEvents() {
      $events[KernelEvents::REQUEST][] = array('checkForSynchronization');
      return $events;
    }

  }
