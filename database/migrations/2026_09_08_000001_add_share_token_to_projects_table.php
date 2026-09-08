<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'share_token')) return;

        Schema::table('projects', fn (Blueprint $table) => $table->uuid('share_token')->nullable()->after('id'));
        DB::table('projects')->whereNull('share_token')->orderBy('id')->each(fn (object $project) => DB::table('projects')->where('id', $project->id)->update(['share_token' => (string) Str::uuid()]));
        Schema::table('projects', fn (Blueprint $table) => $table->unique('share_token'));
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'share_token')) return;

        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn('share_token');
        });
    }
};
