<?php

namespace BfwApi\Test\Helpers;

$vendorPath = realpath(__DIR__.'/../../../vendor');
require_once($vendorPath.'/bulton-fr/bfw/test/unit/mocks/src/Module.php');

trait Module
{
    protected $module;
    
    protected function disableSomeAppSystem()
    {
        $appSystemList = $this->app->obtainAppSystemDefaultList();
        unset($appSystemList['cli']);
        $this->app->setAppSystemToInstantiate($appSystemList);
    }
    
    protected function removeLoadModules()
    {
        $runTasks = $this->app->getRunTasks();
        $allSteps = $runTasks->getRunSteps();
        unset($allSteps['moduleList']);
        $runTasks->setRunSteps($allSteps);
    }
    
    protected function createModule()
    {
        $config     = new \BFW\Config('bfw-api');
        $moduleList = $this->app->getModuleList();
        $moduleList->setModuleConfig('bfw-api', $config);
        $moduleList->addModule('bfw-api');
        
        $this->module = $moduleList->getModuleByName('bfw-api');
        
        $this->module->monolog = new \BFW\Monolog(
            'bfw-api',
            \BFW\Application::getInstance()->getConfig()
        );
        $this->module->monolog->addAllHandlers();
        
        $config->setConfigForFilename(
            'config.php',
            [
                'urlPrefix'  =>  '/api',
                'useRest'    => true,
                'useGraphQL' => false
            ]
        );
        
        $config->setConfigForFilename(
            'routes.php',
            [
                'routes' =>  [
                    '/books' => [
                        'className'  => '\BfwApi\test\unit\mocks\Books',
                        'httpMethod' => ['GET']
                    ],
                    '/books/{bookId:\d+}' => [
                        'className' => 'Books'
                    ],
                    '/books/{bookId:\d+}/comments' => [
                        'className'  => 'BooksComments',
                        'httpMethod' => ['GET', 'POST']
                    ],
                    '/books/{bookId:\d+}/comments/{commentId:\d+}' => [
                        'className'  => 'BooksComments',
                        'httpMethod' => ['GET']
                    ],
                    '/author' => [
                        'httpMethod' => ['GET']
                    ],
                    '/editors' => [
                        'className'  => '\BfwApi\test\unit\mocks\Editors',
                        'httpMethod' => ['GET']
                    ],
                    '/libraries' => [
                        'className'  => '\BfwApi\test\unit\mocks\Libraries',
                        'httpMethod' => ['GET']
                    ]
                ]
            ]
        );
    }
}
