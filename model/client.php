<?php

require_once('core/model.php');
require_once('compte.php');

class Client extends Model {

  public $id;
  public $nom;
  public $prenom;
  public $ville;

  public function comptes() {
    $compte = new Compte($this->dbh);
    return $this->hasMany($compte);
  }
  
}

