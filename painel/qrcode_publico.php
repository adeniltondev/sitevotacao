<?php
// Gera QR Code público para o painel de resultados
require_once '../config/functions.php';

$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/resultados.php';

// Gera QR Code usando Google Chart API
$qr_url = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($url) . '&choe=UTF-8';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - Painel Público</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.getItem('darkMode') === '1' ||
                (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        .dark body { background: #18181b !important; color: #f3f4f6 !important; }
        .dark .bg-white { background: #23232a !important; color: #f3f4f6 !important; }
        .dark .text-gray-800 { color: #f3f4f6 !important; }
        .dark .text-gray-600 { color: #d1d5db !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 flex flex-col items-center">
        <h1 class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-4">Acesse o Painel Público</h1>
        <img src="<?= $qr_url ?>" alt="QR Code do Painel Público" class="mb-4 w-64 h-64">
        <div class="text-gray-700 dark:text-gray-200 text-center mb-2">Escaneie o QR Code para acessar o painel de resultados em tempo real.</div>
        <a href="<?= htmlspecialchars($url) ?>" class="text-blue-600 dark:text-blue-400 underline break-all"><?= htmlspecialchars($url) ?></a>
    </div>
</body>
</html>
