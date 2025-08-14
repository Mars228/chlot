GET  /



// Sekcja GRY (CRUD) + pod-zasoby: grupy zakresów, warianty, progi wypłat
GET  /gry
GET  /gry/nowa
POST /gry/zapisz
GET  /gry/{:num}
GET  /gry/{:num}/edytuj
POST /gry/{:num}/aktualizuj
POST /gry/{:num}/usun

// Grupy zakresów liczb (A/B)
POST /gry/:num/grupy/dodaj
POST /gry/:num/grupy/{:num2}/usun

// Warianty (np. „10 z 80”, „6 z 49”, „5 z 50 + 2 z 12”)
POST /gry/:num/warianty/dodaj
POST /gry/:num/warianty/{:num2}/usun

// Progi wypłat (per wariant)
POST /gry/warianty/{:num}/progi/dodaj
POST /gry/warianty/{:num}/progi/{:num2}/usun



// domyślnie Multi Multi, bieżący miesiąc
GET  /losowania

// Import z CSV
GET  /losowania/import
POST /losowania/import

// Pobranie z Lotto OpenAPI pojedynczego losowania wg daty + gry
GET  /losowania/pobierz
POST /losowania/pobierz
GET  /losowania/test-latest
POST /losowania/sync-mm-ids



// ...
GET  /statystyki/schematy
GET  /statystyki/schematy/nowy
POST /statystyki/schematy
GET  /statystyki/schematy/{:num}/edytuj
POST /statystyki/schematy/{:num}



GET  /strategie
GET  /strategie/schemas-by-game?game_id={:num}



// ZAKŁADY
// /zaklady  → lista serii
GET  /zaklady
// /zaklady/nowa  → formularz nowej serii
GET  /zaklady/nowa
// start serii (JSON)
POST /zaklady/start
// przetwórz kawałek (JSON)
GET  /zaklady/step/{:num}
// POST /zaklady  → zapis/uruchomienie generowania
POST /zaklady
// /zaklady/seria/123  → podgląd serii
GET  /zaklady/seria/{:num}




GET  /wyniki
// przelicz wyniki (dla filtrów)
POST /wyniki/przelicz



GET /ustawienia
POST /ustawienia/zapisz
POST /ustawienia/test-api