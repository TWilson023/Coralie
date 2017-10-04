<?php

require __DIR__ . "/../../vendor/autoload.php";

$conn = Coralie\Connections\MySqlConnection::getInstance(require "settings.php");

if (isset($_GET['id']))
{
	// Update article
	$success = $conn->table('articles')->update([
			'title' => $_POST['title'] ?? "Untitled Article",
			'content' => $_POST['content'] ?? "This article has no content."
	])->where('id', $_GET['id'])->execute();
}
else
{
	// Submit article
	$success = $conn->table('articles')->insert([
			'title' => $_POST['title'] ?? "Untitled Article",
			'content' => $_POST['content'] ?? "This article has no content."
	])->execute();
}

if ($success)
	header("Location: index.php");
else
	echo "Failed to submit article.";