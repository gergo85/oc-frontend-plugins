<?php namespace Indikator\Plugins\Models;

use Model;

class Frontend extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $table = 'indikator_frontend_plugins';

    public $rules = [
        'name'     => 'required',
        'theme'    => 'required',
        'language' => 'required|between:1,4|numeric'
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
