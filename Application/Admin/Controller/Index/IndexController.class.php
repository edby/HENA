<?php
namespace Admin\Controller\Index;

use Admin\Controller\CommonController;

class IndexController extends CommonController {
    public function index(){
        $this->display();
    }
    public function welcome(){
    	$this->display();
    }
}