<?php

use Coralie\Schema\Migration;
use Coralie\Schema\Table;

class CreateArticles extends Migration
{
	public function up(): void
	{
		$this->table('articles', function (Table $table) {
			$table->addPrimary('id');
			$table->addString('title');
			$table->addString('content');
		});
	}
	
	public function down(): void
	{
		$this->dropTable('articles');
	}
}