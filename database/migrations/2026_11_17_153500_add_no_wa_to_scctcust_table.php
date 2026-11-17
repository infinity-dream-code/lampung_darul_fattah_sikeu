<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'DATA_MYSQL';

    public function up(): void
    {
        if (!Schema::connection($this->connection)->hasTable('scctcust')) {
            return;
        }

        if (!Schema::connection($this->connection)->hasColumn('scctcust', 'NO_WA')) {
            Schema::connection($this->connection)->table('scctcust', function (Blueprint $table) {
                $table->string('NO_WA', 20)->nullable()->after('GENUS');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::connection($this->connection)->hasTable('scctcust')
            && Schema::connection($this->connection)->hasColumn('scctcust', 'NO_WA')
        ) {
            Schema::connection($this->connection)->table('scctcust', function (Blueprint $table) {
                $table->dropColumn('NO_WA');
            });
        }
    }
};
