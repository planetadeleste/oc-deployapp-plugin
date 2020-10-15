<?php

namespace PlanetaDelEste\DeployApp\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * Class AddPathFrontappsTable
 *
 * @package PlanetaDelEste\DeployApp\Updates
 */
class AddPathFrontappsTable extends Migration
{
    const TABLE = 'planetadeleste_deployapp_frontapps';

    public function up()
    {
        if (Schema::hasTable(self::TABLE)) {
            Schema::table(
                self::TABLE,
                function (Blueprint $obTable) {
                    $obTable->string('path')->nullable();
                }
            );
        }
    }

    public function down()
    {
        if (!Schema::hasTable(self::TABLE)) {
            return false;
        }

        if (Schema::hasColumns(self::TABLE, ['path'])) {
            Schema::table(
                self::TABLE,
                function (Blueprint $obTable) {
                    $obTable->dropColumn(['path']);
                }
            );
        }
    }

}
