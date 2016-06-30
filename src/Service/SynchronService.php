<?php

namespace Drupal\synchron\Service;

use \Drupal\core\Entity\Entity;

/**
 * SynchronService Handle some requests.
 *
 * @category Class
 *
 * @package SynchronService
 */
class SynchronService {

  public $getStorage;
  protected $serviceDatabase;
  protected $defaultConnectionOptions;

  /**
   * Class constructor __construct.
   */
  public function __construct() {
    // TODO URGENT.
    // TODO offcourse impreve this, avoid dependency on URL.
    $entityArg0 = explode('/', $_SERVER[REQUEST_URI]);
    $getStorage = \Drupal::entityManager()->getStorage($entityArg0[array_search('synchron', $entityArg0) - 2]);
    $this->getStorage = $getStorage;
    $this->serviceDatabase = \Drupal::service('database');
    $this->defaultConnectionOptions = $this->serviceDatabase->getConnectionOptions();
  }

  /**
   * Get Default database connection.
   *
   * @return array
   *   connection options.
   */
  public function getDefaultConnectionOptions() {
    return $this->defaultConnectionOptions;
  }

  /**
   * Set target database as default.
   *
   * @param string $databaseName
   *   Database name.
   */
  public function setConnectionDatabase($databaseName) {
    // TODO Stock connection one and two to avoid open new one each time.
    \Drupal::cache()->delete();
    // Get active current database service connection.
    $getConnectionOptions = $this->getDefaultConnectionOptions();

    // Choose a database name.
    // Use an admin form to handle the database name liste.
    // Override default database name only because.
    // This will work on the same server only.
    $getConnectionOptions['database'] = $databaseName;

    // Return the good PDO object to this connection.
    $pdoConnection = $this->serviceDatabase->open($getConnectionOptions);

    // Override the service by passing new values to connection construct.
    $this->serviceDatabase->__construct($pdoConnection, $getConnectionOptions);

    // TODO better implementations.
    // Update Storage.
    \Drupal::entityManager()->getStorage('user')->resetCache();
    $this->getStorage->resetCache();
    $this->getStorage = \Drupal::entityManager()->getStorage($this->getStorage->getEntityTypeId());

    // CHeck if module exists.
    $this->moduleHandler();
  }

  /**
   * Load entity by field/value.
   *
   * @param string $value
   *   The expected value to the conditional field.
   * @param string $field
   *   The field being used to get the entity.
   *
   * @return Entity|FALSE
   *   Return found entity or FALSE otherwise.
   */
  public function loadEntity($value, $field) {
    // Query.
    $query = \Drupal::entityQuery($this->getStorage->getEntityTypeId());
    $query->condition($field, $value);
    $entity_ids = $query->execute();

    // Return the last updated entity.
    return $this->getStorage->load(@end($entity_ids));
  }

  /**
   * Provision a content from origin databse to the target one.
   *
   * @param Entity $originalEntity
   *   Original entity.
   * @param string $fromDatabase
   *   Origin Database name.
   * @param string $toDatabase
   *   Target Database name.
   */
  public function provisionFromSiteToAnother(Entity $originalEntity, $fromDatabase, $toDatabase) {
    // TODO alter names everywhere: originalEntity and TargetEntity.
    // Set database connection to $fromDatabase.
    $this->setConnectionDatabase($fromDatabase);
    // If entity exists continue.
    if ($originalEntity) {
      // If entity hasnt synchronid add new one.
      $synchronid = $this->setGetSynchronId($originalEntity);

      // Get all revisions from original entity.
      $originalEntityRevisions = $this->getRevisions($originalEntity);

      // Synchro this content to another databases.
      // Set database connection to $toDatabase.
      $this->setConnectionDatabase($toDatabase);

      // Load entity by synchronid to match the target entity.
      if ($loadEntityTargetDatabase = $this->loadEntity($synchronid, 'synchronid')) {
        // TODO replace target data with origin data.
        // Delete target revisions.
        // Replace target fields except nid.
        $this->updateTargetEntity($originalEntity, $loadEntityTargetDatabase, NULL);
      }
      else {
        // TODO IGNORE TARGET ID.
        // TODO Throw error due missing fields.
        // TODO ask t synchron data safe mode.
        $loadEntityTargetDatabase = $this->getStorage->create($this->entityValues($originalEntity));
        $loadEntityTargetDatabase->set('uid', 1);
        $loadEntityTargetDatabase->set('synchronid', $synchronid);
        // TODO log actions and violations everywhere.
        // TODO Check first database dependencies to avoid errors on migration.
        // var_dump($loadEntityTargetDatabase->toArray());
        $loadEntityTargetDatabase->save();
      }

      // TODO check if has revisions.
      // Update revisions on target entity.
      if ($loadEntityTargetDatabase->hasField('vid')) {
        $this->updateTargetEntity($originalEntity, $loadEntityTargetDatabase, $originalEntityRevisions);
      }

      // Set target database as default.
      $this->setConnectionDatabase($fromDatabase);
    }
  }

