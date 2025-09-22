<?php

/**
 * Installation script for AdminLTE IP Logger
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Check if already installed
if (file_exists('installed.txt')) {
    die('Sistema já foi instalado. Delete o arquivo installed.txt para reinstalar.');
}

$error = '';
$success = '';

if ($_POST) {
    try {
        // Initialize database connection
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            throw new Exception('Não foi possível conectar ao banco de dados');
        }

        // Create tables
        if ($db->createTables()) {
            $success = 'Tabelas criadas com sucesso!';

            // Create installed flag
            file_put_contents('installed.txt', date('Y-m-d H:i:s'));

            // Redirect to dashboard
            header('Location: pages/dashboard/');
            exit;
        } else {
            $error = 'Erro ao criar tabelas';
        }
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Instalação - IP Logger AdminLTE</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
    <link rel="stylesheet" href="dist/font-awesome/css/font-awesome.min.css">
</head>

<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">
        <div class="content-wrapper">
            <section class="content">
                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Instalação do IP Logger AdminLTE</h3>
                            </div>
                            <div class="box-body">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger">
                                        <i class="fa fa-warning"></i> <?php echo $error; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success">
                                        <i class="fa fa-check"></i> <?php echo $success; ?>
                                    </div>
                                <?php endif; ?>

                                <h4>Configuração do Banco de Dados</h4>
                                <p>Verifique se as configurações do banco de dados estão corretas no arquivo <code>config/config.php</code>:</p>

                                <ul>
                                    <li><strong>Host:</strong> <?php echo DB_HOST; ?></li>
                                    <li><strong>Database:</strong> <?php echo DB_NAME; ?></li>
                                    <li><strong>User:</strong> <?php echo DB_USER; ?></li>
                                </ul>

                                <h4>Requisitos do Sistema</h4>
                                <ul>
                                    <li>PHP 7.4 ou superior</li>
                                    <li>MySQL 5.7 ou superior</li>
                                    <li>Extensões PHP: PDO, PDO_MySQL, JSON, cURL</li>
                                    <li>Mod_rewrite habilitado (Apache)</li>
                                </ul>

                                <h4>Verificação de Extensões</h4>
                                <ul>
                                    <li>PDO: <?php echo extension_loaded('pdo') ? '<span class="text-green">✓</span>' : '<span class="text-red">✗</span>'; ?></li>
                                    <li>PDO MySQL: <?php echo extension_loaded('pdo_mysql') ? '<span class="text-green">✓</span>' : '<span class="text-red">✗</span>'; ?></li>
                                    <li>JSON: <?php echo extension_loaded('json') ? '<span class="text-green">✓</span>' : '<span class="text-red">✗</span>'; ?></li>
                                    <li>cURL: <?php echo extension_loaded('curl') ? '<span class="text-green">✓</span>' : '<span class="text-red">✗</span>'; ?></li>
                                </ul>

                                <form method="POST">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fa fa-download"></i> Instalar Sistema
                                        </button>
                                    </div>
                                </form>

                                <div class="alert alert-info">
                                    <h4><i class="icon fa fa-info"></i> Informação Importante</h4>
                                    <p>Após a instalação, o sistema estará pronto para uso. Certifique-se de:</p>
                                    <ul>
                                        <li>Alterar a senha padrão do administrador</li>
                                        <li>Configurar as chaves de API (Google Maps, etc.)</li>
                                        <li>Testar a funcionalidade de redirecionamento</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>

</html>