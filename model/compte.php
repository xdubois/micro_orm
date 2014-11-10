<?php
require_once('core/model.php');
require_once('client.php');

class Compte extends Model {

  public $id;
  public $solde;
  public $taux;
  public $client_id;

  public function client() {
    $client = new Client($this->dbh);
    return $this->belongsTo($client);
  }
}