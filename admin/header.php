<?php
iniciarSessao();
$admin_nome = $_SESSION['admin_nome'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, html { font-family: 'Inter', sans-serif !important; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
<header class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-40 border-b border-gray-100 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-20">
        <!-- Logo -->
        <div class="flex items-center gap-2">
            <span class="text-2xl font-bold text-green-600">Vota<span class="text-blue-600">Câmara</span></span>
        </div>
        <!-- Menu -->
        <nav class="flex gap-2 md:gap-4">
            <a href="dashboard.php" class="px-4 py-2 rounded-full font-semibold transition text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 hover:text-blue-700 dark:hover:text-blue-400 <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-blue-600 text-white dark:bg-blue-500 dark:text-white' : '' ?>">Dashboard</a>
            <a href="eleitores.php" class="px-4 py-2 rounded-full font-semibold transition text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 hover:text-blue-700 dark:hover:text-blue-400 <?= basename($_SERVER['PHP_SELF']) === 'eleitores.php' ? 'bg-blue-600 text-white dark:bg-blue-500 dark:text-white' : '' ?>">Eleitores</a>
            <a href="historico.php?cpf=" class="px-4 py-2 rounded-full font-semibold transition text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 hover:text-blue-700 dark:hover:text-blue-400 <?= basename($_SERVER['PHP_SELF']) === 'historico.php' ? 'bg-blue-600 text-white dark:bg-blue-500 dark:text-white' : '' ?>">Histórico</a>
            <a href="../painel/resultados.php" target="_blank" class="px-4 py-2 rounded-full font-semibold transition text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900 hover:text-blue-700 dark:hover:text-blue-400">Painel Público</a>
        </nav>
        <!-- Avatar e Sair -->
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-lg">
                    <?= strtoupper(mb_substr($admin_nome, 0, 1, 'UTF-8')) ?>
                </div>
                <span class="hidden md:block text-gray-800 dark:text-gray-100 font-semibold text-sm">
                    <?= htmlspecialchars($admin_nome) ?>
                </span>
            </div>
            <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition font-semibold">Sair</a>
        </div>
    </div>
</header>
