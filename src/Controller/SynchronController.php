<?php

namespace Drupal\synchron\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * SynchronController Class Handle synchronization between same server database.
 *
 * @category Class
 *
 * @package SynchronController
 */
class SynchronController extends ControllerBase {

  /**
   * Method syncToDatabase is applied to manage synchronization.
   *
   * @return RedirectResponse
   *   Redirect to Content Admin.
   */
  public function syncToDatabase() {
    // Get main synchron services.
    $synchronService = \Drupal::service('synchron');

    // Get request entity and database.
    // TODO do not use this parameter , need to think about it.
    // TODO Dynamic routing parameters.
    $targetDatabase = \Drupal::request()->get('database');
    $originalEntityID = \Drupal::request()->get('entity');

    // TODO this doesnt seem right nor global, update after.
    if ($originalEntity = $synchronService->getStorage->load($originalEntityID)) {

      // TODO: Get this values from admin form.
      // Get name off from and to databases.
      $fromDatabase = $synchronService->getDefaultConnectionOptions()['database'];
      $toDatabase = $fromDatabase === 'bdd1' ? 'bdd2' : 'bdd1';

      // TODO: Add synchro id field.
      // TODO add subriber AFTER SAVE NODE and SYNC the ALREADY SYNCED NODESS.
      // If entity exists.
      $synchronService->provisionFromSiteToAnother($originalEntity, $fromDatabase, $toDatabase);
    }

    // Revenir à la page de gestion des fonctionnalites.
    return new RedirectResponse('/admin/content');
  }

}
