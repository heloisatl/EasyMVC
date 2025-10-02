<?php
ini_set('display_errors', 1);
ini_set('display_startup_erros', 1);
error_reporting(E_ALL);
class Creator
{
    private $con;
    private $servidor;
    private $banco;
    private $usuario;
    private $senha;
    private $tabelas;

    function __construct()
    {
        if (isset($_GET['id']))
            $this->buscaBancodeDados();
        else {
            $this->criaDiretorios();
            $this->conectar(1);
            $this->buscaTabelas();
            $this->gerarpaginaInicial();
            $this->ClassesModel();
            $this->ClasseConexao();
            $this->ClassesControl();
            $this->classesView();
            $this->ClassesDao();

            $this->compactar();
            header("Location:index.php?msg=2");
        }
    } //fimConsytruct
    function criaDiretorios()
    {

        $dirs = [
            "sistema",
            "sistema/model",
            "sistema/control",
            "sistema/view",
            "sistema/dao",
            "sistema/css"
        ];
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    header("Location:index.php?msg=0");
                }
            }
        }
        copy('estilos.css', 'sistema/css/estilos.css');
    } //fimDiretorios
    function conectar($id)
    {
        $this->servidor = $_REQUEST["servidor"];
        $this->usuario = $_REQUEST["usuario"];
        $this->senha = $_REQUEST["senha"];
        if ($id == 1) {
            $this->banco = $_POST["banco"];
        } else {
            $this->banco = "mysql";
        }
        try {
            $this->con = new PDO(
                "mysql:host=" . $this->servidor . ";dbname=" . $this->banco,
                $this->usuario,
                $this->senha
            );
        } catch (Exception $e) {

            header("Location:index.php?msg=1");
        }
    } //fimConectar
    function buscaBancodeDados()
    {
        try {
            $this->conectar(0);
            $sql = "SHOW databases";
            $query = $this->con->query($sql);
            $databases = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach ($databases as $database) {
                echo "<option>" . $database["Database"] . "</option>";
            }
            $this->con = null;
        } catch (Exception $e) {
            header("Location:index.php?msg=3");
        }
    } //BuscaBD
    function buscaTabelas()
    {
        try {
            $sql = "SHOW TABLES";
            $query = $this->con->query($sql);
            $this->tabelas = $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            header("Location:index.php?msg=3");
        }
    } //fimBuscaTabelas
    function buscaAtributos($nomeTabela)
    {
        $sql = "show columns from " . $nomeTabela;
        $atributos = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
        return $atributos;
    } //fimBuscaAtributos
    function ClassesModel()
    {
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array) $tabela)[0];
            $atributos = $this->buscaAtributos($nomeTabela);
            $nomeAtributos = "";
            $geters_seters = "";
            foreach ($atributos as $atributo) {
                $atributo = $atributo->Field;
                $nomeAtributos .= "\tprivate \${$atributo};\n";
                $metodo = ucfirst($atributo);
                $geters_seters .= "\tfunction get" . $metodo . "(){\n";
                $geters_seters .= "\t\treturn \$this->{$atributo};\n\t}\n";
                $geters_seters .= "\tfunction set" . $metodo . "(\${$atributo}){\n";
                $geters_seters .= "\t\t\$this->{$atributo}=\${$atributo};\n\t}\n";
            }
            $nomeClasse = ucfirst($nomeTabela);
            $conteudo = <<<EOT
