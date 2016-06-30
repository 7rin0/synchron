<?php

namespace Drupal\synchron\EventListener;

// TODO add more listeners adnd subscribers.
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * SynchronSubscriber Handle some requests.
 *
 * @category Class
 *
 * @package SynchronSubscriber
 */
class SynchronSubscriber implements EventSubscriberInterface {

  /**
   * The method checkForSynchronization acts on request of certain events.
   *
   * @param GetResponseEvent $event
   *   Current event.
   */
  public function checkForSynchronization(GetResponseEvent $event) {
    // TODO add subscribers AFTER SAVE NODE and SYNC the ALREADY SYNCED NODESS.
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForSynchronization');
    return $events;
  }

}
