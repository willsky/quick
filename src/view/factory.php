<?php 
namespace Quick\View;
use\Quick\Core\Singleton;

abstract class Factory extends Singleton {
    protected $data = array();
    protected $isRender = FALSE;

    abstract public function render($tpl = NULL);

    public function set($key, $value = NULL) {
        if (is_array($key)) {
            foreach($key as $_k => $_v) {
                $this->set($_k, $_v);
            }
        } else {
            $this->data[$key] = $value;
        }
    }
}