<?php
class {$nomeClasse} {
{$nomeAtributos}
{$geters_seters}
}
?>
EOT;
            file_put_contents("sistema/model/{$nomeTabela}.php", $conteudo);
        }
    } //fimModel
    function ClasseConexao()
    {
        $conteudo = <<<EOT

<?php
class Conexao {
    private \$server;
    private \$banco;
    private \$usuario;
    private \$senha;
    function __construct() {
        \$this->server = '{$this->servidor}';
        \$this->banco = '{$this->banco}';
        \$this->usuario = '{$this->usuario}';
        \$this->senha = '{$this->senha}';
    }
    
    function conectar() {
        try {
            \$conn = new PDO(
                "mysql:host=" . \$this->server . ";dbname=" . \$this->banco,\$this->usuario,
                \$this->senha
            );
            return \$conn;
        } catch (Exception \$e) {
            echo "Erro ao conectar com o Banco de dados: " . \$e->getMessage();
        }
    }
}
?>
EOT;
        file_put_contents("sistema/model/conexao.php", $conteudo);
    } //fimConexao
    function ClassesControl()
    {
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array)$tabela)[0];
            $atributos = $this->buscaAtributos($nomeTabela);
            $nomeClasse = ucfirst($nomeTabela);
            $posts = "";
            foreach ($atributos as $atributo) {
                if ($atributo->Key == "PRI")
                    continue;

                $atributo = $atributo->Field;
                $posts .= "\$this->{$nomeTabela}->set" . ucFirst($atributo) .
                    "(\$_POST['{$atributo}']);\n\t\t";
            }

            $conteudo = <<<EOT
<?php
require_once("../model/{$nomeTabela}.php");
require_once("../dao/{$nomeTabela}Dao.php");
class {$nomeClasse}Control {
    private \${$nomeTabela};
    private \$acao;
    private \$dao;
    public function __construct(){
       \$this->{$nomeTabela}=new {$nomeClasse}();
      \$this->dao=new {$nomeClasse}Dao();
      \$this->acao=\$_GET["a"];
      \$this->verificaAcao(); 
    }
    function verificaAcao(){
       switch(\$this->acao){
          case 1:
            \$this->inserir();
          break;
          case 2:
            \$this->excluir();
          break;
        case 3:
            \$this->alterar();
          break;
       }
    }
  
    function inserir(){
        {$posts}
        \$this->dao->inserir(\$this->{$nomeTabela});
    }
    function excluir(){
        \$this->dao->excluir(\$_REQUEST['id']);
    }
    function alterar(){
        {$posts}
        \$this->dao->alterar(\$this->{$nomeTabela}, \$_REQUEST['id']);
    }
    function buscarId({$nomeClasse} \${$nomeTabela}){}

}
new {$nomeClasse}Control();
?>
EOT;
            file_put_contents("sistema/control/{$nomeTabela}Control.php", $conteudo);
        }
    } //fimControl
    function compactar()
    {
        $folderToZip = 'sistema';
        $outputZip = 'sistema.zip';
        $zip = new ZipArchive();
        if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return false;
        }
        $folderPath = realpath($folderToZip);  // Corrigido aqui
        if (!is_dir($folderPath)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folderPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        return $zip->close();
    } //fimCompactar
    function ClassesDao()
    {
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array)$tabela)[0];
            $nomeClasse = ucfirst($nomeTabela);
            $atributos = $this->buscaAtributos($nomeTabela);
            $id = "";
            foreach ($atributos as $atributo) {
                if ($atributo->Key == "PRI")
                    $id = $atributo->Field;
            }
            $atributos = array_map(function ($obj) {
                if ($obj->Key == "PRI") {
                    return null;
                }
                return $obj->Field;
            }, $atributos);

            $atributos = array_filter($atributos, function ($valor) {
                return !is_null($valor);
            });
            $sqlCols = implode(', ', $atributos);
            $placeholders = implode(', ', array_fill(0, count($atributos), '?'));
            // Para UPDATE: campo1=?, campo2=? ....... 
            $sqlUpdate = implode('=?, ', $atributos) . '=?';
            $vetAtributos = [];
            $AtributosMetodos = "";

            foreach ($atributos as $atributo) {

                $atr = ucfirst($atributo);
                array_push($vetAtributos, "\${$atributo}");
                $AtributosMetodos .= "\${$atributo}=\$obj->get{$atr}();\n";
            }
            $atributosOk = implode(",", $vetAtributos);
            $conteudo = <<<EOT
