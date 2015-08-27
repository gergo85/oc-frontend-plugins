<?php namespace Indikator\Plugins\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Exception;
use DB;

class Plugins extends ReportWidgetBase
{
    public function render()
    {
        try {
            $this->loadData();
        }
        catch (Exception $ex) {
            $this->vars['error'] = $ex->getMessage();
        }

        return $this->makePartial('widget');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'backend::lang.dashboard.widget_title_label',
                'default'           => 'indikator.plugins::lang.plugin.name',
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'backend::lang.dashboard.widget_title_error'
            ],
            'total' => [
                'title'             => 'indikator.plugins::lang.widget.show_total',
                'default'           => true,
                'type'              => 'checkbox'
            ],
            'js' => [
                'title'             => 'indikator.plugins::lang.widget.show_js',
                'default'           => true,
                'type'              => 'checkbox'
            ],
            'php' => [
                'title'             => 'indikator.plugins::lang.widget.show_php',
                'default'           => true,
                'type'              => 'checkbox'
            ],
            'css' => [
                'title'             => 'indikator.plugins::lang.widget.show_css',
                'default'           => true,
                'type'              => 'checkbox'
            ]
        ];
    }

    protected function loadData()
    {
        $this->vars['total'] = DB::table('indikator_frontend_plugins')->count();
        $this->vars['js'] = DB::table('indikator_frontend_plugins')->where('language', '1')->count();
        $this->vars['php'] = DB::table('indikator_frontend_plugins')->where('language', '2')->count();
        $this->vars['css'] = DB::table('indikator_frontend_plugins')->where('language', '3')->count();
    }
}
