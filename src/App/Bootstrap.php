<?php
namespace App;

use Monolog;
use Slim\App;
use Slim\Views;

class Bootstrap
{

    protected $app;

    protected $container;

    public function __construct()
    {
        // Start Session
        $this->session();

        // Instantiate the app
        $this->app = new App($this->settings());

        // Set up dependencies
        $this->dependencies();

        // Register middleware
        $this->middleware();

        // Register routes
        $this->routes();
    }

    public function cli()
    {
        die('Slim Skeleton');
    }

    public function process($request, $response)
    {
        return $this->app->process($request, $response);
    }

    public function run()
    {
        $this->app->run();
    }

    private function dependencies()
    {
        // DIC configuration
        $this->container = $this->app->getContainer();

        // view renderer
        $this->container['renderer'] = function ($c) {
            $settings = $c->get('settings')['renderer'];
            return new Views\PhpRenderer($settings['template_path']);
        };

        // monolog
        $this->container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
            return $logger;
        };

        // twig renderer
        $this->container['view'] = function ($c) {
            $settings = $c->get('settings')['twig'];
            $twig = new Views\Twig($settings['template_path'], [
                'cache' => $settings['cache'],
                'charset' => $settings['charset']
            ]);
            $twig->addExtension(new Views\TwigExtension($c->router, $c->request->getUri()));
            return $twig;
        };

        // home controller
        $this->container['homeController'] = function ($c) {
            return new Controllers\HomeController($c);
        };

        // not found handler
        $this->container['notFoundHandler'] = function ($c) {
            return function ($request, $response) use ($c) {
                return $c->view->render($response, 'error.twig', [
                    'code' => '404',
                    'error' => 'Not Found'
                ])->withStatus(404);
            };
        };

        // not allowed handler
        $this->container['notAllowedHandler'] = function ($c) {
            return function ($request, $response, $methods) use ($c) {
                return $c->view->render($response, 'error.twig', [
                    'code' => '405',
                    'error' => 'Method Not Allowed'
                ])
                    ->withHeader('Allow', implode(', ', $methods))
                    ->withStatus(405);
            };
        };

        // php error handler
        $this->container['phpErrorHandler'] = function ($c) {
            return function ($request, $response, $error) use ($c) {
                return $c->view->render($response, 'error.twig', [
                    'code' => '500',
                    'error' => 'Internal Server Error'
                ])->withStatus(500);
            };
        };
    }

    private function middleware()
    {
        // Application middleware

        // e.g: $this->app->add(new \Slim\Csrf\Guard);
    }

    private function routes()
    {
        // Routes
        $this->app->get('/[{name}]', 'homeController:index')->setName('home');
    }

    private function session()
    {
        if (PHP_SAPI != 'cli') {
            $settings = $this->settings()['settings']['session'];

            if (is_dir($settings['save_path']) && is_writeable($settings['save_path'])) {
                session_save_path($settings['save_path']);
            }

            if (isset($settings['name']) && ! empty($settings['name'])) {
                session_name($settings['name']);
            }

            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        }
    }

    private function settings()
    {
        return [
            'settings' => [
                'displayErrorDetails' => true, // set to false in production
                'addContentLengthHeader' => false, // Allow the web server to send the content-length header

                // Renderer settings
                'renderer' => [
                    'template_path' => __DIR__ . '/../../templates/'
                ],

                // Session settings
                'session' => [
                    'name' => 'session',
                    'save_path' => __DIR__ . '/../../var/sessions/'
                ],

                // Monolog settings
                'logger' => [
                    'level' => Monolog\Logger::DEBUG,
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../../logs/app.log'
                ],

                // Twig settings
                'twig' => [
                    'cache' => false,
                    'charset' => 'utf8',
                    'template_path' => __DIR__ . '/../../templates/'
                ]
            ]
        ];
    }
}
