<?php
$routes = [
    ['GET',  '/login',     'AuthController@showLogin',      null],
    ['POST', '/login',     'AuthController@login',          null],
    ['POST', '/logout',    'AuthController@logout',         '*'],

    ['GET',  '/',          'DashboardController@index',     'dashboard'],
    ['GET',  '/dashboard', 'DashboardController@index',     'dashboard'],

    ['GET',  '/users',     'ModuleController@users',        'users'],
    ['POST', '/users',     'ModuleController@createUser',   'users'],
    ['GET',  '/branches',  'ModuleController@branches',     'branches'],
    ['GET',  '/customers', 'ModuleController@customers',    'customers'],
    ['GET',  '/calendar',  'ModuleController@calendar',     'calendar'],
    ['POST', '/calendar/weddings', 'ModuleController@createWedding', 'calendar'],
    ['GET',  '/billing',   'ModuleController@billing',      'billing'],
    ['GET',  '/inventory', 'ModuleController@inventory',    'inventory'],
    ['GET',  '/reports',   'ModuleController@reports',      'reports'],
];