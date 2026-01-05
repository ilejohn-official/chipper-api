<?php

use App\Enums\FavoritableType;
use Illuminate\Support\Facades\DB;
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

            $table->index(['favoritable_type', 'favoritable_id']);
        });

        // Migrate existing data
        DB::table('favorites')->whereNotNull('post_id')->update([
            'favoritable_id' => DB::raw('post_id'),
            'favoritable_type' => FavoritableType::POST->value,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrate data back to post_id
        DB::table('favorites')->where('favoritable_type', FavoritableType::POST->value)->update([
            'post_id' => DB::raw('favoritable_id'),
        ]);

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex(['favoritable_type', 'favoritable_id']);
            $table->dropColumn(['favoritable_type', 'favoritable_id']);
        });
    }
};
