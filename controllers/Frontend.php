<?php namespace Indikator\Plugins\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use System\Classes\SettingsManager;
use DB;
use Flash;
use Lang;
use File;

class Frontend extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $bodyClass = 'compact-container';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Indikator.Plugins', 'frontend');
    }

    public function onRemovePlugins()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $objectId) {
                if (DB::table('indikator_frontend_plugins')->where('id', $objectId)->count() == 1) {
                    DB::table('indikator_frontend_plugins')->where('id', $objectId)->delete();
                }
            }

            Flash::success(Lang::get('indikator.plugins::lang.flash.remove'));
        }

        return $this->listRefresh('manage');
    }

    public function pluginFolderStat($folder = 'themes')
    {
        $attr['size'] = $attr['files'] = $attr['folders'] = 0;

        if (!File::exists($folder)) {
            File::makeDirectory($folder, 0775, true);
        }

        $elementents = scandir($folder);

        foreach ($elementents as $element) {
            if ($element != '.' && $element != '..' && $element != '.quarantine' && $element != '.tmb') {
                if (filetype($folder.'/'.$element) == 'dir') {
                    $value = $this->pluginFolderStat($folder.'/'.$element);
                    $attr['size'] += $value['size'];
                    $attr['files'] += $value['files'];
                    $attr['folders'] += $value['folders'] + 1;
                }

                else {
                    $attr['size'] += File::size($folder.'/'.$element);
                    $attr['files']++;
                }
            }
        }

        return $attr;
    }

    public function pluginFileSize($size = 0)
    {
        if ($size > 0) {
            $name = ['B', 'KB', 'MB'];
            $common = ['au', 'bn', 'bw', 'ch', 'cn', 'do', 'eg', 'gt', 'hk', 'hn', 'ie', 'il', 'in', 'jp', 'ke', 'kp', 'kr', 'lb', 'lk', 'mn', 'mo', 'mt', 'mx', 'my', 'ng', 'ni', 'np', 'nz', 'pa', 'ph', 'pk', 'sg', 'th', 'tw', 'tz', 'ug', 'uk', 'us', 'zw'];

            for ($i = 0; $size >= 1024; $i++) {
                $size /= 1024;

                if ($i < 1) {
                    $size = round($size, 0);
                }
                else {
                    $size = round($size, 1);
                }
            }

            global $preferences;

            if (!in_array($preferences['locale'], $common)) {
                $size = str_replace('.', ',', $size);
            }

            return $size.' '.$name[$i];
        }

        $size = '0 '.'B';

        return $size;
    }
}
