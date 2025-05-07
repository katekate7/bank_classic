<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/api/expense' => [[['_route' => 'api_expense_create', '_controller' => 'App\\Controller\\ApiExpenseController::create'], null, ['POST' => 0], null, false, false, null]],
        '/api/expenses' => [[['_route' => 'api_expense_list', '_controller' => 'App\\Controller\\ApiExpenseController::list'], null, ['GET' => 0], null, false, false, null]],
        '/register' => [
            [['_route' => 'api_register', '_controller' => 'App\\Controller\\ApiRegistrationController::register'], null, ['POST' => 0], null, false, false, null],
            [['_route' => 'app_register', '_controller' => 'App\\Controller\\RegistrationController::register'], null, null, null, false, false, null],
        ],
        '/api/categories' => [[['_route' => 'categories_list', '_controller' => 'App\\Controller\\CategoryController::list'], null, ['GET' => 0], null, false, false, null]],
        '/home' => [[['_route' => 'home', '_controller' => 'App\\Controller\\HomeController::index'], null, null, null, false, false, null]],
        '/profile' => [[['_route' => 'profile_show', '_controller' => 'App\\Controller\\ProfileController::show'], null, ['GET' => 0], null, true, false, null]],
        '/profile/edit' => [[['_route' => 'app_profile_edit', '_controller' => 'App\\Controller\\ProfileController::edit'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/login' => [[['_route' => 'app_login', '_controller' => 'App\\Controller\\SecurityController::login'], null, null, null, false, false, null]],
        '/logout' => [[['_route' => 'app_logout', '_controller' => 'App\\Controller\\SecurityController::logout'], null, null, null, false, false, null]],
        '/user/expense' => [[['_route' => 'app_user_expense_index', '_controller' => 'App\\Controller\\UserExpenseController::index'], null, ['GET' => 0], null, true, false, null]],
        '/user/expense/new' => [[['_route' => 'app_user_expense_new', '_controller' => 'App\\Controller\\UserExpenseController::new'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/_profiler' => [[['_route' => '_profiler_home', '_controller' => 'web_profiler.controller.profiler::homeAction'], null, null, null, true, false, null]],
        '/_profiler/search' => [[['_route' => '_profiler_search', '_controller' => 'web_profiler.controller.profiler::searchAction'], null, null, null, false, false, null]],
        '/_profiler/search_bar' => [[['_route' => '_profiler_search_bar', '_controller' => 'web_profiler.controller.profiler::searchBarAction'], null, null, null, false, false, null]],
        '/_profiler/phpinfo' => [[['_route' => '_profiler_phpinfo', '_controller' => 'web_profiler.controller.profiler::phpinfoAction'], null, null, null, false, false, null]],
        '/_profiler/open' => [[['_route' => '_profiler_open_file', '_controller' => 'web_profiler.controller.profiler::openAction'], null, null, null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/api/expense/([^/]++)(?'
                    .'|(*:31)'
                .')'
                .'|/user/expense/([^/]++)(?'
                    .'|(*:64)'
                    .'|/edit(*:76)'
                    .'|(*:83)'
                .')'
                .'|/_(?'
                    .'|error/(\\d+)(?:\\.([^/]++))?(*:122)'
                    .'|wdt/([^/]++)(*:142)'
                    .'|profiler/([^/]++)(?'
                        .'|/(?'
                            .'|search/results(*:188)'
                            .'|router(*:202)'
                            .'|exception(?'
                                .'|(*:222)'
                                .'|\\.css(*:235)'
                            .')'
                        .')'
                        .'|(*:245)'
                    .')'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        31 => [
            [['_route' => 'api_expense_delete', '_controller' => 'App\\Controller\\ApiExpenseController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
            [['_route' => 'api_expense_get', '_controller' => 'App\\Controller\\ApiExpenseController::getExpense'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_expense_update', '_controller' => 'App\\Controller\\ApiExpenseController::update'], ['id'], ['PUT' => 0], null, false, true, null],
        ],
        64 => [[['_route' => 'app_user_expense_show', '_controller' => 'App\\Controller\\UserExpenseController::show'], ['id'], ['GET' => 0], null, false, true, null]],
        76 => [[['_route' => 'app_user_expense_edit', '_controller' => 'App\\Controller\\UserExpenseController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        83 => [[['_route' => 'app_user_expense_delete', '_controller' => 'App\\Controller\\UserExpenseController::delete'], ['id'], ['POST' => 0], null, false, true, null]],
        122 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        142 => [[['_route' => '_wdt', '_controller' => 'web_profiler.controller.profiler::toolbarAction'], ['token'], null, null, false, true, null]],
        188 => [[['_route' => '_profiler_search_results', '_controller' => 'web_profiler.controller.profiler::searchResultsAction'], ['token'], null, null, false, false, null]],
        202 => [[['_route' => '_profiler_router', '_controller' => 'web_profiler.controller.router::panelAction'], ['token'], null, null, false, false, null]],
        222 => [[['_route' => '_profiler_exception', '_controller' => 'web_profiler.controller.exception_panel::body'], ['token'], null, null, false, false, null]],
        235 => [[['_route' => '_profiler_exception_css', '_controller' => 'web_profiler.controller.exception_panel::stylesheet'], ['token'], null, null, false, false, null]],
        245 => [
            [['_route' => '_profiler', '_controller' => 'web_profiler.controller.profiler::panelAction'], ['token'], null, null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