<?php
require_once("../model/conexao.php");
class {$nomeClasse}Dao {
    private \$con;
    public function __construct(){
       \$this->con=(new Conexao())->conectar();
    }
function inserir(\$obj) {
    \$sql = "INSERT INTO {$nomeTabela} ({$sqlCols}) VALUES ({$placeholders})";
    \$stmt = \$this->con->prepare(\$sql);
    {$AtributosMetodos}
    \$stmt->execute([{$atributosOk}]);
    header("Location:../view/lista{$nomeClasse}.php");
}
    function alterar(\$obj, \$idValue){
    \$sql = "UPDATE {$nomeTabela} SET {$sqlUpdate} WHERE {$id}=?";
    \$stmt = \$this->con->prepare(\$sql);
    {$AtributosMetodos}
    \$stmt->execute([{$atributosOk}, \$idValue]);
    header("Location:../view/lista{$nomeClasse}.php");
}
function listaGeral(){
    \$sql = "select * from {$nomeTabela}";
    \$query = \$this->con->query(\$sql);
    \$dados = \$query->fetchAll(PDO::FETCH_ASSOC);
    return \$dados;
}
function excluir(\$id){
    \$sql = "delete from {$nomeTabela} where {$id}=\$id";
    \$query = \$this->con->query(\$sql);
    header("Location:../view/lista{$nomeClasse}.php");
}
function buscaPorId(\$id){
    \$sql = "select * from {$nomeTabela} where {$id}=\$id";
    \$query = \$this->con->query(\$sql);
    \$dados = \$query->fetch(PDO::FETCH_ASSOC);
    return \$dados;
}

}
?>
EOT;
            file_put_contents("sistema/dao/{$nomeTabela}Dao.php", $conteudo);
        }
    } //fimDao

    function verificacao($tipo)
    {
        if ($tipo->Key == "PRI") {
            $tipofinal = "hidden";
        } else {

            if ($tipo->Type == "int") {
                $tipofinal = "number";
            } else {
                $tipofinal = "text";
            }
        }

        return $tipofinal;
    }

    function classesView()
    {
        //formulários
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array) $tabela)[0];
            $atributos = $this->buscaAtributos($nomeTabela);
            $formCampos = "";
            foreach ($atributos as $atributo) {
                $tipo = $this->verificacao($atributo);
                $campo = $atributo->Field;
                $labelTexto = ucfirst(str_replace('_', ' ', $campo));

                if ($tipo == "hidden") {
                    $formCampos .= "\n<input type='hidden' id='{$campo}' name='{$campo}' value='<?php echo \$obj?\$obj['{$campo}']:''; ?>'>\n";
                } else {
                    $required = ($tipo != "hidden") ? "required" : "";
                    $formCampos .= "\n<div class=\"campo-grupo\">\n";
                    $formCampos .= "<label for='{$campo}'>{$labelTexto}</label>\n";
                    $formCampos .= "<input type='{$tipo}' id='{$campo}' name='{$campo}' value='<?php echo \$obj?\$obj['{$campo}']:''; ?>' {$required}>\n";
                    $formCampos .= " </div>\n";
                }
            }
            $conteudo = <<<HTML
<?php
    require_once('../dao/{$nomeTabela}Dao.php');
    \$obj=null;
    if(isset(\$_GET['id']))
        \$obj=(new {$nomeTabela}Dao())->buscaPorId(\$_GET['id']);

    \$acao=\$obj? 3:1; 
    \$nome = \$acao == 1 ? "Cadastrar" : "Editar";
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <title><?= \$nome ?> de {$nomeTabela}</title>
        <link rel="stylesheet" href="../../estilos.css">
    </head>
    <body class="form-page">
        <div class="container">
            <form id="form-{$nomeTabela}" class="" action="../control/{$nomeTabela}Control.php?a=<?php echo \$acao ?><?php if(isset(\$_GET['id'])) {echo '&id='.\$_GET['id'];} ?>" method="post">
                <h2><?= \$nome ?> {$nomeTabela}</h2>
                {$formCampos}
                <button type="submit" class="btn-submit"><?= \$acao == 1 ? 'Cadastrar' : 'Atualizar' ?></button>
            </form>
        </div>
    </body>
