<?php namespace Indikator\Plugins;

use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use Backend\Models\Preference;
use Backend;
use Event;
use BackendAuth;

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
                'url'         => Backend::url('indikator/plugins/frontend'),
                'category'    => SettingsManager::CATEGORY_CMS,
                'permissions' => ['indikator.plugins']
            ]
        ];
    }

    public function registerReportWidgets()
    {
        return [
            'Indikator\Plugins\ReportWidgets\Plugins' => [
                'label'       => 'indikator.plugins::lang.plugin.name',
                'context'     => 'dashboard',
                'permissions' => ['indikator.plugins']
            ]
        ];
    }

    public function registerPermissions()
    {
        return [
            'indikator.plugins' => [
                'tab'   => 'system::lang.permissions.name',
                'label' => 'indikator.plugins::lang.permission',
                'roles' => ['developer']
            ]
        ];
    }

    public function boot()
    {
        Event::listen('backend.form.extendFields', function($form)
        {
            if (!BackendAuth::check() || $form->isNested) {
                return;
            }

            if ($form->model instanceof Preference) {

                if (!BackendAuth::getUser()->hasAccess('indikator.plugins')) {
                    return;
                }

                $form->addTabFields([
                    'fp_show_total' => [
                        'tab'     => 'indikator.plugins::lang.plugin.name',
                        'label'   => 'indikator.plugins::lang.settings.show_total',
                        'type'    => 'switch',
                        'default' => true
                    ],
                    'fp_show_sizes' => [
                        'tab'     => 'indikator.plugins::lang.plugin.name',
                        'label'   => 'indikator.plugins::lang.settings.show_sizes',
                        'type'    => 'switch',
                        'default' => true
                    ],
                    'fp_show_files' => [
                        'tab'     => 'indikator.plugins::lang.plugin.name',
                        'label'   => 'indikator.plugins::lang.settings.show_files',
                        'type'    => 'switch',
                        'default' => true
                    ],
                    'fp_show_folders' => [
                        'tab'     => 'indikator.plugins::lang.plugin.name',
                        'label'   => 'indikator.plugins::lang.settings.show_folders',
                        'type'    => 'switch',
                        'default' => true
                    ],
                    'fp_show_fonts' => [
                        'tab'     => 'indikator.plugins::lang.plugin.name',
                        'label'   => 'indikator.plugins::lang.settings.show_fonts',
                        'type'    => 'switch',
                        'default' => true
                    ]
                ]);
            }
        });
    }
}
