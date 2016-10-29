<?php

namespace BfwApi\test\unit;

use \atoum;

require_once(__DIR__.'/../../../../vendor/autoload.php');
require_once(__DIR__.'/../../../../vendor/bulton-fr/bfw/test/unit/mocks/src/class/ConfigForceDatas.php');
require_once(__DIR__.'/../../../../vendor/bulton-fr/bfw/test/unit/mocks/src/class/Module.php');

class BfwApi extends atoum
{
    /**
     * @var $class : Instance de la class
     */
    protected $class;
    
    protected $module;
    
    /**
     * Instanciation de la class avant chaque méthode de test
     */
    public function beforeTestMethod($testMethod)
    {
        define('CTRL_DIR', 'controllers/');
        define('CONFIG_DIR', 'config/');
        define('MODULES_DIR', 'modules/');
        
        $config = new \BFW\test\unit\mocks\ConfigForceDatas('unit_test');
        
        $config->forceConfig(
            'config',
            (object) [
                'urlPrefix' =>  '/api'
            ]
        );
        
        $config->forceConfig(
            'routes',
            (object) [
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
        
        $this->module = new \BFW\test\unit\mocks\Module('unit_test', false);
        $this->module->forceConfig($config);
        
        if ($testMethod === 'testConstruct') {
            return;
        }
        
        $this->class = new \BfwApi\test\unit\mocks\BfwApi($this->module);
    }
    
    public function testConstructor()
    {
        $this->assert('test BfwApi::__construct')
            ->if($this->class = new \BfwApi\test\unit\mocks\BfwApi($this->module))
            ->then
            ->object($this->class->module)
                ->isIdenticalTo($this->module)
            ->object($this->class->config)
                ->isInstanceOf('\BFW\Config')
            ->object($this->class->dispatcher)
                ->isInstanceOf('\FastRoute\Dispatcher');
    }
    
    public function testAddRoutesToCollector()
    {
        $this->assert('test BfwApi::addRoutesToCollector')
            ->if($this->class->setDispatcher(
                \FastRoute\simpleDispatcher(
                    [$this->class, 'addRoutesToCollector'],
                    ['dispatcher' => '\\BfwApi\\test\\unit\\mocks\\Dispatcher']
                )
            ))
            ->given($dispatcher = $this->class->getDispatcher())
            ->given($staticRouteMap = $dispatcher->staticRouteMap)
            ->given($variableRouteData = $dispatcher->variableRouteData)
            
            ->array($staticRouteMap)
                ->isEqualTo([
                    'GET' => [
                        '/api/books' => [
                            'className' => '\BfwApi\test\unit\mocks\Books'
                        ],
                        '/api/author' => [],
                        '/api/editors' => [
                            'className' => '\BfwApi\test\unit\mocks\Editors'
                        ],
                        '/api/libraries' => [
                            'className'  => '\BfwApi\test\unit\mocks\Libraries'
                        ]
                    ]
                ])
            ->array($variableRouteData)
                ->isEqualTo([
                    'GET' => [
                        0 => [
                            'regex' => '~^(?|/api/books/(\d+)|/api/books/(\d+)/comments()|/api/books/(\d+)/comments/(\d+)())$~',
                            'routeMap' => [
                                2 => [
                                    0 => ['className' => 'Books'],
                                    1 => ['bookId' => 'bookId']
                                ],
                                3 => [
                                    0 => ['className' => 'BooksComments'],
                                    1 => ['bookId' => 'bookId']
                                ],
                                4 => [
                                    0 => ['className' => 'BooksComments'],
                                    1 => [
                                        'bookId' => 'bookId',
                                        'commentId' => 'commentId'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'POST' => [
                        0 => [
                            'regex' => '~^(?|/api/books/(\d+)|/api/books/(\d+)/comments())$~',
                            'routeMap' => [
                                2 => [
                                    0 => ['className' => 'Books'],
                                    1 => ['bookId' => 'bookId']
                                ],
                                3 => [
                                    0 => ['className' => 'BooksComments'],
                                    1 => ['bookId' => 'bookId']
                                ]
                            ]
                        ]
                    ],
                    'PUT' => [
                        0 => [
                            'regex' => '~^(?|/api/books/(\d+))$~',
                            'routeMap' => [
                                2 => [
                                    0 => ['className' => 'Books'],
                                    1 => ['bookId' => 'bookId']
                                ]
                            ]
                        ]
                    ],
                    'DELETE' => [
                        0 => [
                            'regex' => '~^(?|/api/books/(\d+))$~',
                            'routeMap' => [
                                2 => [
                                    0 => ['className' => 'Books'],
                                    1 => ['bookId' => 'bookId']
                                ]
                            ]
                        ]
                    ]
                ]);
    }
    
    public function testObtainClassNameForCurrentRoute()
    {
        $request = \BFW\Request::getInstance();
        
        $this->assert('test BfwApi::obtainClassNameForCurrentRoute for empty request')
            ->variable($this->class->callObtainClassNameForCurrentRoute())
                ->isNull()
            ->integer(http_response_code())
                ->isEqualTo(404);
        
        $this->assert('test BfwApi::obtainClassNameForCurrentRoute for an existing request')
            ->if($_SERVER['REQUEST_URI'] = 'http://bfw.bulton.fr/api/books')
            ->and($_SERVER['REQUEST_METHOD'] = 'GET')
            ->and($request->runDetect())
            ->string($this->class->callObtainClassNameForCurrentRoute())
                ->isEqualTo('\BfwApi\test\unit\mocks\Books')
            ->integer(http_response_code())
                ->isEqualTo(200);
        
        $this->assert('test BfwApi::obtainClassNameForCurrentRoute for an existing request but uncorrect method')
            ->if($_SERVER['REQUEST_URI'] = 'http://bfw.bulton.fr/api/books')
            ->and($_SERVER['REQUEST_METHOD'] = 'PUT')
            ->and($request->runDetect())
            ->variable($this->class->callObtainClassNameForCurrentRoute())
                ->isNull()
            ->integer(http_response_code())
                ->isEqualTo(405);
        
        $this->assert('test BfwApi::obtainClassNameForCurrentRoute for an existing request')
            ->if($_SERVER['REQUEST_URI'] = 'http://bfw.bulton.fr/api/books')
            ->and($_SERVER['REQUEST_METHOD'] = 'PUT')
            ->and($request->runDetect())
            ->variable($this->class->callObtainClassNameForCurrentRoute())
                ->isNull()
            ->integer(http_response_code())
                ->isEqualTo(405);
        
        $this->assert('test BfwApi::obtainClassNameForCurrentRoute for an existing request with an exception')
            ->if($_SERVER['REQUEST_URI'] = 'http://bfw.bulton.fr/api/author')
            ->and($_SERVER['REQUEST_METHOD'] = 'GET')
            ->and($request->runDetect())
            ->given($class = $this->class)
            ->exception(function() use ($class) {
                $class->callObtainClassNameForCurrentRoute();
            })
                ->hasMessage('className not define for uri /api/author');
    }
    
    public function testRun()
    {
        $request = \BFW\Request::getInstance();
        
        $this->assert('test BfwApi::run with class exception')
            ->if($_SERVER['REQUEST_URI'] = 'http://bfw.bulton.fr/api/libraries')
            ->and($_SERVER['REQUEST_METHOD'] = 'GET')
            ->and($request->runDetect())
            ->given($class = $this->class)
            ->then
            ->exception(function() use ($class) {
                $class->run();
            })
                ->hasMessage('Class \BfwApi\test\unit\mocks\Libraries not found.');

        $this->assert('test BfwApi::run with method exception')
            ->if($_SERVER['REQUEST_URI'] = 'http://bfw.bulton.fr/api/editors')
            ->and($_SERVER['REQUEST_METHOD'] = 'GET')
            ->and($request->runDetect())
            ->given($class = $this->class)
            ->then
            ->exception(function() use ($class) {
                $class->run();
            })
                ->hasMessage('Method getRequest not found in class \BfwApi\test\unit\mocks\Editors.');
    }
    
    public function testRunWithoutError()
    {
        $request = \BFW\Request::getInstance();
        
        $this->assert('test BfwApi::run without error')
            ->if($_SERVER['REQUEST_URI'] = 'http://bfw.bulton.fr/api/books')
            ->and($_SERVER['REQUEST_METHOD'] = 'GET')
            ->and($request->runDetect())
            ->given($class = $this->class)
            ->then
            ->output(function() use ($class) {
                $class->run();
            })
                ->isEqualTo('List of all books.');
    }
    
    public function testUpdate()
    {
        $this->assert('test BfwApi::update without event')
            ->if($this->class->update(new \BFW\Subjects))
            ->then
            ->boolean(http_response_code())
                ->isFalse();
        
        $this->assert('test BfwApi::update with event')
            ->if($_SERVER['REQUEST_URI'] = 'http://bfw.bulton.fr/api/books')
            ->and($_SERVER['REQUEST_METHOD'] = 'GET')
            ->given($class = $this->class)
            ->given($subject = new \BFW\Subjects)
            ->and($subject->setAction('bfw_run_finish'))
            ->then
            ->output(function() use ($class, $subject) {
                $class->update($subject);
            })
                ->isEqualTo('List of all books.')
            ->integer(http_response_code())
                ->isEqualTo(200);
    }
}