<?php
$tabelas=[["nome_tabela"=>"cidade"],
         ["nome_tabela"=>"estado"]];
$atributos_cidade=[
    (object) ["Field" => "id",    "Type" => "int(11)"],
    (object) ["Field" => "nome",  "Type" => "varchar(100)"],
    (object) ["Field" => "idEstado", "Type" => "int(3)"],
    (object) ["Field" => "habitantes", "Type" => "int(5)"],
];
$atributos_estado=[
    (object) ["Field" => "id",    "Type" => "int(11)"],
    (object) ["Field" => "nome",  "Type" => "varchar(100)"],
    (object) ["Field" => "sigla", "Type" => "varchar(2)"]
];

/*
   $sql= insert into cidade (nome,idEstado,habitantes) values (?,?,?);
   $stmt=$this->con->prepare($sql);
   $stmt->bindValue(1, $obj->getNome());
   $stmt->bindValue(2, $obj->getIdEstado());
   $stmt->bindValue(3, $obj->getSigla());
   $stmt->execute();

*/