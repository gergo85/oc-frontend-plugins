<?php namespace Indikator\Plugins\Updates;

use Schema;
use DbDongle;
use October\Rain\Database\Updates\Migration;

class UpdateTimestampsNullable extends Migration
{
    public function up()
    {
        DbDongle::disableStrictMode();

        DbDongle::convertTimestamps('indikator_frontend_plugins');
    }

    public function down()
    {
        // ...
    }
}
