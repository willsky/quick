<?php
namespace Quick\Controller;

class Api extends Factory
{
    protected function initView(){
        $this->view = \Quick\View\Api::instance();
    }
    
    protected function render($data = NULL) {

        if (!$this->view) {
            $this->view = \Quick\View\Api::instance();
        }
        
        if (is_array($data)) {
           $this->view->set($data); 
        }
        
        parent::render();
    }
}
