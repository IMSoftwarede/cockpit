<?php

namespace LimeExtra;

class App extends \Lime\App {


    public function __construct ($settings = array()) {

        $settings["helpers"]  = array_merge(array(
            "assets" => "Lime\\Helper\\Assets",
            "cache"  => "Lime\\Helper\\Cache",
            "filesystem"  => "Lime\\Helper\\Filesystem",
            "image"  => "Lime\\Helper\\Image",
            "i18n"   => "Lime\\Helper\\I18n",
            "utils"  => "Lime\\Helper\\Utils",
        ), isset($settings["helpers"]) ? $settings["helpers"] : array());

        parent::__construct($settings);

        $this["modules"] = new \ArrayObject(array());

        $this("session")->init();
    }

    public function loadModules($dir) {

        $modules = array();

        if(file_exists($dir)){

            // load modules
            foreach (new \DirectoryIterator($dir) as $module) {

                if($module->isFile() || $module->isDot()) continue;

                $m = strtolower($module);

                $this->path($m, $module->getPathname());
                $this["modules"][$m] = new Module($this);

                $this->bootModule($module->getPathname()."/bootstrap.php", $this["modules"][$m]);

                $modules[] = $module->getBasename();
            }

            $this["autoload"]->append($dir);

        }else{
            $modules = false;
        }


        return $modules;
    }

    public function module($name) {
        return $this["modules"]->offsetExists($name) && $this["modules"][$name] ? $this["modules"][$name] : null;
    }

    protected function bootModule($bootfile, $module) {

        $app = $this;

        require($bootfile);
    }


    /**
    * Render view.
    * @param  String $template Path to view
    * @param  Array  $slots   Passed variables
    * @return String               Rendered view
    */
    public function view($template, $slots = array()) {

        $renderer     = $this->renderer();

        $slots["app"] = $this;
        $layout       = $this->layout;

        if (strpos($template, ' with ') !== false ) {
            list($template, $layout) = explode(' with ', $template, 2);
        }

        if (strpos($template, ':') !== false && $file = $this->path($template)) {
            $template = $file;
        }

        $extend = function($from) use(&$layout) {
            $layout = $from;
        };

        $output = $renderer->file($template, $slots);

        if ($layout) {

            if (strpos($layout, ':') !== false && $file = $this->path($layout)) {
                $layout = $file;
            }

            $slots["content_for_layout"] = $output;

            $output = $renderer->file($layout, $slots);
        }

        return $output;
    }

    public function renderer() {

        static $renderer;

        if (!$renderer)  {
            $renderer = new \Lexy();

            //register app helper functions
            $renderer->extend(function($content){

                $content = preg_replace('/(\s*)@base\((.+?)\)/'   , '$1<?php $app->base($2); ?>', $content);
                $content = preg_replace('/(\s*)@route\((.+?)\)/'  , '$1<?php $app->route($2); ?>', $content);
                $content = preg_replace('/(\s*)@assets\((.+?)\)/' , '$1<?php $app("assets")->style_and_script($2); ?>', $content);
                $content = preg_replace('/(\s*)@render\((.+?)\)/' , '$1<?php echo $app->view($2); ?>', $content);
                $content = preg_replace('/(\s*)@trigger\((.+?)\)/', '$1<?php $app->trigger($2); ?>', $content);
                $content = preg_replace('/(\s*)@lang\((.+?)\)/'   , '$1<?php echo $app("i18n")->get($2); ?>', $content);

                $content = preg_replace('/(\s*)@start\((.+?)\)/'   , '$1<?php $app->start($2); ?>', $content);
                $content = preg_replace('/(\s*)@end\((.+?)\)/'   , '$1<?php $app->end($2); ?>', $content);
                $content = preg_replace('/(\s*)@block\((.+?)\)/'   , '$1<?php $app->block($2); ?>', $content);

                return $content;
            });
        }

        return $renderer;
    }
}