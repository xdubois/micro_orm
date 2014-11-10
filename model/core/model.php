<?php
/**
 * Name:  Model
 *
 * Author: Mathieu Dubois
 *         xd.dubois@gmail.com
 *
 * Created: 10.11.2014
 *
 * Description:
 * Micro orm like Model implementation 
 * using PDO
 */
abstract class Model {
    
  protected $table;
  protected $dbh;
  protected $primary_key = 'id';
  private $statement = NULL;
  private $sql;
  private $counters;
  private $params = [];

  public function __construct($dbh) {
    $this->dbh = $dbh;
    $this->table = strtolower(get_class($this));
    $this->counters['where'] = 0;
    $this->counters['order'] = 0;
    $this->counters['select'] = 0;
    $this->counters['params'] = 1;
  }


  /**
   * select field to retreive with the query
   * @param  Array $attributs array of attributs
   * @return $this 
   */
  public function select($attributs = []) {

    if (empty($attributs)) {
      $attributs = "*";
    }
    else {
      $attributs = implode(',', $attributs);
    }
    $this->counters['select'] = 1;
    $this->sql = "SELECT $attributs FROM $this->table";

    return $this;
  }

  /**
   * add an orderby statement to the query string
   * @param  String $attribut attribut to order
   * @param  String $order    order
   * @return $this  
   */
  public function orderBy($attribut, $order = 'ASC') {

    if ($this->counters['select'] < 1) {
      $this->select();
    }

    if ($this->counters['order']++ == 0) {
      $this->sql;
      $this->sql .= " ORDER BY $attribut $order";
    }
    else {
      $this->sql .= ", $attribut $order";
    }

    return $this;
  }

  /**
   * add a where statement to the query string
   * @param  String $attribut attribut to query
   * @param  string $operator Operator
   * @param  mixed $value    value to compare
   * @param  String $logic    when chaining where you 
   * can specify here what logical operation    
   * @return $this 
   */
  public function where($attribut, $operator = '=' , $value, $logic = 'AND') {
    
    if ($this->counters['select'] < 1) {
      $this->select();
    }

    if ($this->counters['where']++ == 0) {
      $this->sql .= " WHERE $attribut $operator ?";
    }
    else {
      $this->sql .= " $logic $attribut $operator ?";
    }
    
    $this->params[$this->counters['params']++] = $value;
    return $this;
  }


  /**
   * Execute the query 
   * @return Array result fetched in object
   */
  public function get() {

    return $this->execute($this->sql)->fetchAll(PDO::FETCH_OBJ);
  }

  /**
   * Select and return a single row
   * @param  int $id    field
   * @param  string $where optional to speceify
   * the column to query
   * @return $this        
   */
  public function find($id, $where = 'id') {
    $this->sql = "SELECT * FROM $this->table WHERE $where = ?";
    $this->params[$this->counters['params']++] = $id;
    return $this->first();
  }

  /**
   * fetch all result
   * @return Object result object
   */
  public function all() {
    $this->sql = "SELECT * FROM $this->table";

    return $this->execute()->fetchAll(PDO::FETCH_OBJ);
  }

  /**
   * execute the current query but retreive
   * only the first result
   * bind attributs to the current object
   * @return $this 
   */
  public function first() {
    $this->execute();
    $this->bindAttributs();
    
    return $this;
  }

  /**
   * Delete the specific row by id
   * @param  int $id    row id
   * @param  string $where optional to speceify
   * the column to query
   * @return $this->execute();
   */
  public function remove($id, $where = 'id') {
    $this->sql = "DELETE FROM $this->table WHERE $where = ?";
    $this->params[$this->counters['params']++] = $id;
    return $this->execute();
  }

  /**
   * count the number of row affected if a query
   * was executed. Or return the number of 
   * row of the table
   * @return int number of row
   */
  public function count() {

    if ($this->statement != NULL) {
      return $this->statement->rowCount();
    }

    $this->sql = "SELECT count(*) FROM $this->table";
    $this->execute();

    return $this->execute()->fetchColumn();
  }

  /**
   * acces to the pdo statement
   * @return Ojbect pdo statement
   */
  public function getStatement() {
    return $this->statement;
  }

