<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('publications', 'embed_code')) {
            Schema::table('publications', function (Blueprint $table) {
                $table->dropColumn('embed_code');
            });
        }

        if (! Schema::hasColumn('publications', 'pdf_file')) {
            Schema::table('publications', function (Blueprint $table) {
                $table->string('pdf_file')->nullable()->after('cover_image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('publications', 'pdf_file')) {
            Schema::table('publications', function (Blueprint $table) {
                $table->dropColumn('pdf_file');
            });
        }

        if (! Schema::hasColumn('publications', 'embed_code')) {
            Schema::table('publications', function (Blueprint $table) {
                $table->text('embed_code')->nullable()->after('cover_image');
            });
        }
    }
};
