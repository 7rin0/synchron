<?php

  // TODO: USE THIS AS SERVICE
  namespace Drupal\synchron\Service;

  use \Drupal\node\Entity\Node;
  use \Drupal\core\Entity\Entity;

  class SynchronService {

    public $getStorage;
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
      // Query
      $query = \Drupal::entityQuery($this->$getStorage->getEntityTypeId());
      $query->condition($field, $value);
      $entity_ids = $query->execute();

      // Return the last updated node
      return $this->getStorage->load(@end($entity_ids));
    }

    // TODO alter names everywhere: originalEntity and TargetEntity
    public function provisionFromSiteToAnother(Entity $entity, $fromDatabase, $toDatabase) {
      // Set database connection to $fromDatabase
      $this->setConnectionDatabase($fromDatabase);
      // If entity exists continue
      if($entity) {
        // If entity hasnt synchronid add new one
        $synchronid = $this->prepareSynchronId($entity);

        // Get all revisions from original node
        $originalNodeRevisions = $this->getRevisions($entity);

        // Synchro this content to another databases
        // Set database connection to $toDatabase
        $this->setConnectionDatabase($toDatabase);

        // Load node by synchronid to match the target node
        if($loadNodeTargetDatabase = $this->loadNode($synchronid, 'synchronid')) {
          // TODO replace target data with origin data
          // Delete target revisions
          // Replace target fields except nid
          // TODO when provisionning check if theres related entities do rovision aswell
          // echo '#####FOUND#####';
        } else {
          // echo '#####NOT FOUND#####';
          $loadNodeTargetDatabase = get_class($loadEntity)::create($this->entityValues($entity));
          $loadNodeTargetDatabase->save();
        }

        // Update revisions on target node
        $this->updateTargetNode($entity, $loadNodeTargetDatabase, $originalNodeRevisions);

        // Set target database as default
        $this->setConnectionDatabase($fromDatabase);
      }
    }

    public function prepareSynchronId($node, $return = 'nid') {
      $nodeThisDatabase = $node->toArray();
      if(!(boolean)$nodeThisDatabase['synchronid']) {
        $synchronid = mt_rand();
        if($node->isNew()) {
          $node->set('synchronid', $synchronid);
        } else {
          // $node->set('synchronid', $synchronid)->save();
        }
      }
      return $return === 'nid' ?
        $node->get('synchronid')->getValue()[0]['value'] : $node;
    }

    protected function entityValues($node, $returnUnique = true) {

      // Create associative array of key value from each field
      $fieldsKeyValue = [];
      // Entity to array
      $nodeValues = $node->toArray();
      unset(
        $nodeValues['nid'],
        $nodeValues['uuid'],
        $nodeValues['uid'],
        $nodeValues['vid']
      );

      // Return Key => value/pair
      foreach ($nodeValues as $key => $value) {
        if($value = @reset($value[0])) {
          $fieldsKeyValue[$key] = $value;
        }
      }

      return $fieldsKeyValue;
    }

    protected function updateTargetNode($originalNode, $targetNode, $originalNodeRevisions) {
      // Delete target revisions
      $this->deleteRevisions($targetNode);
      // Insert revisions
      foreach ($originalNodeRevisions as $revisionNode) {
        $revisionValues = $this->entityValues($revisionNode);
        foreach ($revisionValues as $key => $value) {
          if($targetNode->getFieldDefinition($key)) {
            $targetNode->set($key, $value);
          }
        }
        $targetNode->setNewRevision();
        $targetNode->save();
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
      if($targetNode) {
        $entityManagerService = $node_revision = \Drupal::entityTypeManager();
        $targetNodeVid = $targetNode->get('vid')->getValue()[0]['value'];
        if((boolean)$targetNodeVid) {
          $deleteOldRevisions = $this->serviceDatabase->delete('node_revision')
            ->condition('nid', $targetNode->id())
            ->condition('vid', $targetNodeVid, '<')
            ->execute();
        }
      }
    }

    // Return all revisions to a given node
    protected function getRevisions($originalNode) {
      $entityManagerService = $node_revision = \Drupal::entityTypeManager();
      $getEntityRevisions = $this->serviceDatabase->select('node_revision', 'nr')
        ->condition('nr.nid', $originalNode->id())
        ->fields('nr')
        ->execute();

      if($originalRevisions = $getEntityRevisions->fetchAll(\PDO::FETCH_OBJ)) {
        // Load Original Revisions entities
        $originalRevisionEntity = [];
        foreach ($originalRevisions as $key => $value) {
          // TODO dynamique load by entity instead only node based
          $originalRevisionEntity[] = $this->getStorage->loadRevision($value->vid);
        }
        return $originalRevisionEntity;
      }
    }

    public function getEntity() {
      // Get entity
      $query = \Drupal::entityQuery($this->getStorage->getEntityTypeId());
      $query->condition('type', 'article');
      $entity_ids = $query->execute();
      return $entity_ids;
    }
  }
