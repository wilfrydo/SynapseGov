<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the officer asked the reporter to supply (status awaiting_info). Previously stored in
 * rejection_reason, which made an information request look like a rejection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'info_request')) {
                $table->text('info_request')->nullable();
            }
        });

        DB::table('reports')
            ->where('status', 'awaiting_info')
            ->whereNotNull('rejection_reason')
            ->update(['info_request' => DB::raw('rejection_reason'), 'rejection_reason' => null]);
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('info_request');
        });
    }
};
