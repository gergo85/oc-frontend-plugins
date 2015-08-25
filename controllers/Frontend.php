<?php namespace Indikator\Plugins\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use System\Classes\SettingsManager;
use DB;
use Flash;
use Lang;

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
                if (DB::table('frontend_plugins')->where('id', $objectId)->count() == 1) {
                    DB::table('frontend_plugins')->where('id', $objectId)->delete();
                }
            }

            Flash::success(Lang::get('indikator.plugins::lang.flash.remove'));
        }

        return $this->listRefresh('manage');
    }
}
