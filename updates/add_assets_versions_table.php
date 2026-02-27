<?php

namespace PlanetaDelEste\DeployApp\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddAssetsVersionsTable extends Migration
{
    /**
     * @var string
     */
    protected $sTableName = 'planetadeleste_deployapp_versions';

    public function up(): void
    {
        if (!Schema::hasTable($this->sTableName)) {
            return;
        }

        Schema::table($this->sTableName, function (Blueprint $obTable): void {
            if (Schema::hasColumn($this->sTableName, 'assets')) {
                return;
            }

            $obTable->text('assets')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->sTableName)) {
            return;
        }

        Schema::table($this->sTableName, function (Blueprint $obTable): void {
            if (!Schema::hasColumn($this->sTableName, 'assets')) {
                return;
            }

            $obTable->dropColumn('assets');
        });
    }
}
