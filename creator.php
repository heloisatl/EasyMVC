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
       }
    }
  
    function inserir(){
        {$posts}
        \$this->dao->inserir(\$this->{$nomeTabela});
    }
    function excluir(){
        \$this->dao->excluir(\$_REQUEST['id']);
    }
    function alterar(){}
    function buscarId({$nomeClasse} \${$nomeTabela}){}
    function buscaTodos(){}

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
                return $obj->Field;
            }, $atributos);
            $sqlCols = implode(', ', $atributos);
            $placeholders = implode(', ', array_fill(0, count($atributos), '?'));
            $vetAtributos = [];
            $AtributosMetodos = "";

            foreach ($atributos as $atributo) {
                //$id=$atributos[0];
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
    
}
?>
EOT;
            file_put_contents("sistema/dao/{$nomeTabela}Dao.php", $conteudo);
        }
    } //fimDao
    
    function classesView()
{
    $links = "";

    // Primeiro, geramos os formulários e listas
    foreach ($this->tabelas as $tabela) {
        $nomeTabela = array_values((array) $tabela)[0];
        $nomeTabelaUC = ucfirst($nomeTabela);
        $atributos = $this->buscaAtributos($nomeTabela);

        // Gera os campos do formulário
        $formCampos = "";
        foreach ($atributos as $atributo) {
            $atributo = $atributo->Field;
            $formCampos .= "
            <div class='mb-4'>
                <label for='{$atributo}' class='block text-gray-700 text-sm font-bold mb-2'>{$atributo}</label>
                <input type='text' name='{$atributo}' class='shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline'>
            </div>";
        }

        // Conteúdo do formulário (sem HTML completo)
        $conteudoForm = <<<HTML
<div class='max-w-md mx-auto bg-white rounded-lg shadow-md p-6'>
    <h1 class='text-2xl font-bold text-gray-800 mb-6 text-center'>Cadastro de {$nomeTabela}</h1>
    <form action="../control/{$nomeTabela}Control.php?a=1" method="post">
        {$formCampos}
        <div class='flex space-x-4'>
            <button type="submit" class='bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1'>
                Salvar
            </button>
            <button type="button" onclick="voltarMenu()" class='bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded flex-1'>
                Voltar
            </button>
        </div>
    </form>
</div>
HTML;
        file_put_contents("sistema/view/{$nomeTabela}.php", $conteudoForm);

        // Gera lista
        $attr = "";
        $id = "";
        foreach ($atributos as $atributo) {
            if ($atributo->Key == "PRI")
                $id = "{\$dado['{$atributo->Field}']}";

            $attr .= "echo \"<td class='py-2 px-4 border-b'>{\$dado['{$atributo->Field}']}</td>\";\n";
        }

        $conteudoLista = <<<HTML
<div class='max-w-6xl mx-auto bg-white rounded-lg shadow-md p-6'>
    <h1 class='text-2xl font-bold text-gray-800 mb-6 text-center'>Lista de {$nomeTabela}</h1>
    <?php
    require_once("../dao/{$nomeTabela}Dao.php");
    \$dao = new {$nomeTabela}DAO();
    \$dados = \$dao->listaGeral();
    
    if (!empty(\$dados)) {
        echo "<div class='overflow-x-auto'>";
        echo "<table class='min-w-full bg-white rounded-lg overflow-hidden'>";
        echo "<thead class='bg-gray-800 text-white'>";
        echo "<tr>";
        foreach(\$dados[0] as \$key => \$value) {
            echo "<th class='py-3 px-4 text-left'>".ucfirst(\$key)."</th>";
        }
        echo "<th class='py-3 px-4 text-left'>Ações</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody class='text-gray-700'>";
        
        foreach(\$dados as \$index => \$dado) {
            \$rowClass = \$index % 2 === 0 ? 'bg-gray-50' : 'bg-white';
            echo "<tr class='{\$rowClass} hover:bg-gray-100'>";
            {$attr}
            echo "<td class='py-2 px-4 border-b'>";
            echo "<a href='../control/{$nomeTabela}Control.php?id={$id}&a=2' class='bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm mr-2' onclick='return confirm(\\\`Confirma exclusão?`\\\)'>Excluir</a>";
            echo "<button class='bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm'>Alterar</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<p class='text-gray-600 text-center'>Nenhum registro encontrado.</p>";
    }
    ?>
    <div class='mt-6 flex space-x-4 justify-center'>
        <button onclick="carregarPagina('{$nomeTabela}.php')" class='bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded'>
            Novo Cadastro
        </button>
        <button onclick="voltarMenu()" class='bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded'>
            Voltar ao Menu
        </button>
    </div>
</div>
HTML;
        file_put_contents("sistema/view/lista{$nomeTabelaUC}.php", $conteudoLista);

        // Adiciona botões ao menu lateral
        $links .= "
            <button onclick=\"carregarPagina('{$nomeTabela}.php')\">📝 Cadastrar {$nomeTabelaUC}</button>
            <button onclick=\"carregarPagina('lista{$nomeTabelaUC}.php')\">📋 Listar {$nomeTabelaUC}</button>
        ";
    }

    // Gera o index.php fixo
    $conteudoIndex = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>EasyMVC - Sistema</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { display: flex; height: 100vh; margin: 0; }
        nav { width: 250px; background: #127078; padding: 20px; color: white; }
        nav button { display: block; margin-bottom: 10px; padding: 10px; width: 100%;
                     text-align: left; background: #1da1ad; border-radius: 5px; }
        nav button:hover { background: #0c4c4f; }
        #conteudo { flex: 1; padding: 20px; overflow-y: auto; background: #f0f0f0; }
    </style>
</head>
<body>
    <nav>
        <h2 class="text-xl font-bold mb-4">Menu</h2>
        {$links}
    </nav>

    <div id="conteudo">
        <h1 class="text-2xl font-bold">Bem-vindo ao sistema!</h1>
        <p>Selecione uma opção no menu.</p>
    </div>

    <script>
        function carregarPagina(url) {
            fetch(url)
                .then(res => res.text())
                .then(html => { document.getElementById('conteudo').innerHTML = html; })
                .catch(() => { document.getElementById('conteudo').innerHTML = "<p>Erro ao carregar.</p>"; });
        }
        function voltarMenu() {
            document.getElementById('conteudo').innerHTML = "<h1 class='text-2xl font-bold'>Bem-vindo ao sistema!</h1><p>Selecione uma opção no menu.</p>";
        }
    </script>
</body>
</html>
HTML;
    file_put_contents("sistema/view/index.php", $conteudoIndex);
}

}
new Creator();
