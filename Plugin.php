<?php namespace Indikator\Plugins;

use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use Backend;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'indikator.plugins::lang.plugin.name',
            'description' => 'indikator.plugins::lang.plugin.description',
            'author'      => 'indikator.plugins::lang.plugin.author',
            'icon'        => 'icon-cubes',
            'homepage'    => 'https://github.com/gergo85/oc-frontend-plugins'
        ];
    }

    public function registerSettings()
    {
        return [
            'frontend' => [
                'label'       => 'indikator.plugins::lang.plugin.name',
                'description' => 'indikator.plugins::lang.plugin.description',
                'icon'        => 'icon-cubes',
                'permissions' => ['indikator.plugins.all'],
                'url'         => Backend::url('indikator/plugins/frontend'),
                'category'    => SettingsManager::CATEGORY_CMS
            ]
        ];
    }

    public function registerReportWidgets()
    {
        return [
            'Indikator\Plugins\ReportWidgets\Plugins' => [
                'label'   => 'indikator.plugins::lang.plugin.name',
                'context' => 'dashboard'
            ]
        ];
    }

    public function registerPermissions()
    {
        return [
            'indikator.plugins.all' => [
                'tab'   => 'system::lang.permissions.name',
                'label' => 'indikator.plugins::lang.permission'
            ]
        ];
    }
}