  /**
   * Return and set synchronid, if not defined, on origin Entity.
   *
   * @param Entity $originalEntity
   *   Entity to be checked or updated.
   *
   * @return string
   *   Return the synchron id.
   */
  public function setGetSynchronId(Entity $originalEntity) {
    $originalEntityToArray = $originalEntity->toArray();
    if ($originalEntity->isNew()) {
      $originalEntity->set('synchronid', mt_rand());
    }
    elseif (!(boolean) $originalEntityToArray['synchronid']) {
      $originalEntity->set('synchronid', mt_rand())->save();
    }
    return $originalEntity->get('synchronid')->getValue()[0]['value'];
  }

  /**
   * Extract the values from an entity and prepare to set on target one.
   *
   * @param Entity $entity
   *   Origin entity from we extract values.
   *
   * @return array $fieldsKeyValue
   *   Return sanitized fields.
   */
  protected function entityValues(Entity $entity) {
    // Create associative array of key value from each field.
    $fieldsKeyValue = [];
    // Entity to array.
    $entityValues = $entity->toArray();
    $entityValues['uid'] = 1;
    unset(
      $entityValues['id'],
      $entityValues['nid'],
      $entityValues['uuid'],
      $entityValues['vid']
    );

    // Return Key => value/pair.
    foreach ($entityValues as $key => $value) {
      if ($entity->getFieldDefinition($key) && $value = @reset($value[0])) {
        $fieldsKeyValue[$key] = $value;
      }
    }

    return $fieldsKeyValue;
  }

  /**
   * Upate values of the target entity.
   *
   * @param Entity $originalEntity
   *   The origin entity.
   * @param Entity $targetEntity
   *   The targeted entity.
   * @param array $originalEntityRevisions
   *   The revisions to also synchronize.
   */
  protected function updateTargetEntity(Entity $originalEntity, Entity $targetEntity, $originalEntityRevisions = NULL) {
    // Fields first.
    $this->setValuesIntoEntity($targetEntity, $this->entityValues($originalEntity));
    $targetEntity->save();

    // Revisions next.
    if ($originalEntityRevisions) {
      $this->deleteRevisions($targetEntity);
      // Insert revisions.
      foreach ($originalEntityRevisions as $revisionEntity) {
        $revisionValues = $this->entityValues($revisionEntity);
        // Update, insert and validate fields.
        $this->setValuesIntoEntity($targetEntity, $revisionValues);
        $targetEntity->setNewRevision();
        $targetEntity->save();
      }
    }
  }

  /**
   * From a group of values keyed by field update an entity.
   *
   * @param Entity $entity
   *   The affected entity.
   * @param array $values
   *   The values to be set on entity.
   */
  protected function setValuesIntoEntity(Entity $entity, $values) {
    foreach ($values as $key => $value) {
      if ($entity->getFieldDefinition($key)) {
        $entity->set($key, $value);
      }
    }
  }

  /**
   * Ensure that synchron is enable on both databases.
   *
   * @return boolean $moduleExists
   *   True if is enable False otherwise.
   */
  protected function moduleHandler() {
    $moduleHandlerService = \Drupal::service('module_handler');
    $moduleInstallerService = \Drupal::service('module_installer');
    // Check if module exists.
    $moduleExists = $moduleHandlerService->moduleExists('synchron');

    // Check if module is enabled.
    $isSynchronEnabled = $this->serviceDatabase->select('key_value', 'kv')
      ->condition('kv.name', 'synchron')
      ->fields('kv')
      ->execute();
    // TODO Force entities to update if needed.
    if (!$moduleExists || !$isSynchronEnabled->fetchAll(\PDO::FETCH_OBJ)) {
      $moduleExists = $moduleInstallerService->install(array('synchron'));
    }

    return $moduleExists;
  }

  /**
   * Delete all revisions of a given entity.
   *
   * @param Entity $targetEntity
   *   The entity to be removed all revision references.
   */
  protected function deleteRevisions(Entity $targetEntity) {
    if ($targetEntity) {
      $entityManagerService = $entity_revision = \Drupal::entityTypeManager();
      $targetEntityVid = $targetEntity->get('vid')->getValue()[0]['value'];
      if ((boolean) $targetEntityVid) {
        $deleteOldRevisions = $this->serviceDatabase->delete('node_revision')
          ->condition('nid', $targetEntity->id())
          ->condition('vid', $targetEntityVid, '<')
          ->execute();
      }
    }
  }

  /**
   * Return all revisions to a given entity.
   *
   * @param Entity $originalEntity
   *   The entity from we extract the revisions.
   *
   * @return array $originalRevisionEntity
   *   Return all revisions
   */
  protected function getRevisions(Entity $originalEntity) {
    $entityManagerService = $entity_revision = \Drupal::entityTypeManager();
    $getEntityRevisions = $this->serviceDatabase->select('node_revision', 'nr')
      ->condition('nr.nid', $originalEntity->id())
      ->fields('nr')
      ->execute();

    if ($originalRevisions = $getEntityRevisions->fetchAll(\PDO::FETCH_OBJ)) {
      // Load Original Revisions entities.
      $originalRevisionEntity = [];
      foreach ($originalRevisions as $key => $value) {
        // TODO dynamique load by entity instead only entity based.
        $originalRevisionEntity[] = $this->getStorage->loadRevision($value->vid);
      }
      return $originalRevisionEntity;
    }
  }

}
