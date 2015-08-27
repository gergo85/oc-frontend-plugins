<?php namespace Indikator\Plugins\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreatePluginsTable extends Migration
{
    public function up()
    {
        Schema::create('indikator_frontend_plugins', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 100);
            $table->string('webpage', 100);
            $table->string('version', 10);
            $table->string('language', 10);
            $table->string('theme', 100);
            $table->text('description')->nullable();
            $table->text('common')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('indikator_frontend_plugins');
    }
}
