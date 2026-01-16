<?php
/**
 * Logout de Eleitores
 */
require_once '../config/functions.php';

iniciarSessao();

// Destruir sessão do eleitor
unset($_SESSION['eleitor_id']);
unset($_SESSION['eleitor_cpf']);
unset($_SESSION['eleitor_nome']);
unset($_SESSION['eleitor_cargo']);
unset($_SESSION['eleitor_foto']);

// Redirecionar para login
header('Location: login.php');
exit;
