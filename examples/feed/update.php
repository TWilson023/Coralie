<html>
	<head>
		<title>Coralie Example</title>
	</head>
	<body>
		<?php
			require __DIR__ . "/../../vendor/autoload.php";
			
			$conn = Coralie\Connections\MySqlConnection::getInstance(
					require "settings.php");
			
			$article = $conn->table('articles')
				->select('id', 'title', 'content')
				->where('id', $_GET['id'])
				->limit(1)
				->execute()[0];
		?>
		
		<form method='POST' action='submit.php?id=<?= $article->id ?>'>
			<input type='text' name='title' value='<?= $article->title ?>' /><br />
			<textarea name='content'><?= $article->content ?></textarea><br />
			<input type='submit' />
		</form>
	</body>
</html>