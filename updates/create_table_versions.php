<?php namespace PlanetaDelEste\DeployApp\Updates;

use Schema;
use Illuminate\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * Class CreateTableVersions
 *
 * @package PlanetaDelEste\DeployApp\Classes\Console
 */
class CreateTableVersions extends Migration
{
    const TABLE = 'planetadeleste_deployapp_versions';

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
                $obTable->unsignedInteger('frontapp_id');
                $obTable->timestamps();
                $obTable->string('version', 50);
                $obTable->text('description')->nullable();

                $obTable->index('version');
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
