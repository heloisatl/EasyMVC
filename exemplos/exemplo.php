<?php
/*echo"<hr> <h1>Exemplo 1</h1>";

   $tabela=[["nome"=>"Adalberto","idade"=>"20"],
            ["nome"=>"Asdrubaldo","idade"=>"30"]];
   echo $tabela["nome"];
   echo"<hr>";
   foreach ($tabela as $dado){
       $indice=array_values((array)$dado);
       echo $indice[0];
   }*/

 echo"<hr> <h1>Exemplo 2</h1>";

$dados = [
    (object) ["Field" => "id",    "Type" => "int(11)"],
    (object) ["Field" => "nome",  "Type" => "varchar(100)"],
    (object) ["Field" => "idade", "Type" => "int(3)"]
];

$atributos = array_map(function($obj) {
    return $obj->Field;
}, $dados);
//["id","nome","idade"] => id,nome,idade
$str = implode(",",$atributos);
echo $str;

$placeholders=implode(",",array_fill(0,3,'?'));
echo "<br>".$placeholders;