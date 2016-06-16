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

  }
