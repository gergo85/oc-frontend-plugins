<?php namespace Indikator\Plugins\Controllers;

use Backend\Classes\Controller;
use Backend\Models\UserPreference;
use BackendMenu;
use Cms\Classes\Theme;
use System\Classes\SettingsManager;
use Indikator\Plugins\Models\Frontend as FrontendPlugins;
use File;
use Flash;
use Lang;
use App;
use Redirect;

class Frontend extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $bodyClass = 'compact-container';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Indikator.Plugins', 'frontend');

        $stat = $this->getStat();

        $this->vars['themeFolder'] = $stat['themeFolder'];
        $this->vars['assets']      = $stat['assets'];
        $this->vars['stat']        = $stat['stat'];
        $this->vars['size']        = $stat['size'];
        $this->vars['count']       = $stat['count'];
        $this->vars['pieces']      = $stat['pieces'];
        $this->vars['preferences'] = $stat['preferences'];
    }

    // Get stat
    public function getStat()
    {
        // Base variables
        $theme = Theme::getEditTheme()->getDirName();
        $result['themeFolder'] = 'themes/'.$theme.'/assets/';

        // Assets folder
        $result['assets']['js']  = File::exists($result['themeFolder'].'js') ? 'js' : 'javascript';
        $result['assets']['css'] = File::exists($result['themeFolder'].'css') ? 'css' : 'stylesheets';
        $result['assets']['img'] = File::exists($result['themeFolder'].'images') ? 'images' : 'img';

        // Stats
        $result['stat'] = [
            'js'  => $this->pluginFolderStat($result['themeFolder'].$result['assets']['js']),
            'css' => $this->pluginFolderStat($result['themeFolder'].$result['assets']['css']),
            'img' => $this->pluginFolderStat($result['themeFolder'].$result['assets']['img'])
        ];

        // Sizes
        $result['size'] = [
            'js'  => explode(' ', $this->pluginFileSize($result['stat']['js']['size'])),
            'css' => explode(' ', $this->pluginFileSize($result['stat']['css']['size'])),
            'img' => explode(' ', $this->pluginFileSize($result['stat']['img']['size']))
        ];

        // Assets
        $result['count'] = [
            'js'  => FrontendPlugins::where('language', '1')->count(),
            'css' => FrontendPlugins::where('language', '2')->count(),
            'php' => FrontendPlugins::where('language', '3')->count()
        ];

        // Pieces
        $result['pieces'] = [
            'total'   => FrontendPlugins::count(),
            'files'   => $result['stat']['js']['files'] + $result['stat']['css']['files'] + $result['stat']['img']['files'],
            'folders' => $result['stat']['js']['folders'] + $result['stat']['css']['folders'] + $result['stat']['img']['folders'],
            'font'    => FrontendPlugins::where('language', 4)->count()
        ];

        // Preferences
        $preferenceModel = UserPreference::forUser();
        $result['preferences'] = $preferenceModel->get('backend::backend.preferences');

        // Finish
        return $result;
    }

    // Search plugins
    public function onSearchPlugins()
    {
        // Error
        if (!method_exists('DOMDocument', '__construct')) {
            Flash::error(Lang::get('indikator.plugins::lang.flash.error'));
            return;
        }

        // Settings
        libxml_use_internal_errors(true);
        $count = 0;

        // Themes
        if ($themes = opendir(base_path().'/themes')) {
            while (false !== ($theme = readdir($themes))) {
                if ($theme != '.' && $theme != '..') {

                    // Layouts
                    if ($layouts = opendir(base_path().'/themes/'.$theme.'/layouts')) {
                        while (false !== ($layout = readdir($layouts))) {
                            if ($layout != '.' && $layout != '..' && filetype(base_path().'/themes/'.$theme.'/layouts/'.$layout) != 'dir') {

                                // File
                                $html = File::get(base_path().'/themes/'.$theme.'/layouts/'.$layout);
                                $html = substr($html, strpos($html, '==') + 2);

                                // Empty
                                if ($html == '') {
                                    continue;
                                }

                                // Content
                                $dom = new \DOMDocument;
                                $dom->loadHTML($html);

                                // Stylesheet
                                foreach ($dom->getElementsByTagName('link') as $item) {
                                    $href = $item->getAttribute('href');

                                    // Fonts
                                    if (substr_count($href, 'fonts.googleapis') == 1) {
                                        $href = substr($href, 8);
                                        $start = strpos($href, 'family=') + 7;

                                        // Calculate the end position
                                        if (substr_count($href, ':') == 1) {
                                            $end = strpos($href, ':') - $start;
                                        }
                                        else if (substr_count($href, '&') == 1) {
                                            $end = strpos($href, '&') - $start;
                                        }
                                        else {
                                            $end = strlen($href) - $start;
                                        }

                                        // Remove the "plus" sign
                                        $name = str_replace('+', ' ', substr($href, $start, $end));

                                        // Check duplication
                                        if (FrontendPlugins::where('name', $name)->where('language', 4)->count() > 0) {
                                            continue;
                                        }

                                        // Add to database
                                        $this->insertToDatabase($name, 'https://www.google.com/fonts', 'none', 4, $theme, 'web_font');

                                        // Plugin couter
                                        $count++;
                                    }

                                    // Bootstrap
                                    else if (substr_count($href, 'maxcdn.bootstrapcdn') == 1) {

                                        // Check duplication
                                        if (FrontendPlugins::where('name', 'Bootstrap')->where('language', 3)->count() > 0) {
                                            continue;
                                        }

                                        // Plugin details
                                        $url = explode('/', substr($href, strpos($href, '//') + 2));
                                        $data = $this->getPluginDetails('Bootstrap_CSS');

                                        // Add to database
                                        $this->insertToDatabase($data['name'], $data['webpage'], $url[2], 3, $theme, $data['desc']);

                                        // Plugin couter
                                        $count++;
                                    }

                                    // Popular
                                    else if (substr_count($href, '{{ [') == 1 || substr_count($href, '{{[') == 1) {
                                        $href = preg_replace('/\s+/', '', trim($href));

                                        // Plugin filename
                                        $file = [
                                            'animate',
                                            'bootstrap.css',
                                            'bootstrap.min.css',
                                            'font-awesome',
                                            'normalize'
                                        ];

                                        // Plugin name
                                        $name = [
                                            'Animate',
                                            'Bootstrap_CSS',
                                            'Bootstrap_CSS',
                                            'Font_Awesome',
                                            'Normalize'
                                        ];

                                        // Check availability
                                        foreach ($file as $key => $value) {
                                            if (substr_count($href, $value) > 0) {

                                                // Plugin details
                                                $data = $this->getPluginDetails($name[$key]);

                                                // Check duplication
                                                if (FrontendPlugins::where('name', $data['name'])->count() > 0) {
                                                    continue;
                                                }

                                                // Add to database
                                                $this->insertToDatabase($data['name'], $data['webpage'], 'none', 3, $theme, $data['desc']);
                                            }
                                        }
                                    }
                                }

                                // JavaScript
                                foreach ($dom->getElementsByTagName('script') as $item) {
                                    $src = $item->getAttribute('src');

                                    // External URL
                                    if (substr_count($src, '//') == 1) {
                                        $url = explode('/', substr($src, strpos($src, '//') + 2));
                                        $data['webpage'] = $data['desc'] = '';

                                        // jQuery
                                        if (substr_count($src, 'code.jquery') == 1) {
                                            $data = $this->getPluginDetails('jQuery');
                                            $data['version'] = str_replace(['.min', '.js'], '', substr($url[1], 7));
                                        }

                                        // jQuery
                                        else if (substr_count($src, 'unpkg.com/jquery') == 1) {
                                            $data = $this->getPluginDetails('jQuery');
                                            $data['version'] = '';
                                        }

                                        // Google
                                        else if (substr_count($src, 'ajax.aspnetcdn') == 1) {
                                            $data = $this->getPluginDetails(ucfirst($url[2]));
                                            $data['version'] = substr($url[3], 7, 5);
                                        }

                                        // Microsoft
                                        else if (substr_count($src, 'ajax.googleapis') == 1) {
                                            $data = $this->getPluginDetails(ucfirst($url[3]));
                                            $data['version'] = $url[4];
                                        }

                                        // CDNJS
                                        else if (substr_count($src, 'cdnjs.cloudflare') == 1) {
                                            $data = $this->getPluginDetails(ucfirst($url[3]));
                                            $data['version'] = $url[4];
                                        }

                                        // jsDelivr
                                        else if (substr_count($src, 'cdn.jsdelivr') == 1) {
                                            $data = $this->getPluginDetails(ucfirst($url[1]));
                                            $data['version'] = $url[2];
                                        }

                                        // Bootstrap
                                        else if (substr_count($src, 'maxcdn.bootstrapcdn') == 1) {
                                            $data = $this->getPluginDetails('Bootstrap');
                                            $data['version'] = $url[2];
                                        }

                                        // CKEditor
                                        else if (substr_count($src, 'cdn.ckeditor') == 1) {
                                            $data = $this->getPluginDetails('CKEditor');
                                            $data['version'] = $url[1];
                                        }

                                        // TinyMCE
                                        else if (substr_count($src, 'cdn.tinymce') == 1) {
                                            $data = $this->getPluginDetails('TinyMCE');
                                            $data['version'] = $url[1];
                                        }

                                        // DataTables
                                        else if (substr_count($src, 'cdn.datatables') == 1) {
                                            $data = $this->getPluginDetails('DataTables');
                                            $data['version'] = $url[1];
                                        }

                                        // AngularJS
                                        else if (substr_count($src, 'unpkg.com/angular') == 1) {
                                            $data = $this->getPluginDetails('AngularJS');
                                            $data['version'] = '';
                                        }

                                        // React
                                        else if (substr_count($src, 'unpkg.com/react') == 1) {
                                            $data = $this->getPluginDetails('React');
                                            $data['version'] = '';
                                        }

                                        // Vue.js
                                        else if (substr_count($src, 'unpkg.com/vue') == 1) {
                                            $data = $this->getPluginDetails('VueJS');
                                            $data['version'] = '';
                                        }

                                        // Masonry
                                        else if (substr_count($src, 'unpkg.com/masonry') == 1) {
                                            $data = $this->getPluginDetails('Masonry');
                                            $data['version'] = '';
                                        }

                                        // Check duplication
                                        if (FrontendPlugins::where('name', $data['name'])->whereOr('name', lcfirst($data['name']))->where('language', 1)->count() > 0) {
                                            $this->updateToDatabase(FrontendPlugins::where('name', $data['name'])->whereOr('name', lcfirst($data['name']))->where('language', 1)->pluck('id'), $data['version']);
                                            continue;
                                        }

                                        // Add to database
                                        $this->insertToDatabase($data['name'], $data['webpage'], $data['version'], 1, $theme, $data['desc']);

                                        // Plugin couter
                                        $count++;
                                    }

                                    // Self hosted
                                    else if (substr_count($src, '{{ [') == 1 || substr_count($src, '{{[') == 1) {
                                        $src = preg_replace('/\s+/', '', trim($src));
                                        $items = explode(',', str_replace("'", "", $src));

                                        foreach ($items as $js) {
                                            // Get file path
                                            $js = trim(str_replace([
                                                '{{ [',
                                                '{{[',
                                                ']|theme}}',
                                                ']|theme }}',
                                                ']| theme}}',
                                                ']| theme }}',
                                                '] |theme}}',
                                                '] |theme }}',
                                                '] | theme}}',
                                                '] | theme }}'
                                            ], '', $js));

                                            // Get file name
                                            $name = trim(str_replace([
                                                '.custom',
                                                '-custom',
                                                '.min',
                                                '-min',
                                                '.pack',
                                                '-pack',
                                                '.core',
                                                '-core',
                                                '.bundle',
                                                '-bundle',
                                                '.pkgd',
                                                '-pkgd',
                                                '.js',
                                                '.jquery',
                                                '-jquery',
                                                'jquery.',
                                                'jquery-',
                                                'bootstrap.',
                                                'bootstrap-'
                                            ], '', $js));

                                            // Remove subfolders
                                            if (substr_count($name, '/') > 0) {
                                                $path = explode('/', $name);
                                                $name = $path[count($path) - 1];
                                            }

                                            // Plugin details
                                            $data = $this->getPluginDetails($name, 'themes/'.$theme.'/'.$js);

                                            // Not allow file names
                                            $banned = ['@framework', '@framework extras', 'Script', 'Scripts', 'Plugin', 'Plugins', 'Theme', 'Theme-functions', 'Theme-options', 'Custom', 'App', 'Main', 'Own'];

                                            // Check duplication
                                            if (FrontendPlugins::where('name', $data['name'])->where('language', 1)->count() > 0) {
                                                $this->updateToDatabase(FrontendPlugins::where('name', $data['name'])->where('language', 1)->pluck('id'), $data['version']);
                                                continue;
                                            }

                                            // Not allow file name
                                            else if (in_array($data['name'], $banned)) {
                                                continue;
                                            }

                                            // Add to database
                                            $this->insertToDatabase($data['name'], $data['webpage'], $data['version'], 1, $theme, $data['desc']);

                                            // Plugin couter
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

        // Flash message
        Flash::success(str_replace('%s', $count, Lang::get('indikator.plugins::lang.flash.search')));

        // Refresh the page
        if ($count > 0) {
            return Redirect::refresh();
        }
    }

    // Get details
    public function getPluginDetails($name = '', $path = '')
    {
        // Supported plugins
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
            'ckeditor' => [
                'name'    => 'CKEditor',
                'webpage' => 'http://ckeditor.com'
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
            'jquery_migrate' => [
                'name'    => 'jQuery Migrate',
                'webpage' => 'https://github.com/jquery/jquery-migrate'
            ],
            'angularjs' => [
                'name'    => 'AngularJS',
                'webpage' => 'https://angularjs.org'
            ],
            'react' => [
                'name'    => 'React',
                'webpage' => 'https://facebook.github.io/react'
            ],
            'vuejs' => [
                'name'    => 'Vue.js',
                'webpage' => 'https://vuejs.org'
            ],
            'baguettebox' => [
                'name'    => 'baguetteBox',
                'webpage' => 'https://feimosi.github.io/baguetteBox.js'
            ],
            'bxslider' => [
                'name'    => 'bxSlider',
                'webpage' => 'http://bxslider.com'
            ],
            'isotope' => [
                'name'    => 'Isotope',
                'webpage' => 'http://isotope.metafizzy.co'
            ],
            'masonry' => [
                'name'    => 'Masonry',
                'webpage' => 'https://masonry.desandro.com'
            ],
            'modernizr' => [
                'name'    => 'Modernizr',
                'webpage' => 'https://modernizr.com'
            ],
            'moment' => [
                'name'    => 'Moment',
                'webpage' => 'http://momentjs.com'
            ],
            'owl_carousel' => [
                'name'    => 'OWL Carousel',
                'webpage' => 'http://www.owlgraphic.com/owlcarousel'
            ],
            'tooltipster' => [
                'name'    => 'Tooltipster',
                'webpage' => 'http://iamceege.github.io/tooltipster'
            ],
            'waypoints' => [
                'name'    => 'Waypoints',
                'webpage' => 'http://imakewebthings.com/waypoints'
            ],
            'wow' => [
                'name'    => 'WOW',
                'webpage' => 'http://mynameismatthieu.com/WOW'
            ]
        ];

        // Formating the name
        $code = strtolower($name);

        // Modify the special names 
        if ($code == '@jquery') {
            $code = 'jquery';
        }
        else if ($code == 'jqueryui' || $code == 'ui') {
            $code = 'jquery_ui';
        }
        else if ($code == 'migrate') {
            $code = 'jquery_migrate';
        }
        else if ($code == 'angular') {
            $code = 'angularjs';
        }
        else if ($code == 'bootstrap') {
            $code = 'bootstrap_js';
        }
        else if ($code == 'moment-with-locales') {
            $code = 'moment';
        }
        else if ($code == 'owl.carousel' || $code == 'owl') {
            $code = 'owl_carousel';
        }

        // There is no version
        if ($path == '') {
            $version = 'none';
        }

        // jQuery build-in combiner
        else if ($name == '@jquery') {
            $version = '2.1.3';
        }

        // Get version from name
        else if (is_numeric(substr($code, -3, 1)) && is_numeric(substr($code, -1, 1))) {
            $array = explode('-', $code);
            $name = str_replace($array[count($array) - 1], '', $name);
            $version = $array[count($array) - 1];
        }

        // Get version from file
        else {
            $version = $this->getPluginVersion($path);
        }

        // Empty details
        if (!isset($plugin[$code])) {
            return [
                'name'    => ucfirst(str_replace(['.', '-', '_'], ' ', $name)),
                'webpage' => '',
                'version' => $version,
                'desc'    => ''
            ];
        }

        // Details of plugin
        return [
            'name'    => $plugin[$code]['name'],
            'webpage' => $plugin[$code]['webpage'],
            'version' => $version,
            'desc'    => $code
        ];
    }

    // Get version
    public function getPluginVersion($path = '')
    {
        if (!File::exists(base_path().'/'.$path)) {
            return 'none';
        }

        $content = explode(' ', substr(File::get(base_path().'/'.$path), 0, 500));
        $parts = count($content);

        for ($i = 0; $i < $parts; $i++) {
            if (strlen($content[$i]) < 12 && strlen($content[$i]) > 2 && substr_count($content[$i], '.') > 0) {
                if ($content[$i][0] == 'v') {
                    $content[$i] = substr($content[$i], 1);
                }

                if (!is_numeric(substr($content[$i], 0, 1))) {
                    continue;
                }

                return str_replace([',', ';', '+', '*', '-', '/'], '', $content[$i]);
            }
        }

        return 'none';
    }

    // Add plugin
    public function insertToDatabase($name = '', $webpage = '', $version = 'none', $language = 1, $theme = '', $description = '')
    {
        if ($description != '') {
            $description = Lang::get('indikator.plugins::lang.3rd_plugin.'.$description);
        }

        FrontendPlugins::insertGetId([
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

    // Update version
    public function updateToDatabase($id = 0, $version = '1.0')
    {
        if (!$item = FrontendPlugins::find($id)) {
            $item->update([
                'version' => $version
            ]);
        }
    }

    // Remove plugins
    public function onRemovePlugins()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $itemId) {
                if (!$item = FrontendPlugins::whereId($itemId)) {
                    continue;
                }

                $item->delete();
            }

            Flash::success(Lang::get('indikator.plugins::lang.flash.remove'));
        }

        return Redirect::refresh();
    }

    // Folder stat
    public function pluginFolderStat($folder = 'themes')
    {
        $attr['size'] = $attr['files'] = $attr['folders'] = 0;

        if (!File::exists(base_path().'/'.$folder)) {
            File::makeDirectory(base_path().'/'.$folder, 0775, true);
        }

        $elements = scandir(base_path().'/'.$folder);

        foreach ($elements as $element) {
            if ($element != '.' && $element != '..') {
                if (filetype(base_path().'/'.$folder.'/'.$element) == 'dir') {
                    $value = $this->pluginFolderStat($folder.'/'.$element);
                    $attr['size']    += $value['size'];
                    $attr['files']   += $value['files'];
                    $attr['folders'] += $value['folders'] + 1;
                }

                else {
                    $attr['size'] += File::size(base_path().'/'.$folder.'/'.$element);
                    $attr['files']++;
                }
            }
        }

        return $attr;
    }

    // File size
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

        return '0 B';
    }
}
