<?php namespace Indikator\Plugins\Updates;

use October\Rain\Database\Updates\Migration;
use DbDongle;

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
