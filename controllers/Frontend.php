<?php namespace Indikator\Plugins\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use System\Classes\SettingsManager;
use File;
use DB;
use Flash;
use Lang;
use App;

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

    public function onSearchPlugins()
    {
        /* Settings */
        libxml_use_internal_errors(true);
        $count = 0;

        /* Themes */
        if ($themes = opendir('themes')) {
            while (false !== ($theme = readdir($themes))) {
                if ($theme != '.' && $theme != '..') {

                    /* Layouts */
                    if ($layouts = opendir('themes/'.$theme.'/layouts')) {
                        while (false !== ($layout = readdir($layouts))) {
                            if ($layout != '.' && $layout != '..') {

                                /* File */
                                $html = File::get('themes/'.$theme.'/layouts/'.$layout);
                                $html = substr($html, strpos($html, '==') + 2);

                                /* Empty */
                                if ($html == '') {
                                    continue;
                                }

                                /* Content */
                                $dom = new \DOMDocument;
                                $dom->loadHTML($html);

                                /* Stylesheet */
                                foreach ($dom->getElementsByTagName('link') as $item) {
                                    $href = $item->getAttribute('href');

                                    /* Fonts */
                                    if (substr_count($href, 'fonts.googleapis') == 1) {
                                        $name = str_replace('+', ' ', substr($href, 34, strpos($href, ':') - 34));

                                        /* Check duplication */
                                        if (DB::table('indikator_frontend_plugins')->where('name', $name)->where('language', 4)->count() > 0) {
                                            continue;
                                        }

                                        /* Add to database */
                                        $this->insertToDatabase($name, 'https://www.google.com/fonts', '', 4, $theme, 'web_font');

                                        /* Plugin couter */
                                        $count++;
                                    }

                                    /* Bootstrap */
                                    else if (substr_count($href, 'maxcdn.bootstrapcdn') == 1) {
                                        /* Check duplication */
                                        if (DB::table('indikator_frontend_plugins')->where('name', 'Bootstrap')->where('language', 3)->count() > 0) {
                                            continue;
                                        }

                                        /* Plugin details */
                                        $url = explode('/', substr($href, strpos($href, '//') + 2));
                                        $data = $this->getPluginDetails('Bootstrap_CSS');

                                        /* Add to database */
                                        $this->insertToDatabase($data['name'], $data['webpage'], $url[2], 3, $theme, $data['desc']);

                                        /* Plugin couter */
                                        $count++;
                                    }

                                    /* Popular */
                                    else if (substr_count($href, '{{ [') == 1) {
                                        $href = preg_replace('/\s+/', ' ', trim($href));

                                        /* Plugin filename */
                                        $file = [
                                            'animate.css',
                                            'bootstrap.css',
                                            'bootstrap.min.css',
                                            'font-awesome.css',
                                            'normalize.css'
                                        ];

                                        /* Plugin name */
                                        $name = [
                                            'Animate',
                                            'Bootstrap_CSS',
                                            'Bootstrap_CSS',
                                            'Font_Awesome',
                                            'Normalize'
                                        ];

                                        /* Check availability */
                                        foreach ($file as $key => $value) {
                                            if (substr_count($href, $value) > 0) {
                                                /* Plugin details */
                                                $data = $this->getPluginDetails($name[$key]);

                                                /* Check duplication */
                                                if (DB::table('indikator_frontend_plugins')->where('name', $data['name'])->count() > 0) {
                                                    continue;
                                                }

                                                /* Add to database */
                                                $this->insertToDatabase($data['name'], $data['webpage'], '', 3, $theme, $data['desc']);
                                            }
                                        }
                                    }
                                }

                                /* JavaScript */
                                foreach ($dom->getElementsByTagName('script') as $item) {
                                    $src = $item->getAttribute('src');

                                    /* CDN */
                                    if (substr_count($src, '//') == 1) {
                                        $url = explode('/', substr($src, strpos($src, '//') + 2));
                                        $data['webpage'] = $data['desc'] = '';

                                        /* jQuery */
                                        if (substr_count($src, 'code.jquery') == 1) {
                                            $data = $this->getPluginDetails('jQuery');
                                            $data['version'] = str_replace(['.min', '.js'], '', substr($url[1], 7));
                                        }

                                        /* Google */
                                        else if (substr_count($src, 'ajax.aspnetcdn') == 1) {
                                            $data = $this->getPluginDetails(ucfirst($url[2]));
                                            $data['version'] = substr($url[3], 7, 5);
                                        }

                                        /* Microsoft */
                                        else if (substr_count($src, 'ajax.googleapis') == 1) {
                                            $data = $this->getPluginDetails(ucfirst($url[3]));
                                            $data['version'] = $url[4];
                                        }

                                        /* CDNJS */
                                        else if (substr_count($src, 'cdnjs.cloudflare') == 1) {
                                            $data = $this->getPluginDetails(ucfirst($url[3]));
                                            $data['version'] = $url[4];
                                        }

                                        /* jsDelivr */
                                        else if (substr_count($src, 'cdn.jsdelivr') == 1) {
                                            $data = $this->getPluginDetails(ucfirst($url[1]));
                                            $data['version'] = $url[2];
                                        }

                                        /* Bootstrap */
                                        else if (substr_count($src, 'maxcdn.bootstrapcdn') == 1) {
                                            $data = $this->getPluginDetails('Bootstrap');
                                            $data['version'] = $url[2];
                                        }

                                        /* TinyMCE */
                                        else if (substr_count($src, 'cdn.tinymce') == 1) {
                                            $data = $this->getPluginDetails('TinyMCE');
                                            $data['version'] = $url[1];
                                        }

                                        /* DataTables */
                                        else if (substr_count($src, 'cdn.datatables') == 1) {
                                            $data = $this->getPluginDetails('DataTables');
                                            $data['version'] = $url[1];
                                        }

                                        /* Check duplication */
                                        if (DB::table('indikator_frontend_plugins')->where('name', $data['name'])->whereOr('name', lcfirst($data['name']))->count() > 0) {
                                            continue;
                                        }

                                        /* Add to database */
                                        $this->insertToDatabase($data['name'], $data['webpage'], $data['version'], 1, $theme, $data['desc']);

                                        /* Plugin couter */
                                        $count++;
                                    }

                                    /* Self hosted */
                                    else if (substr_count($src, '{{ [') == 1 || substr_count($src, '{{[') == 1) {
                                        $src = preg_replace('/\s+/', ' ', trim($src));
                                        $items = explode(',', str_replace("'", "", $src));

                                        foreach ($items as $js) {
                                            /* Remove strings */
                                            $name = trim(str_replace([
                                                '{{ [',
                                                '{{[',
                                                'assets/js/',
                                                'assets/javascript/',
                                                'assets/vendor/',
                                                '.custom',
                                                '-custom',
                                                '.min',
                                                '.pack',
                                                '.js',
                                                'jquery.',
                                                'bootstrap.',
                                                'bootstrap-',
                                                ']|theme}}',
                                                ']|theme }}'
                                            ], '', $js));

                                            /* Remove subfolders */
                                            if (substr_count($name, '/') > 0) {
                                                $path = explode('/', $name);
                                                $name = $path[count($path) - 1];
                                            }

                                            /* Plugin details */
                                            $data = $this->getPluginDetails($name);

                                            /* Unknown plugin */
                                            if ($data['webpage'] == '') {
                                                $data['name'] = ucfirst(str_replace(['.', '-'], ' ', $data['name']));
                                                $data['desc'] = '';
                                            }

                                            /* Check duplication */
                                            if (DB::table('indikator_frontend_plugins')->where('name', $data['name'])->where('language', 1)->count() > 0 || $data['name'] == 'Script' || $data['name'] == 'Plugins' || $data['name'] == 'Theme' || $data['name'] == 'Theme-functions' || $data['name'] == 'Custom' || $data['name'] == 'App' || $data['name'] == 'Main' || $data['name'] == 'Own') {
                                                continue;
                                            }

                                            /* Add to database */
                                            $this->insertToDatabase($data['name'], $data['webpage'], '', 1, $theme, $data['desc']);

                                            /* Plugin couter */
                                            $count++;
                                        }
                                    }
                                }
                            }
                        }

                        closedir($layouts);
                    }
                }
            }

            closedir($themes);
        }

        Flash::success(str_replace('%s', $count, Lang::get('indikator.plugins::lang.flash.search')));

        return $this->listRefresh('manage');
    }

    public function getPluginDetails($name = '')
    {
        /* Supported plugins */
        $plugin = [
            'bootstrap_css' => [
                'name'    => 'Bootstrap',
                'webpage' => 'http://getbootstrap.com/css'
            ],
            'animate' => [
                'name'    => 'Animate',
                'webpage' => 'https://daneden.github.io/animate.css'
            ],
            'font_awesome' => [
                'name'    => 'Font Awesome',
                'webpage' => 'http://fontawesome.io/icons'
            ],
            'normalize' => [
                'name'    => 'Normalize',
                'webpage' => 'https://necolas.github.io/normalize.css'
            ],
            'bootstrap_js' => [
                'name'    => 'Bootstrap',
                'webpage' => 'http://getbootstrap.com/javascript'
            ],
            'tinymce' => [
                'name'    => 'TinyMCE',
                'webpage' => 'https://www.tinymce.com'
            ],
            'datatables' => [
                'name'    => 'DataTables',
                'webpage' => 'https://datatables.net'
            ],
            'jquery' => [
                'name'    => 'jQuery',
                'webpage' => 'http://jquery.com'
            ],
            'jquery_ui' => [
                'name'    => 'jQuery UI',
                'webpage' => 'http://jqueryui.com'
            ],
            'angularjs' => [
                'name'    => 'AngularJS',
                'webpage' => 'https://angularjs.org'
            ],
            'bxslider' => [
                'name'    => 'bxSlider',
                'webpage' => 'http://bxslider.com'
            ],
            'isotope' => [
                'name'    => 'Isotope',
                'webpage' => 'http://isotope.metafizzy.co'
            ],
            'modernizr' => [
                'name'    => 'Modernizr',
                'webpage' => 'https://modernizr.com'
            ],
            'owl_carousel' => [
                'name'    => 'OWL Carousel',
                'webpage' => 'http://www.owlgraphic.com/owlcarousel'
            ],
            'wow' => [
                'name'    => 'WOW',
                'webpage' => 'http://mynameismatthieu.com/WOW'
            ]
        ];

        /* Formating the name */
        $code = strtolower($name);

        if ($code == 'jquery.ui' || $code == 'jqueryui') {
            $code = 'jquery_ui';
        }
        else if ($code == 'angular') {
            $code = 'angularjs';
        }
        else if ($code == 'bootstrap') {
            $code = 'bootstrap_js';
        }
        else if ($code == 'owl.carousel' || $code == 'owl') {
            $code = 'owl_carousel';
        }

        /* Empty details */
        if (!isset($plugin[$code])) {
            return [
                'name'    => $name,
                'webpage' => '',
                'desc'    => ''
            ];
        }

        /* Details of plugin */
        return [
            'name'    => $plugin[$code]['name'],
            'webpage' => $plugin[$code]['webpage'],
            'desc'    => $code
        ];
    }

    public function insertToDatabase($name = '', $webpage = '', $version = '', $language = 1, $theme = '', $description = '')
    {
        if ($description != '') {
            $description = Lang::get('indikator.plugins::lang.3rd_plugin.'.$description);
        }

        DB::table('indikator_frontend_plugins')->insertGetId([
            'name'        => $name,
            'webpage'     => $webpage,
            'version'     => $version,
            'language'    => $language,
            'theme'       => $theme,
            'description' => $description,
            'common'      => '',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s')
        ]);
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
            if ($element != '.' && $element != '..') {
                if (filetype($folder.'/'.$element) == 'dir') {
                    $value = $this->pluginFolderStat($folder.'/'.$element);
                    $attr['size']    += $value['size'];
                    $attr['files']   += $value['files'];
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

            if (!in_array(App::getLocale(), $common)) {
                $size = str_replace('.', ',', $size);
            }

            return $size.' '.$name[$i];
        }

        $size = '0 '.'B';

        return $size;
    }
}
