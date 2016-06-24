<?php

  namespace Drupal\synchron\Controller;

  use Drupal\Core\Controller\ControllerBase;
  use Symfony\Component\HttpFoundation\RedirectResponse;
  use Drupal\Core\Entity\EntityInterface;
  use Drupal\node\Entity\Node;
  use Drupal\group\Entity\Group;

  class SynchronController extends ControllerBase {

    public function syncToDatabase() {
      // Get main synchron services
      $synchronService = \Drupal::service('synchron');

      // Get request node and database
      // TODO do not use this parameter , need to think about it
      // TODO Dynamic routing parameters
      $targetDatabase = \Drupal::request()->get('database');
      $originalNodeID = \Drupal::request()->get('node');
      $loadEntity = strpos($_SERVER[REQUEST_URI], 'node') ? Node::load($originalNodeID) : Group::load($originalNodeID);

      var_dump($loadEntity->toArray());
      die();

      // TODO: Get this values from admin form
      // Get name off from and to databases
      $fromDatabase = $synchronService->getDefaultConnectionOptions()['database'];
      $toDatabase = $fromDatabase === 'ixarm' ? 'ixarm_achats' : 'ixarm';

      // TODO: Add synchro id field
      // TODO add subriber AFTER SAVE NODE and SYNC the ALREADY SYNCED NODESS
      // If node exists
      if($originalNode = Node::load($originalNodeID)) {
        $synchronService->provisionFromSiteToAnother($originalNode->id(), $fromDatabase, $toDatabase);
      }

      // Revenir à la page de gestion des fonctionnalites
      return new RedirectResponse('/admin/content');
    }

  }
