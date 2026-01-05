<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->string('favoritable_type')->nullable();
            $table->unsignedBigInteger('favoritable_id')->nullable();
            $table->unsignedBigInteger('post_id')->nullable()->change();

            $table->index(['favoritable_type', 'favoritable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex(['favoritable_type', 'favoritable_id']);
            $table->dropColumn(['favoritable_type', 'favoritable_id']);
            $table->unsignedBigInteger('post_id')->nullable(false)->change();
        });
    }
};
