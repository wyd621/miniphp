<?php
class helper
{
    public static function createLink($module, $method, $params = '')
    {
        global $app;
        $vars = array();
        if(!is_array($params)) parse_str($params, $vars);
        return $app->config->webRoot . $module . '-' . $method . join('-', $vars) . '.html';
    }
}
function a()
{
    $args = func_get_args();
    foreach($args as $arg) echo "<pre>" . var_export($arg, true) . "</pre>";
}

function getWebRoot()
{
    $path = $_SERVER['SCRIPT_NAME'];
    $path = substr($path, 0, (strrpos($path, '/') + 1));
    $path = str_replace('\\', '/', $path);
    return $path;
}

function __autoload($className)
{
    global $app;
    $classFile = $app->libPath . $className . DS . "{$className}.class.php";
    if(file_exists($classFile)) include $classFile;
}
