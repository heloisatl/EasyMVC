<?php
$mensagens = [
    'Erro 0: Não foi possível criar diretórios',
    'Erro 1: Falha ao conectar ao banco de dados',
    'Projeto criado: faça download clicando <a href="sistema.zip">aqui</a>',
    'Erro 2: Não foi possível obter as tabelas'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>EasyMVC - Sistema</title>
    <link rel="stylesheet" href="estilos.css">
    <script src="js/funcoes.js"></script>
</head>
<body class="container">

    <h1 class="title">EasyMVC - Sistema</h1>

    <div class="container">
        <!-- Mensagens do sistema -->
        <?php
        if (isset($_GET['msg'])) {
            $msg = $_GET['msg'];
            $classe = $msg == 2 ? 'mensagem' : 'mensagem_erro';
            echo "<div class=\"$classe\">"  . ($mensagens[$msg] ?? "Erro desconhecido") . "</div>";
        }
        ?>

        <!-- Formulário de conexão -->
        <form action="creator.php" method="POST">
            <h2>Configuração</h2>

            <div class="caixa">
                <label for="servidor">Servidor:</label>
            </div>
            <div class="caixa">
                <input type="text" id="servidor" name="servidor" required> 
            </div>

            <div class="caixa">
                <label for="usuario">Usuário:</label>
            </div>
            <div class="caixa">
                <input type="text" id="usuario" name="usuario" required>
            </div>

            <div class="caixa">
                <label for="senha">Senha:</label>
            </div>
            <div class="caixa">
                <input type="password" id="senha" name="senha" onblur="carregarBanco()">
            </div>

            <div class="caixa">
                <label for="banco">Banco de Dados:</label>
            </div>
            <div class="caixa">
                <select id="banco" name="banco" required></select>
            </div>

            <label id="carregando"></label>
            <button type="submit">Enviar</button>
        </form>
    </div>

    <hr>

  

    

</body>
</html>
