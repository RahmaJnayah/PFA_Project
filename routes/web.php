<?php
$router->get('/auth/login', 'AuthController@login');
$router->post('/auth/login', 'AuthController@login');
$router->get('/auth/register', 'AuthController@register');
$router->post('/auth/register', 'AuthController@register');
$router->get('/auth/forgot', 'AuthController@forgotPassword');
$router->post('/auth/forgot', 'AuthController@forgotPassword');
$router->get('/auth/reset', 'AuthController@resetPassword');
$router->post('/auth/reset', 'AuthController@resetPassword');
$router->get('/auth/logout', 'AuthController@logout');