<html>
	<head>
		<title>Coralie Example</title>
	</head>
	<body>
		<?php
			require __DIR__ . "/../../vendor/autoload.php";
			
			$conn = Coralie\Connections\MySqlConnection::getInstance(
					require "settings.php");
			
			$articles = $conn->table('articles')
				->select('id', 'title', 'content')->execute();
			
			foreach ($articles as $article)
			{
				echo "<h1>$article->title</h1>";
				echo "<p>$article->content</p>";
				echo "<a href='update.php?id=$article->id'>Update</a>";
				echo "<a href='delete.php?id=$article->id'>Delete</a>";
			}
		?>
		
		<h1>Create new article:</h1>
		<form method="POST" action="submit.php">
			<input type="text" name="title" placeholder="Article Title" /><br />
			<textarea name="content" placeholder="Article Body"></textarea><br />
			<input type="submit" />
		</form>
	</body>
</html>