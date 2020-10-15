<?php namespace PlanetaDelEste\DeployApp\Updates;

use Schema;
use Illuminate\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * Class CreateTableFrontApps
 *
 * @package PlanetaDelEste\DeployApp\Classes\Console
 */
class CreateTableFrontApps extends Migration
{
    const TABLE = 'planetadeleste_deployapp_frontapps';

    /**
     * Apply migration
     */
    public function up()
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(
            self::TABLE,
            function (Blueprint $obTable) {
                $obTable->engine = 'InnoDB';
                $obTable->increments('id')->unsigned();
                $obTable->string('name')->index();
                $obTable->timestamps();
            }
        );
    }

    /**
     * Rollback migration
     */
    public function down()
    {
        Schema::dropIfExists(self::TABLE);
    }
}
