<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('taggables')) {
            return;
        }

        $this->purgeDuplicates();

        if (! Schema::hasIndex('taggables', 'taggables_unique')) {
            Schema::table('taggables', function (Blueprint $table) {
                $table->unique(['tag_id', 'taggable_id', 'taggable_type'], 'taggables_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('taggables') && Schema::hasIndex('taggables', 'taggables_unique')) {
            Schema::table('taggables', function (Blueprint $table) {
                $table->dropUnique('taggables_unique');
            });
        }
    }

    /**
     * Keep the lowest id per (tag, taggable) pair and drop the rest.
     * Uses a two-step approach so it runs on MySQL, SQLite and Postgres:
     * MySQL forbids referencing the DELETE target inside its own subquery.
     */
    protected function purgeDuplicates(): void
    {
        $survivors = DB::table('taggables')
            ->selectRaw('MIN(id) as id')
            ->groupBy('tag_id', 'taggable_id', 'taggable_type')
            ->pluck('id');

        if ($survivors->isEmpty()) {
            return;
        }

        DB::table('taggables')
            ->whereNotIn('id', $survivors)
            ->delete();
    }
};
