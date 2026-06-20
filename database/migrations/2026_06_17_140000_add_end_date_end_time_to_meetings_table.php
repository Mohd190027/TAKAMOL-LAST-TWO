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
        Schema::table('meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings', 'end_date')) {
                $table->date('end_date')->nullable()->after('date');
            }
            if (!Schema::hasColumn('meetings', 'end_time')) {
                $table->string('end_time', 5)->nullable()->after('end_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('meetings', 'end_date')) {
                $columns[] = 'end_date';
            }
            if (Schema::hasColumn('meetings', 'end_time')) {
                $columns[] = 'end_time';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
