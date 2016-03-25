<?php 
namespace Quick\Controller;

class Html extends Factory {
    protected function initView() {
        $this->view = \Quick\View\Html::instance();
    }
}
