<!DOCTYPE html>

<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>EasyMVC - Sistema</title>
    <link rel="stylesheet" href="estilos.css">
    <script src="js/funcoes.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }

    .container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.25);
        padding: 30px 40px;
        max-width: 400px;
        width: 100%;
        text-align: center;
    }

    h1 {
        font-size: 28px;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    h2 {
        font-size: 18px;
        color: #7f8c8d;
        margin-bottom: 20px;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    label {
        font-weight: bold;
        text-align: left;
        color: #34495e;
    }

    input, select {
        padding: 10px;
        border: 1px solid #bdc3c7;
        border-radius: 6px;
        font-size: 14px;
        outline: none;
        transition: border 0.3s;
    }

    input:focus, select:focus {
        border-color: #2980b9;
    }

    button {
        background: #2980b9;
        color: #fff;
        padding: 12px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }

    button:hover {
        background: #3498db;
    }

    .mensagem {
        background: #dff0d8;
        color: #3c763d;
        border: 1px solid #d6e9c6;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .mensagem_erro {
        background: #f2dede;
        color: #a94442;
        border: 1px solid #ebccd1;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    #carregando {
        font-size: 13px;
        color: #7f8c8d;
        text-align: left;
    }
</style>


</head>
<body>
<div class="container">
    <form action="creator.php" method="POST">
        <?php
        include 'mensagens.php';
        if (isset($_GET['msg']) ){
            $msg=$_GET['msg'];
            $classe=$msg==2?'mensagem':'mensagem_erro';
            echo "<div class=\"$classe\">"  . ($mensagens[$msg] ?? "Erro desconhecido") . "</div>";
        }
        ?>
        <h1>EasyMVC</h1>
        <h2>Configuração de Conexão</h2>


    <label for="servidor">Servidor:</label>
    <input type="text" id="servidor" name="servidor" placeholder="Ex: localhost" required>

    <label for="usuario">Usuário:</label>
    <input type="text" id="usuario" name="usuario" placeholder="Usuário do banco" required>

    <label for="senha">Senha:</label>
    <input type="password" id="senha" name="senha" placeholder="Senha do banco" onblur="carregarBanco()">

    <label for="banco">Banco de Dados:</label>
    <select id="banco" name="banco" required>
    </select>

    <label id="carregando"></label>

    <button type="submit">Conectar</button>
</form>
```

</div>
</body>
</html>
