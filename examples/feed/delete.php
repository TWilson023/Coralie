<?php

require __DIR__ . "/../../vendor/autoload.php";

$conn = Coralie\Connections\MySqlConnection::getInstance(
		require "settings.php");

// Update article
$success = $conn->table('articles')->delete('id', $_GET['id'])->execute();

if ($success)
	header("Location: index.php");
else
	echo "Failed to delete article.";