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

        if ($folders = opendir('themes')) {
            while (false !== ($folder = readdir($folders))) {
                if ($folder != '.' && $folder != '..') {
                    $themes[$folder] = $folder;
                }
            }

            closedir($folders);
        }

        return $themes;
    }
}
