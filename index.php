<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

DEFINE('HOST', '127.0.0.1');
DEFINE('USER', 'root');
DEFINE('PASSWORD', 'a');
DEFINE('DB_NAME', 'dling');
DEFINE('APP_NAME', 'آذین ویستا');
DEFINE('APP_URL', 'آذین_ویستا');
DEFINE('APP_SITE', 'azinvista.com');


$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader);
$config = [
    'settings' => [
        'displayErrorDetails' => true,
        'logger' => [
            'name' => 'slim-app',
            'level' => Monolog\Logger::DEBUG,
            'path' => __DIR__ . '/../logs/app.log',
        ],
    ],
];
$app = new \Slim\App($config);



$app->get('/', function (Request $request, Response $response, $args) use ($twig, $app) {
    $response->getBody()->write($twig->render('home.twig', [ "app_name" => APP_NAME,"app_url" => APP_URL, "app_site" => APP_SITE]));
})->setName('home');

$app->get('/{name}', function(Request $request, Response $response, $args) use ($twig, $app) {
    $path = trim(urldecode($args["name"]));
    if ($path == "در_و_پنجره_UPVC_".APP_NAME) {
        $response->getBody()->write($twig->render('home.twig', ["app_name" => APP_NAME,"app_url" => APP_URL, "app_site" => APP_SITE]));
        return;
    }
    if ($path == "انواع_پروفیل_UPVC_در_و_پنجره") {
        $response->getBody()->write($twig->render('products.twig', ["app_name" => APP_NAME,"app_url" => APP_URL, "app_site" => APP_SITE]));
        return;
    }
    if ($path == "پروژه_های_در_و_پنجره_UPVC") {
        $response->getBody()->write($twig->render('projects.twig', ["app_name" => APP_NAME,"app_url" => APP_URL, "app_site" => APP_SITE]));
        return;
    }
    if ($path == "تماس_با_".APP_URL) {
        $response->getBody()->write($twig->render('contact.twig', ["app_name" => APP_NAME,"app_url" => APP_URL, "app_site" => APP_SITE]));
        return;
    }
    if ($path == "درباره_".APP_URL) {
        $response->getBody()->write($twig->render('about.twig', ["app_name" => APP_NAME,"app_url" => APP_URL, "app_site" => APP_SITE]));
        return;
    }
    $response->getBody()->write($twig->render('notfound.twig', ["app_name" => APP_NAME,"app_url" => APP_URL, "app_site" => APP_SITE]));
    return;
});

$app->run();
