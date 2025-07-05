<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('category_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('slug')->after('title');
            $table->string('meta_title')->after('slug')->nullable();
            $table->text('meta_description')->after('meta_title')->nullable();
            $table->text('meta_keywords')->after('meta_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');

            // Drop all other added columns
            $table->dropColumn([
                'slug',
                'meta_title',
                'meta_description',
                'meta_keywords',
            ]);
        });
    }
};
