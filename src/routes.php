<?php
use core\Router;

$router = new Router();

$router->get('/', 'HomeController@index');
$router->get('/login', 'LoginController@signin'); // Logar
$router->post('/login', 'LoginController@signinAction'); // Ação para receber o login

$router->get('/cadastro', 'LoginController@signup'); //Cadastrar
$router->post('/cadastro', 'LoginController@signupAction'); // Ação para receber dados cadastrais

$router->post('/post/new', 'PostController@new');

$router->get('/perfil/{id}/fotos', 'ProfileController@photos');
$router->get('/perfil/{id}/amigos', 'ProfileController@friends');
$router->get('/perfil/{id}/follow', 'ProfileController@follow');
$router->get('/perfil/{id}', 'ProfileController@index');
$router->get('/perfil', 'ProfileController@index');

$router->get('/amigos', 'ProfileController@friends');
$router->get('/fotos', 'ProfileController@photos');

$router->get('/pesquisa', 'SearchController@index');

$router->get('/config', 'ConfigController@index'); // Criar rotas dos forms, continuar o CSS da senha e criar as funções

$router->get('/sair', 'LoginController@logout');

//$router->get('/pesquisa');
//$router->get('/perfil');
//$router->get('/sair');
//$router->get('/amigos');
//$router->get('/fotos');
//$router->get('/config');