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

      // Choose a database name
      // Use an admin form to handle the database name liste
      // Override default database name only because
      // This will work on the same server only
      $getConnectionOptions['database'] = $databaseName;

      // Return the good PDO object to this connection
      $pdoConnection = \Drupal::service('database')->open($getConnectionOptions);

      // Override the service by passing new values to connection construct
      \Drupal::service('database')->__construct($pdoConnection, $getConnectionOptions);
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
          $loadNode->set('synchronid', $syncronid);
          $loadNode->save();
        }

        // Synchro this content to another databases
        // Set database connection to $toDatabase
        $this->setConnectionDatabase($toDatabase);
        // Load node by synchronid to match the target node
        $loadNodeTargetDatabase = $this->loadNodeId($nid, 'syncronid');

        if($loadNodeThisDatabase) {

        } else {

        }

        die();

        $loadNode->set('synchronid', 9878);
        $loadNode->save();

        var_dump($loadNode->toArray());

        print_r('<--------------------------!!!!!!!-------------------------->');

        // Set database connection to ixarm achats
        $this->setConnectionDatabase($toDatabase);
        print_r(getEntity());
        $loadNode = loadNode(11257);
        var_dump($loadNode->toArray());
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