</html>
HTML;
            file_put_contents("sistema/view/{$nomeTabela}.php", $conteudo);
        }
        //Listas
        //Listas
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array)$tabela)[0];
            $nomeTabelaUC = ucfirst($nomeTabela);
            $atributos = $this->buscaAtributos($nomeTabela);
            $attr = "";
            $id = "";
            foreach ($atributos as $atributo) {
                if ($atributo->Key == "PRI")
                    $id = "{\$dado['{$atributo->Field}']}";

                $attr .= "echo \"<td>{\$dado['{$atributo->Field}']}</td>\";\n";
            }
            $cabecalhos = "";
            foreach ($atributos as $atributo) {
                $labelCabecalho = ucfirst(str_replace('_', ' ', $atributo->Field));
                $cabecalhos .= "        echo \"<th>{$labelCabecalho}</th>\";\n";
            }

            $conteudo = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head> 
    <title>Lista de {$nomeTabela}</title>
    <link rel="stylesheet" href="../../estilos.css">
</head>
<body class="form-page">
    <div class="tabela-container">
        <h2>Lista de {$nomeTabela}</h2>
        <a href="../view/{$nomeTabela}.php" class="btn">+ Novo {$nomeTabela}</a>
        
        <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <?php
                        {$cabecalhos}
                        echo "<th>Ações</th>";
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                    require_once("../dao/{$nomeTabela}Dao.php");
                    \$dao = new {$nomeTabela}DAO();
                    \$dados = \$dao->listaGeral();

                    foreach(\$dados as \$dado) {
                        echo "<tr>";
                            {$attr}
                        echo "<td>";
                        echo "<a href='../view/{$nomeTabela}.php?id={$id}'>Alterar</a> | ";
                        echo "<a href='../control/{$nomeTabela}Control.php?id={$id}&a=2' 
                                 onclick='return confirm(\"Tem certeza que quer excluir esse campo? Essa ação não tem volta!\")'>
                                 Excluir</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;


            file_put_contents("sistema/view/lista{$nomeTabelaUC}.php", $conteudo);
        }
    } //fimView
    function gerarPaginaInicial()
{
    $menuInserir = "";
    $menuListar = "";

    foreach ($this->tabelas as $tabela) {
        $nomeTabela = array_values((array)$tabela)[0];
        $nomeTabelaUC = ucfirst($nomeTabela);

        $menuInserir .= "<a href='./view/{$nomeTabelaUC}.php' class='dropdown-link' target='iframe'>{$nomeTabelaUC}</a>\n";
        $menuListar .= "<a href='./view/lista{$nomeTabelaUC}.php' class='dropdown-link' target='iframe'>{$nomeTabelaUC}</a>\n";
    }

    $conteudo = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Menu Principal</title>
    <link rel="stylesheet" href="./css/estilos.css">
</head>
<body>
    <header class="header-container">
        <nav class="navbar">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link">Inserir</a>
                    <div class="dropdown">
                        {$menuInserir}
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">Listar</a>
                    <div class="dropdown">
                        {$menuListar}
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <section class="iframe-container">
            <iframe id="contentFrame" name="iframe" src="index.html" frameborder="0"></iframe>
        </section>
    </main>
</body>
</html>
HTML;

    file_put_contents("sistema/index.php", $conteudo);

    $paginaInicio = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>EASYMVC</title>
    <link rel="stylesheet" href="./css/estilos.css">
</head>
<body class="inicio-page">
    <div class="welcome-container">
        <h1>Bem-vindo!</h1>
        <p class="instruction-text">
            <strong>Como usar:</strong> Selecione uma opção no menu acima para começar a gerenciar seu sistema!
        </p>
    </div>
</body>
</html>
HTML;

        file_put_contents("sistema/index.html", $paginaInicio);
    }
}
new Creator();
