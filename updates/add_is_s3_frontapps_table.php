<?php

namespace PlanetaDelEste\DeployApp\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddIsS3FrontappsTable extends Migration
{
    const TABLE = 'planetadeleste_deployapp_frontapps';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, static function (Blueprint $obTable): void {
            $obTable->boolean('is_s3')->default(false);
        });
    }

    public function down()
    {
        if (!Schema::hasTable(self::TABLE)) {
            return false;
        }

        if (!Schema::hasColumns(self::TABLE, ['is_s3'])) {
            return;
        }

        Schema::table(self::TABLE, static function (Blueprint $obTable): void {
            $obTable->dropColumn(['is_s3']);
        });
    }
}
