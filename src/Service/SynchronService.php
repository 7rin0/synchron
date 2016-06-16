<?php

  // TODO: USE THIS AS SERVICE
  namespace Drupal\synchron\Service;

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

    function setConnectionDatabase($databaseName) {

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

    function loadNode($nid) {
      \Drupal::entityManager()->getStorage('node')->resetCache(array($nid));
      return \Drupal\node\Entity\Node::load($nid);
    }

    function provisionFromSiteToAnother($nid, $fromDatabase, $toDatabase) {

      // Set database connection to ixarm
      $this->setConnectionDatabase($fromDatabase);
      $loadNode = $this->loadNode($nid);

      // If entity exists continue
      if($loadNode) {
        // If entity hasnt synchronid add new one
        var_dump($loadNode->get('synchronid'));
        die();

        var_dump($loadNode->toArray());

        print_r('<--------------------------!!!!!!!-------------------------->');

        // Set database connection to ixarm achats
        $this->setConnectionDatabase($toDatabase);
        print_r(getEntity());
        $loadNode = loadNode(11257);
        var_dump($loadNode->toArray());
      }

    }

    function getEntity() {
      // Get entity
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'article');
      $entity_ids = $query->execute();
      return $entity_ids;
    }

  }
