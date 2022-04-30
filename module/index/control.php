<?php
class index extends control
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->view->string = 'test';

        $this->display();
    }
}

