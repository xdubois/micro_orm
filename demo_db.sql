CREATE TABLE client (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nom varchar(50),
  prenom varchar(50),
  ville varchar(100)
);

CREATE TABLE compte (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  solde float,
  taux float,
  client_id int
);

ALTER TABLE compte
  ADD FOREIGN KEY (client_id) REFERENCES client(id);


