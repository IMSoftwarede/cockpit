<?php

namespace Lime\Helper;


class Filesystem extends \Lime\Helper {

    public function ls() {
        $pattern = null;
        $dir     = null;

        $args = func_get_args();
        $lst  = array();

        switch(count($args)){
            case 0:
                $dir = getcwd();
            case 1:
                $dir = $this->app->path($args[0]);
                break;
            case 2:
                $pattern = $args[0];
                $dir = $this->app->path($args[1]);
                break;
            default:
                return $lst;
        }

        if(!$dir || !file_exists($dir)) {
            return $lst;
        }

        foreach (new \DirectoryIterator($dir) as $file) {

            if($file->isDot()) continue;
            if($pattern && !fnmatch($pattern, $file->getBasename())) continue;

            $lst[] = new \SplFileObject($file->getRealPath());
        }

        return $lst;
    }



    public function mkdir($path, $mode = 0777) {

        if (strpos($path, ':') !== false) {
            list($namespace, $additional) = explode(":", $path, 2);
            $dir = $this->app->path("{$namspace}:").$additional;
        } else {
            $dir = $path;
        }

        if (!is_dir($dir) && !@mkdir($dir, $mode, true)) {
            return false;
        }

        return true;
    }


    public static function delete($path) {

        $path = $this->app->path($path);

        if (is_file($path) || is_link($path)) {
            $func = DIRECTORY_SEPARATOR === '\\' && is_dir($path) ? 'rmdir' : 'unlink';
            if (!@$func($path)) {
                throw new \Exception("Unable to delete: {$path}.");
            }
        } elseif (is_dir($path)) {
            foreach (new \FilesystemIterator($path) as $item) {
                $this->delete($item);
            }
            if (!@rmdir($path)) {
                throw new \Exception("Unable to delete directory: {$path}.");
            }
        }
    }


    public static function rename($path, $newpath, $overwrite = true) {

        $path = $this->app->path($path);

        if (!$overwrite && file_exists($newpath)) {
            return false;

        } elseif (!file_exists($path)) {
            return false;

        } else {
            $this->mkdir(dirname($newpath));
            $this->delete($newpath);
            if (!@rename($path, $newpath)) {
               return false;
            }
        }

        return true;
    }

}


if(!function_exists('fnmatch')) {
    function fnmatch($pattern, $string){
        return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."$#i", $string);
    }
}