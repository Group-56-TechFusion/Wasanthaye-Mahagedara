<?php
$routes = [
    ['GET',  '/login',     'AuthController@showLogin',      null],
    ['POST', '/login',     'AuthController@login',          null],
    ['POST', '/logout',    'AuthController@logout',         '*'],

    ['GET',  '/',          'DashboardController@index',     'dashboard'],
    ['GET',  '/dashboard', 'DashboardController@index',     'dashboard'],

    ['GET',  '/users',     'ModuleController@users',        'users'],
    ['GET',  '/branches',  'ModuleController@branches',     'branches'],
    ['GET',  '/customers', 'ModuleController@customers',    'customers'],
    ['GET',  '/calendar',  'ModuleController@calendar',     'calendar'],
    ['GET',  '/billing',   'ModuleController@billing',      'billing'],
    ['GET',  '/reports',   'ModuleController@reports',      'reports'],

    ['GET',  '/inventory',                 'JacketController@index',     'inventory'],
    ['GET',  '/inventory/jackets/create',  'JacketController@create',    'inventory'],
    ['POST', '/inventory/jackets/store',   'JacketController@store',     'inventory'],
    ['GET',  '/inventory/jackets/view',    'JacketController@show',      'inventory'],
    ['GET',  '/inventory/jackets/edit',    'JacketController@edit',      'inventory'],
    ['POST', '/inventory/jackets/update',  'JacketController@update',    'inventory'],
    ['POST', '/inventory/jackets/delete',  'JacketController@destroy',   'inventory'],
];