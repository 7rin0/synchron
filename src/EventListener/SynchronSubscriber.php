<?php

  namespace Drupal\synchron\EventListener;

  use Symfony\Component\HttpFoundation\RedirectResponse;
  use Symfony\Component\HttpKernel\Event\GetResponseEvent;
  use Symfony\Component\EventDispatcher\EventSubscriberInterface;
  use Drupal\Core\Database\Database;
  use Drupal\node\Entity\Node;
  // TODO add more listeners adnd subscribers
  use Symfony\Component\HttpKernel\KernelEvents;
  use Symfony\Component\HttpKernel\ConfigEvents;

  class SynchronSubscriber implements EventSubscriberInterface {

    public function checkForSynchronization(GetResponseEvent $event) {
      // // Get main synchron services
      // $synchronService = \Drupal::service('synchron');
      //
      // // TODO: Get this values from admin form
      // // Get name off from and to databases
      // $fromDatabase = $synchronService->getDefaultConnectionOptions()['database'];
      // $toDatabase = $fromDatabase === 'ixarm' ? 'ixarm_achats' : 'ixarm';
      //
      // // TODO: Add synchro id field
      // // TODO add subriber AFTER SAVE NODE and SYNC the ALREADY SYNCED NODESS
      // // If node exists
      // if($originalNode = Node::load(11261)) {
      //   $synchronService->provisionFromSiteToAnother($originalNode->id(), $fromDatabase, $toDatabase);
      // }

      // var_dump($event->GetResponseEvent())

      // echo uniqid() . '<br>';die();

      // TODO if asked synchronization of content we check request here and then we provision
    }

    /**
     * {@inheritdoc}
     */
    static function getSubscribedEvents() {
      $events[KernelEvents::REQUEST][] = array('checkForSynchronization');
      $events[ConfigEvents::DELETE][] = array('checkForSynchronization');
      return $events;
    }

  }