  /**
   * protected attribut, they will not be filled
   * when saving an entity
   * @return Array attributs
   */
  protected function getProtectedAttributs() {
    return ['table', 
            'dbh',
            'statement',
            'sql',
            'counters',
            'params',
            'primary_key'];
  }

  /**
   * execute the query
   * bind params and reset the counters
   * @return Object pdo statement
   */
  protected function execute() {
    try {
      $this->statement = $this->dbh->prepare($this->sql);
      for ($i = 1; $i < $this->counters['params']; $i++) {
        $this->statement->bindParam($i, $this->params[$i]);
      }
      $this->statement->execute();

      //reset counters
      $this->counters['order'] = 0;
      $this->counters['where'] = 0;
      $this->counters['select'] = 0;
      $this->counters['params'] = 1;
    }
    catch(PDOException $exception){
      print 'Erreur : '. $exception->getMessage();
    }

    return $this->statement;
  }

  /**
   * save the current entity
   * @return int inserted id
   */
  public function save() {

    if ($this->{$this->primary_key} != NULL) {
      $attributs = $this->getProtectedAttributs();
      //Removed the pk from the update
      $attributs[] = $this->primary_key;
      $this->update($attributs);
      return $this->{$this->primary_key};
    }

    $sql_attr = '';
    $sql_value = '';
    $count = 0;
    foreach ($this as $key => $value) {
      if (!in_array($key, $this->getProtectedAttributs()) && $value != NULL) {
        $sql_attr .= "$key,";
        $count++;
        $this->params[$this->counters['params']++] = $value;
      }
    }
    $sql_attr = substr($sql_attr, 0, -1);

    for ($i = 0; $i < $count; $i++) {
      $sql_value .= '?,';
    }
    $sql_value = substr($sql_value, 0, -1);
    $this->sql = "INSERT INTO $this->table ($sql_attr) VALUES ($sql_value)";
    $this->execute();

    return $this->dbh->lastInsertId();
  }

  /**
   * update the specific attribut of an entity
   * @param  Array $attributs attributs to update
   * @return $this->execute()  
   */
  public function update($attributs = []) {

    if ($this->{$this->primary_key} === NULL) {
      return false;
    }

    $this->sql = "UPDATE $this->table SET ";
    $sql_attr = '';
    foreach ($this as $key => $value) {
      if (!in_array($key, $attributs) && $value != NULL) {
        $sql_attr .= "$key = ?,";
        $this->{$key} = $value;
        $this->params[$this->counters['params']++] = $value;
      }
    }
    $this->sql .= substr($sql_attr, 0, -1);
    $this->counters['select']++; //quick hack
    $this->where($this->primary_key, '=', $this->id );
    $this->execute();
  }

  /**
   * return the current SQL string
   * @return String sql string
   */
  public function getSql() {
    return $this->sql;
  }

  /**
   * link a entity with another has a
   * "hasMany" relations
   * when no fk provided the default used 
   * is tablename_id
   * @param  Object  $table object table to bin
   * @param  String  $foreign_key    foreign key
   * @param  string  $primary_key    primary key default is id
   * @return Object        entity
   */
  protected function hasMany($table, $foreign_key = null, $primary_key = null) {
    $foreign_key = $foreign_key == NULL ? strtolower(get_class($this)) . '_id' : $foreign_key;
    $primary_key = $primary_key == NULL ? $this->id : $this->{$primary_key};
    return $table->where($foreign_key, '=', $primary_key);
  }

  /**
   * link a entity with another has a
   * "belongsTo" relations
   * when no fk provided the default used 
   * is tablename_id
   * @param  Object  $table object table to bin
   * @param  String  $foreign_key    foreign key
   * @param  string  $primary_key    primary key default is id
   * @return Object        entity
   */
  protected function belongsTo($table, $foreign_key = null, $primary_key = 'id') {
    $foreign_key = $foreign_key == NULL ? strtolower(get_class($table)) . '_id' : $foreign_key;
    return $table->where($primary_key, '=', $this->{$foreign_key});
  }

  /**
   * bind attributs from a pdo result
   * @return void 
   */
  protected function bindAttributs() {
    $fetch = $this->statement->fetch(PDO::FETCH_OBJ);
    if ($fetch !== FALSE) {
      foreach ($fetch as $key => $value) {
        $this->{$key} = $value;
      }
    }
  }



}