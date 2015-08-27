<?php namespace Indikator\Plugins\Models;

use Model;

class frontend extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $table = 'indikator_frontend_plugins';

    public $rules = [
        'name' => 'required|between:1,100'
    ];

    public function getThemeOptions()
    {
        $themes = [];

        if ($handle = opendir('themes')) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    $themes[$item] = $item;
                }
            }

            closedir($handle);
        }

        return $themes;
    }
}
