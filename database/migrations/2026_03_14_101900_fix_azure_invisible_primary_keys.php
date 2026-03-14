<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize tables created under sql_generate_invisible_primary_key=ON
     * so Laravel can insert rows without manually setting `id`.
     */
    public function up(): void
    {
        $this->fixTableIfNeeded('users');
        $this->fixTableIfNeeded('personal_access_tokens');
    }

    public function down(): void
    {
        // Intentionally left blank. Reverting would re-introduce broken schema.
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
