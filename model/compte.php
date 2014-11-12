<?php
require_once('core/model.php');
require_once('client.php');

class Compte extends Model {


  public function client() {
    $client = new Client($this->dbh);
    return $this->belongsTo($client);
  }
}