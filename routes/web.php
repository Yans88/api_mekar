<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/admin', 'AdminController@index');
$router->post('/admin_detail', 'AdminController@detail');
$router->post('/simpan_admin', 'AdminController@store');
$router->post('/edit_admin', 'AdminController@edit');
$router->post('/del_admin', 'AdminController@del');
$router->post('/login_admin', 'AdminController@login_cms');

$router->post('/banner', 'BannerController@index');
$router->post('/simpan_banner', 'BannerController@store');
$router->post('/del_banner', 'BannerController@proses_delete');

$router->post('/get_news', 'NewsController@index');
$router->post('/add_news', 'NewsController@store');
$router->post('/del_news', 'NewsController@proses_delete');
$router->post('/detail_news', 'NewsController@detail');

$router->post('/master_data', 'MasterController@index');
$router->post('/upd_setting', 'MasterController@upd_setting');
$router->post('/get_kasus', 'MasterController@get_kasus');
$router->post('/add_kasus', 'MasterController@add_kasus');
$router->post('/del_kasus', 'MasterController@del_kasus');

$router->post('/members', 'MemberController@index');
$router->post('/profile_member', 'MemberController@detail');
$router->post('/register_member', 'MemberController@reg');
$router->post('/login', 'MemberController@login_member');
$router->post('/edit', 'MemberController@edit');
$router->post('/chg_pass', 'MemberController@change_pass');
$router->post('/forgot_pass', 'MemberController@forgot_pass');
$router->post('/upload_photo', 'MemberController@upl_photo');
$router->get('/verify_email/{id}', 'MemberController@verify_email');
$router->post('/test_email', 'MemberController@test_mail');