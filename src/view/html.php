<?php
namespace Quick\View;

class Html extends Factory
{    
    public function render($tpl = NULL) {
        if ($this->isRender) return TRUE;

        header('Content-Type: text/html; charset=' . \Quick\Core\App::instance()->get('charset'));
        extract($this->data);
        if (is_null($tpl)) {
            require(implode(DIRECTORY_SEPARATOR, 
                array(
                    VIEW_PATH, 
                    strtolower(\Quick\Core\Router::$controller), 
                    strtolower(\Quick\Core\Router::$action) . '.php')));
        } else {
            require(implode(DIRECTORY_SEPARATOR, 
                array(
                    VIEW_PATH, 
                    $tpl.'.php')));
        }
        $this->isRender = TRUE;
    }

}