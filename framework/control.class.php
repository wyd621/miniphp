<?php
class control
{
    public $app;
    public $config;
    public $dbh;
    public $output;

    public function __construct($moduleName = '', $methodName = '')
    {
        global $app;

        $this->app    = $app;
        $this->config = $app->config;
        $this->dbh    = $app->dbh;

        $this->setModuleName($moduleName);
        $this->setMethodName($methodName);
        $this->loadModel($this->moduleName);

        $this->view = new stdclass();
        $this->view->app    = $app;
        $this->view->config = $config;
        $this->view->title  = '';
    }

    public function setModuleName($moduleName = '')
    {
        $this->moduleName = $moduleName ? strtolower($moduleName) : $this->app->moduleName;
    }

    public function setMethodName($methodName = '')
    {
        $this->methodName = $methodName ? strtolower($methodName) : $this->app->methodName;
    }

    public function loadModel($moduleName = '')
    {
        if(empty($moduleName)) $moduleName = $this->moduleName;

        global $loadedModels;
        if(isset($loadedModels[$moduleName]))
        {
            $this->$moduleName = $loadedModels[$moduleName];
            return $this->$moduleName;
        }

        $modelFile = $this->app->moduleRoot . $moduleName . DS . 'model.php';

        if(!file_exists($modelFile)) return false;

        $modelClass = $moduleName . 'model';
        if(!class_exists($modelClass)) $this->app->triggerError(" The model $modelClass not found", __FILE__, __LINE__, $exit = true);

        $loadedModels[$moduleName] = new $modelClass();
        $this->$moduleName = $loadedModels[$moduleName];
        return $this->$moduleName;
    }

    public function display($moduleName = '', $methodName = '')
    {
        if(empty($this->output)) $this->parse($moduleName, $methodName);
        echo $this->output;
    }

    public function parse($moduleName = '', $methodName = '')
    {
        if(empty($moduleName)) $moduleName = $this->moduleName;
        if(empty($methodName)) $methodName = $this->methodName;

        $this->parseDefault($moduleName, $methodName);

        return $this->output;
    }

    public function parseDefault($moduleName, $methodName)
    {
        $viewFile  = $this->setViewFile($moduleName, $methodName);

        $currentPWD = getcwd();
        chdir(dirname($viewFile));

        extract((array)$this->view);
        ob_start();
        include $viewFile;
        $this->output .= ob_get_contents();
        ob_end_clean();

        chdir($currentPWD);
    }

    public function setViewFile($moduleName, $methodName)
    {
        $moduleName = strtolower(trim($moduleName));
        $methodName = strtolower(trim($methodName));

        $modulePath   = $this->app->moduleRoot . $moduleName . DS;
        $mainViewFile = $modulePath . 'view' . DS . $methodName . '.html.php';
        return $mainViewFile;
    }
}
