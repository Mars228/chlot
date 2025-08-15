<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
//$routes->get('/', 'Home::index');


$routes->get('/', 'HomeController::index');
$routes->get('home', 'HomeController::index');

// Sekcja GRY (CRUD) + pod-zasoby: grupy zakresów, warianty, progi wypłat
$routes->group('gry', static function ($routes) {
    $routes->get('/', 'GamesController::index');
    $routes->get('nowa', 'GamesController::create');
    $routes->post('zapisz', 'GamesController::store');
    $routes->get('(:num)', 'GamesController::show/$1');
    $routes->get('(:num)/edytuj', 'GamesController::edit/$1');
    $routes->post('(:num)/aktualizuj', 'GamesController::update/$1');
    $routes->post('(:num)/usun', 'GamesController::delete/$1');

    // Grupy zakresów liczb (A/B)
    $routes->post('(:num)/grupy/dodaj', 'GamePickGroupsController::store/$1');
    $routes->post('(:num)/grupy/(:num2)/usun', 'GamePickGroupsController::delete/$1/$2');

    // Warianty (np. „10 z 80”, „6 z 49”, „5 z 50 + 2 z 12”)
    $routes->post('(:num)/warianty/dodaj', 'GameVariantsController::store/$1');
    $routes->post('(:num)/warianty/(:num2)/usun', 'GameVariantsController::delete/$1/$2');

    // Progi wypłat (per wariant)
    $routes->post('warianty/(:num)/progi/dodaj', 'PrizeTiersController::store/$1');
    $routes->post('warianty/(:num)/progi/(:num2)/usun', 'PrizeTiersController::delete/$1/$2');
});

$routes->group('losowania', static function ($routes) {
    $routes->get('/', 'DrawsController::index'); // domyślnie Multi Multi, bieżący miesiąc

    // Import z CSV
    $routes->get('import', 'DrawsController::importForm');
    $routes->post('import', 'DrawsController::import');

    // Pobranie z Lotto OpenAPI pojedynczego losowania wg daty + gry
    $routes->get('pobierz', 'DrawsController::fetchForm');
    $routes->post('pobierz', 'DrawsController::fetchOne');
    $routes->get('test-latest', 'DrawsController::testLatest'); // AJAX tester
    $routes->post('sync-mm-ids', 'DrawsController::syncMmIds');
});



// ...
$routes->group('statystyki', static function ($routes) {
    // Schematy
    $routes->get('/',                      'StatsController::schemasIndex');
    $routes->get('nowy',                   'StatsController::schemaCreateForm');
    $routes->post('nowy',                  'StatsController::schemaCreate');
    $routes->get('schemat/(:num)/edytuj',  'StatsController::schemaEdit/$1');
    //$routes->post('schemat/(:num)/zapisz', 'StatsController::schemaUpdate/$1');
    $routes->post('schemat/(:num)/start',  'StatsController::schemaStart/$1');
    $routes->post('schemat/(:num)/pauza',  'StatsController::schemaPause/$1');
    $routes->post('schemat/(:num)/usun',   'StatsController::schemaDelete/$1');

    // Krok obliczeń (AJAX, batch)
    $routes->get('schemat/(:num)/step',    'StatsController::schemaStep/$1'); // ?batch=50


// Schematy
    $routes->get('schematy',              'StatsController::schemasIndex');
    //$routes->get('schematy/nowy',         'StatsController::schemasCreate');
    $routes->post('schemat/nowy', 'StatsController::schemaStore');         // CREATE (POST)
    $routes->post('schemat/(:num)/zapisz', 'StatsController::schemaUpdate/$1'); // UPDATE (POST)

    $routes->post('schematy',             'StatsController::schemasStore');
    //$routes->get('schematy/(:num)/edytuj','StatsController::schemasEdit/$1');
    $routes->post('schematy/(:num)',      'StatsController::schemasUpdate/$1');
    
});

$routes->group('strategie', static function($routes){
    $routes->get('/', 'StrategiesController::index');
    $routes->get('schematy-wg-gry', 'StrategiesController::ajaxSchemasByGame');


    $routes->get('backfill', 'StatsController::backfillStrategies');
});


// ZAKŁADY
$routes->group('zaklady', static function ($routes) {
    // /zaklady  → lista serii
    $routes->get('/', 'BetsController::index');
    // /zaklady/nowa  → formularz nowej serii
    $routes->get('nowa', 'BetsController::create');
  // API do progresu
    $routes->post('start', 'BetsController::start');       // start serii (JSON)
    $routes->get('step/(:num)', 'BetsController::step/$1'); // przetwórz kawałek (JSON)
    
    // POST /zaklady  → zapis/uruchomienie generowania
    $routes->post('/', 'BetsController::store');
    // /zaklady/seria/123  → podgląd serii
    $routes->get('seria/(:num)', 'BetsController::show/$1');
});

$routes->group('wyniki', static function($routes){
    $routes->get('/',        'ResultsController::index');
    $routes->post('przelicz','ResultsController::recalc');     // przelicz wyniki (dla filtrów)
});



$routes->group('ustawienia', static function ($routes) {
    $routes->get('/', 'SettingsController::index');
    $routes->post('zapisz', 'SettingsController::save');
    $routes->post('test-api', 'SettingsController::testApi');
});

