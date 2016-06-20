<?php

  // TODO: USE THIS AS SERVICE
  namespace Drupal\synchron\Service;

  use \Drupal\node\Entity\Node;

  class SynchronService {

    protected $serviceDatabase;
    protected $defaultConnectionOptions;

    public function __construct() {
      $this->serviceDatabase = \Drupal::service('database');
      $this->defaultConnectionOptions = $this->serviceDatabase->getConnectionOptions();
    }

    // Get Default database connection
    public function getDefaultConnectionOptions() {
      return $this->defaultConnectionOptions;
    }

    // TODO Stock connection one and two to avoid open new one each time
    public function setConnectionDatabase($databaseName) {

      // Get active current database service connection
      $getConnectionOptions = $this->getDefaultConnectionOptions();

      // Choose a database name
      // Use an admin form to handle the database name liste
      // Override default database name only because
      // This will work on the same server only
      $getConnectionOptions['database'] = $databaseName;

      // Return the good PDO object to this connection
      $pdoConnection = $this->serviceDatabase->open($getConnectionOptions);

      // Override the service by passing new values to connection construct
      $this->serviceDatabase->__construct($pdoConnection, $getConnectionOptions);

      // CHeck if module exists
      $this->moduleHandler();
    }

    public function loadNode($value, $field) {
      // Get storage
      $nodeStorage = \Drupal::entityManager()->getStorage('node');
      $nodeStorage->resetCache(array($value));

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

      // If entity exists continue
      if($loadNodeThisDatabase = $this->loadNode($nid, 'nid')) {
        $nodeThisDatabase = $loadNodeThisDatabase->toArray();
        $originalRevisions = $this->getRevisions($loadNodeThisDatabase);

        // If entity hasnt synchronid add new one
        if(!$syncronid = $nodeThisDatabase['synchronid']) {
          $syncronid = uniqid();
          $loadNodeThisDatabase->set('synchronid', $syncronid)->save();
        } else {
          $syncronid = $syncronid[0]['value'];
        }

        // Synchro this content to another databases
        // Set database connection to $toDatabase
        $this->setConnectionDatabase($toDatabase);

        // Load node by synchronid to match the target node
        if($loadNodeTargetDatabase = $this->loadNode($syncronid, 'synchronid')) {
          // Delete target revisions
          $this->deleteRevisions($loadNodeTargetDatabase);
          // TODO replace target data with origin data
          // Delete target revisions
          // Replace target fields except nid
          // TODO when provisionning check if theres related entities do rovision aswell
          echo 'found';
        } else {
          $loadNodeTargetDatabase = $loadNodeThisDatabase->createDuplicate()->setOriginalId();
          $loadNodeTargetDatabase->save();
        }

        // Update revisions on target node
        $this->updateTargetNode($loadNodeThisDatabase, $loadNodeTargetDatabase, $originalRevisions);

        print_r('From');
        print_r($loadNodeThisDatabase->id());
        print_r('==============================================================================================================');
        print_r('To');
        print_r($loadNodeTargetDatabase->id());
        die();
      }
    }

    protected function updateTargetNode($originalNode, $targetNode, $originalNodeRevisions) {
      // Insert revisions
      foreach ($originalNodeRevisions as $revisionNode) {
        # code...
      }
    }

    protected function moduleHandler() {
      $moduleHandlerService = \Drupal::service('module_handler');
      $moduleInstallerService = \Drupal::service('module_installer');
      // Check if module exists
      $moduleExists = $moduleHandlerService->moduleExists('synchron');
      // Check if module is enabled
      $isSynchronEnabled = $this->serviceDatabase->select('key_value', 'kv')
        ->condition('kv.name', 'synchron')
        ->fields('kv')
        ->execute();

      // TODO Force entities to update if needed
      if(!$moduleExists || !$isSynchronEnabled->fetchAll(\PDO::FETCH_OBJ)) {
        $moduleExists = $moduleInstallerService->install(array('synchron'));
      }

      return $moduleExists;
    }

    // Delete all revisions to a given node
    protected function deleteRevisions($targetNode) {
      $entityManagerService = $node_revision = \Drupal::entityTypeManager();
      $getEntityRevisions = $this->serviceDatabase->delete('node_revision')
        ->condition('revision_uid', $targetNode->get('revision_uid')->getValue()[0]['target_id'])
        ->execute();
    }

    // Return all revisions to a given node
    protected function getRevisions($originalNode) {
      $entityManagerService = $node_revision = \Drupal::entityTypeManager();
      $getEntityRevisions = $this->serviceDatabase->select('node_revision', 'nr')
        ->condition('nr.revision_uid', $originalNode->get('revision_uid')->getValue()[0]['target_id'])
        ->fields('nr')
        ->execute();

      if($originalRevisions = $getEntityRevisions->fetchAll(\PDO::FETCH_OBJ)) {
        // Load Original Revisions entities
        $originalRevisionEntity = [];
        foreach ($originalRevisions as $key => $value) {
          // TODO dynamique load by entity instead only node based
          $originalRevisionEntity[] = $entityManagerService->getStorage('node')->loadRevision($value->vid);
        }
      }
    }

    public function getEntity() {
      // Get entity
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'article');
      $entity_ids = $query->execute();
      return $entity_ids;
    }
  }
