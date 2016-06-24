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

                                /* Content */
                                $dom = new \DOMDocument;
                                $dom->loadHTML($html);

                                /* Stylesheet */
                                foreach ($dom->getElementsByTagName('link') as $item) {
                                    $href = $item->getAttribute('href');

                                    /* Fonts */
                                    if (substr_count($href, 'fonts.googleapis') == 1) {
                                        $name = str_replace('+', ' ', substr($href, 34, strpos($href, ':') - 34));

                                        if (DB::table('indikator_frontend_plugins')->where('name', $name)->where('language', 4)->count() > 0) {
                                            continue;
                                        }

                                        $this->insertToDatabase($name, 'https://www.google.com/fonts', '', 4, $theme, 'web_font');

                                        $count++;
                                    }

                                    /* Bootstrap */
                                    else if (substr_count($href, 'maxcdn.bootstrapcdn') == 1) {
                                        if (DB::table('indikator_frontend_plugins')->where('name', 'Bootstrap')->where('language', 3)->count() > 0) {
                                            continue;
                                        }

                                        $array = explode('/', substr($href, strpos($href, '//') + 2));

                                        $this->insertToDatabase('Bootstrap', 'http://getbootstrap.com/css', $array[2], 3, $theme, 'bootstrap_css');

                                        $count++;
                                    }

                                    /* Popular */
                                    else if (substr_count($href, '{{ [') == 1) {
                                        $href = preg_replace('/\s+/', ' ', trim($href));

                                        $file = [
                                            'animate.css',
                                            'bootstrap.min.css',
                                            'font-awesome.css',
                                            'normalize.css'
                                        ];

                                        $name = [
                                            'Animate',
                                            'Bootstrap',
                                            'Font Awesome',
                                            'Normalize'
                                        ];

                                        $webpage = [
                                            'https://daneden.github.io/animate.css',
                                            'http://getbootstrap.com/css',
                                            'http://fontawesome.io/icons',
                                            'https://necolas.github.io/normalize.css'
                                        ];

                                        $version = '';

                                        $description = [
                                            'animate',
                                            'bootstrap_css',
                                            'font_awesome',
                                            'normalize'
                                        ];

                                        foreach ($file as $key => $value) {
                                            if (substr_count($href, $value) > 0) {
                                                $this->insertToDatabase($name[$key], $webpage[$key], '', 3, $theme, $description[$key]);
                                            }
                                        }
                                    }
                                }

                                /* Scripts */
                                foreach ($dom->getElementsByTagName('script') as $item) {
                                    $src = $item->getAttribute('src');

                                    /* CDN */
                                    if (substr_count($src, '//') == 1) {
                                        $array = explode('/', substr($src, strpos($src, '//') + 2));
                                        $webpage = $description = '';

                                        if (substr_count($src, 'code.jquery') == 1) {
                                            $name = 'jQuery';
                                            $version = substr($array[7], 7, 5);
                                        }
                                        else if (substr_count($src, 'ajax.aspnetcdn') == 1) {
                                            $name = ucfirst($array[2]);
                                            $version = substr($array[3], 7, 5);
                                        }
                                        else if (substr_count($src, 'ajax.googleapis') == 1) {
                                            $name = ucfirst($array[3]);
                                            $version = $array[4];
                                        }
                                        else if (substr_count($src, 'cdnjs.cloudflare') == 1) {
                                            $name = ucfirst($array[3]);
                                            $version = $array[4];
                                        }
                                        else if (substr_count($src, 'cdn.jsdelivr') == 1) {
                                            $name = ucfirst($array[1]);
                                            $version = $array[2];
                                        }
                                        else if (substr_count($src, 'maxcdn.bootstrapcdn') == 1) {
                                            $name = 'Bootstrap';
                                            $version = $array[2];
                                            $webpage = 'http://getbootstrap.com/javascript';
                                            $description = 'bootstrap_js';
                                        }
                                        else if (substr_count($src, 'cdn.tinymce') == 1) {
                                            $name = 'TinyMCE';
                                            $version = $array[1];
                                            $webpage = 'https://www.tinymce.com';
                                            $description = 'tinymce';
                                        }
                                        else if (substr_count($src, 'cdn.datatables') == 1) {
                                            $name = 'DataTables';
                                            $version = $array[1];
                                            $webpage = 'https://datatables.net';
                                            $description = 'datatables';
                                        }

                                        if ($name == 'Jquery' || $name == 'jQuery') {
                                            $name = 'jQuery';
                                            $webpage = 'http://jquery.com';
                                            $description = 'jquery';
                                        }
                                        else if ($name == 'Jqueryui' || $name == 'Jquery.ui') {
                                            $name = 'jQuery UI';
                                            $webpage = 'http://jqueryui.com';
                                            $description = 'jquery_ui';
                                        }
                                        else if ($name == 'Angularjs') {
                                            $name = 'AngularJS';
                                            $webpage = 'https://angularjs.org';
                                            $description = 'angularjs';
                                        }

                                        if (DB::table('indikator_frontend_plugins')->where('name', $name)->whereOr('name', lcfirst($name))->count() > 0) {
                                            continue;
                                        }

                                        $this->insertToDatabase($name, $webpage, $version, 1, $theme, $description);

                                        $count++;
                                    }

                                    /* Self hosted */
                                    else if (substr_count($src, '{{ [') == 1) {
                                        $src = preg_replace('/\s+/', ' ', trim($src));
                                        $array = explode(',', str_replace("'", "", $src));

                                        foreach ($array as $js) {
                                            $name = ucfirst(trim(str_replace([
                                                '{{ [',
                                                'assets/js/',
                                                'assets/javascript/',
                                                'assets/vendor/',
                                                '.min.js',
                                                '.pack.js',
                                                '.js',
                                                'jquery.',
                                                '.custom',
                                                '-custom',
                                                ']|theme }}'
                                            ], '', $js)));

                                            if ($name == 'Jquery') {
                                                $name = 'jQuery';
                                                $webpage = 'http://jquery.com';
                                                $description = 'jquery';
                                            }
                                            else if ($name == 'Angular') {
                                                $name = 'AngularJS';
                                                $webpage = 'https://angularjs.org';
                                                $description = 'angularjs';
                                            }
                                            else if ($name == 'Modernizr') {
                                                $webpage = 'https://modernizr.com';
                                                $description = 'modernizr';
                                            }
                                            else if ($name == 'Wow') {
                                                $name = 'WOW';
                                                $webpage = 'http://mynameismatthieu.com/WOW';
                                                $description = 'wow';
                                            }

                                            if (DB::table('indikator_frontend_plugins')->where('name', $name)->where('language', 1)->count() > 0 || substr_count($name, 'Bootstrap/js/') > 0 || $name == 'Script' || $name == 'Theme' || $name == 'Theme-functions' || $name == 'Custom' || $name == 'App' || $name == 'Main' || $name == 'Own') {
                                                continue;
                                            }

                                            $this->insertToDatabase($name, $webpage, '', 1, $theme, $description);

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

    public function insertToDatabase($name = '', $webpage = '', $version = '', $language = 1, $theme = '', $description = '')
    {
        DB::table('indikator_frontend_plugins')->insertGetId([
            'name' => $name,
            'webpage' => $webpage,
            'version' => $version,
            'language' => $language,
            'theme' => $theme,
            'description' => Lang::get('indikator.plugins::lang.3rd_plugin.'.$description),
            'common' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
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

            if (!in_array(App::getLocale(), $common)) {
                $size = str_replace('.', ',', $size);
            }

            return $size.' '.$name[$i];
        }

        $size = '0 '.'B';

        return $size;
    }
}
