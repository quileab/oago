<?php

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            if (! Schema::hasColumn('tags', 'type')) {
                $table->string('type', 20)->default('commercial')->after('slug')->index();
            }
            if (! Schema::hasColumn('tags', 'color')) {
                $table->string('color', 20)->nullable()->after('type');
            }
            if (! Schema::hasColumn('tags', 'fallback_image')) {
                $table->string('fallback_image', 120)->nullable()->after('color');
            }
        });

        Tag::clearCache();
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $columnsToDrop = array_values(array_filter(
                ['type', 'color', 'fallback_image'],
                fn (string $column): bool => Schema::hasColumn('tags', $column)
            ));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Tag::clearCache();
    }
};
