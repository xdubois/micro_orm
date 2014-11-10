micro_orm
=========

Description:
 Micro orm like Model implementation 
 using PDO

 You need to define the attributs in your model
 and specify any relations.

 This was an afternoon project, don't use it in production


#### get all clients
    $client = new Client($dbh);
    $clients = $client->all();
    foreach ($clients as $row) {
      print $row->nom;
    }

#### get a client and then all accounts
    $client = $client->find(1);
    foreach ($client->comptes()->get() as $compte) {
      var_dump($compte);
    }

#### get an account
    $compte = new Compte($dbh);
    $compte1 = $compte->find(1);

#### update the account
    $compte1->solde = 1000;
    $compte1->save();

#### get the account of a client
    $account = $compte1->client()->get();

#### query an entity
only simple query are supported : select, where, orderby
all three are chainable

    $client = new Client($dbh);
    $result = $client->select(['nom, prenom'])
           ->where('nom', '=', 'dubois')
           ->where('prenom', "<>", 'John', 'AND')
           ->orderBy('nom')
           ->orderBy('prenom', 'DESC')
           ->first();

#### get sql query
    $client->getSql();

#### get row affected by last query if any
or simply count the numbe of row

    $client->count();

#### add an entity
    $client = new Client($dbh);
    $client->nom = "Dubois";
    $client->prenom = "John";
    $inserted_id = $client->save();

#### more supported method
    $client->find(1, $column = 'id');
    $client->all();
    $client->remove(1, $column = 'id');
    $client->update($attributs[]);
