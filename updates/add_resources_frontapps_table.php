<?php

namespace PlanetaDelEste\DeployApp\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;


class AddResourcesFrontappsTable extends Migration
{
    const TABLE = 'planetadeleste_deployapp_frontapps';

    public function up()
    {
        if (Schema::hasTable(self::TABLE)) {
            Schema::table(self::TABLE, function (Blueprint $obTable) {
                $obTable->boolean('resources')->default(false);
            });
        }

    }

    public function down()
    {
        if (!Schema::hasTable(self::TABLE)) {
            return false;
        }

        if (Schema::hasColumns(self::TABLE, ['resources'])) {
            Schema::table(self::TABLE, function (Blueprint $obTable) {
                $obTable->dropColumn(['resources']);

            }
            );
        }
    }

}
