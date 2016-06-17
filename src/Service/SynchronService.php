<?php

  // TODO: USE THIS AS SERVICE
  namespace Drupal\synchron\Service;

  use \Drupal\node\Entity\Node;

  class SynchronService {

    protected $defaultConnectionOptions;

    public function __construct() {
      $getConnectionOptions = \Drupal::service('database')->getConnectionOptions();

      // Store default connected database
      $this->setDefaultConnectionOptions($getConnectionOptions);
    }

    protected function setDefaultConnectionOptions($connectionOptions) {
      $this->defaultConnectionOptions = $connectionOptions;
    }

    public function getDefaultConnectionOptions() {
      return $this->defaultConnectionOptions;
    }

    public function setConnectionDatabase($databaseName) {

      // Get active current database service connection
      $getConnectionOptions = $this->getDefaultConnectionOptions();

      // Service database
      $databaseService = \Drupal::service('database');

      // Choose a database name
      // Use an admin form to handle the database name liste
      // Override default database name only because
      // This will work on the same server only
      $getConnectionOptions['database'] = $databaseName;

      // Return the good PDO object to this connection
      $pdoConnection = $databaseService->open($getConnectionOptions);

      // Override the service by passing new values to connection construct
      $databaseService->__construct($pdoConnection, $getConnectionOptions);

      // CHeck if module exists
      $this->moduleHandler();
    }

    public function loadNode($value, $field) {
      // Get storage
      $nodeStorage = \Drupal::entityManager()->getStorage('node');
      $nodeStorage->resetCache(array($nid));

      // Query
      $query = \Drupal::entityQuery('node');
      $query->condition($field, $value);
      $entity_ids = $query->execute();

      // Return node
      return Node::load(@reset($entity_ids));
    }

    public function provisionFromSiteToAnother($nid, $fromDatabase, $toDatabase) {

      // Set database connection to $fromDatabase
      $this->setConnectionDatabase($fromDatabase);
      $loadNodeThisDatabase = $this->loadNode($nid, 'nid');

      // If entity exists continue
      if($loadNodeThisDatabase) {
        // If entity hasnt synchronid add new one
        if(!$syncronid = $loadNodeThisDatabase->get('synchronid')->getValue()) {
          $syncronid = uniqid();
          $loadNodeThisDatabase->set('synchronid', $syncronid)->save();
        }

        // Synchro this content to another databases
        // Set database connection to $toDatabase
        $this->setConnectionDatabase($toDatabase);

        // Load node by synchronid to match the target node
        if($loadNodeTargetDatabase = $this->loadNode($nid, 'syncronid')) {

        } else {

        }

        die();
      }
    }

    protected function moduleHandler() {
      $databaseConnectionService = \Drupal::service('database');
      $moduleHandlerService = \Drupal::service('module_handler');
      $moduleInstallerService = \Drupal::service('module_installer');
      // Check if module exists
      $moduleExists = $moduleHandlerService->moduleExists('synchron');
      // Check if module is enabled
      $isSynchronEnabled = $databaseConnectionService->select('key_value', 'kv')
        ->condition('kv.name', 'synchron')
        ->fields('kv')
        ->execute();

      if(!$moduleExists || !$isSynchronEnabled->fetchAll(\PDO::FETCH_OBJ)) {
        $moduleExists = $moduleInstallerService->install(array('synchron'));
      }

      return $moduleExists;
    }

    public function getEntity() {
      // Get entity
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'article');
      $entity_ids = $query->execute();
      return $entity_ids;
    }

  }
