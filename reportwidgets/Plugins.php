<?php namespace Indikator\Plugins\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Exception;
use Indikator\Plugins\Models\Frontend;

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
                'title'   => 'indikator.plugins::lang.widget.show_total',
                'default' => true,
                'type'    => 'checkbox'
            ],
            'js' => [
                'title'   => 'indikator.plugins::lang.widget.show_js',
                'default' => true,
                'type'    => 'checkbox'
            ],
            'php' => [
                'title'   => 'indikator.plugins::lang.widget.show_php',
                'default' => true,
                'type'    => 'checkbox'
            ],
            'css' => [
                'title'   => 'indikator.plugins::lang.widget.show_css',
                'default' => true,
                'type'    => 'checkbox'
            ],
            'font' => [
                'title'   => 'indikator.plugins::lang.widget.show_font',
                'default' => true,
                'type'    => 'checkbox'
            ]
        ];
    }

    protected function loadData()
    {
        $this->vars['js']    = Frontend::where('language', '1')->count();
        $this->vars['php']   = Frontend::where('language', '2')->count();
        $this->vars['css']   = Frontend::where('language', '3')->count();
        $this->vars['font']  = Frontend::where('language', '4')->count();
        $this->vars['total'] = $this->vars['js'] + $this->vars['php'] + $this->vars['css'] + $this->vars['font'];
    }
}
