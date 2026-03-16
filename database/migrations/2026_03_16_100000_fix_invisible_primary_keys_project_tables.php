<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize tables created while sql_generate_invisible_primary_key=ON.
     */
    public function up(): void
    {
        $tables = [
            'users',
            'personal_access_tokens',
            'categories',
            'subcategories',
            'products',
            'posts',
            'post_images',
            'post_comments',
            'post_likes',
            'post_shares',
            'follows',
        ];

        foreach ($tables as $table) {
            $this->fixTableIfNeeded($table);
        }
    }

    public function down(): void
    {
        // No down migration to avoid re-introducing broken schema.
    }

    private function fixTableIfNeeded(string $table): void
    {
        $hasInvisiblePk = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('column_name', 'my_row_id')
            ->exists();

        if (! $hasInvisiblePk) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` DROP PRIMARY KEY, DROP COLUMN `my_row_id`, MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)',
            $table
        ));
    }
};
