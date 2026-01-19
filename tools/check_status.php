<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Votações</h2>";
$stmt = $pdo->query("SELECT * FROM votacoes");
$votacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($votacoes)) {
    echo "Nenhuma votação encontrada no banco.<br>";
} else {
    echo "<table border='1'><tr><th>ID</th><th>Titulo</th><th>Status</th></tr>";
    foreach ($votacoes as $v) {
        echo "<tr>";
        echo "<td>" . $v['id'] . "</td>";
        echo "<td>" . htmlspecialchars($v['titulo']) . "</td>";
        echo "<td>" . $v['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
