<?php

use Coralie\Schema\Migration;
use Coralie\Schema\Table;

class AddArticlesAuthor extends Migration
{
	public function up(): void
	{
		$this->table('articles', function (Table $table) {
			$table->addInteger('author');
		});
	}
	
	public function down(): void
	{
		$this->table('articles', function (Table $table) {
			$table->dropColumn('author');
		});
	}
}