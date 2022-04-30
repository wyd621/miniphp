<?php
class model
{
    public $app;
    public $config;
    public $dbh;

    public function __construct($moduleName = '', $methodName = '')
    {
        global $app;

        $this->app    = $app;
        $this->config = $app->config;
        $this->dbh    = $app->dbh;
    }
}
