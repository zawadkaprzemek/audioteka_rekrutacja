# audioteka_rekrutacja

Instalacja
1. ściągnij repozytorium
2. composer install
3. w pliku .env wprowadź swoje dane aby połączyć się z bazą danych
4. symfony server:start
5. bin/console doctrine:database:create
6. bin/console doctrine:migrations:migrate
7. bin/console doctrine:fixtures:load aby dodać do bazy produkty
8. bin/console app:create-user aby stworzyć konto użytkownika i uzyskać token do łączenia po API

Używanie:
Adres http://localhost:8000
Autentykacja Bearer Token
W nagłówkach dodać nagłówek
Authorization : Bearer "token wygenerowany przy tworzeniu konta użytkownika"

Końcówki API:
1. /api/products/add - Metoda POST - dodawanie produktów - w body wysłać jsona z wartościami name i price
2. /api/products/{id_produktu}/delete - Metoda DELETE - usuwanie produktów - puste body
3. /api/products/{id_produktu}/edit/name - METODA PATCH - edycja nazwy produktu - w body wysłać jsona z wartością name
4. /api/products/{id_produktu}/edit/price - METODA PATCH - edycja ceny produktu - w body wysłać jsona z wartością price
5. /api/products/{page} - METODA GET - wyświetlanie listy produktów - page przyjmuje wartości int od 1 wzwyż, przy wartości 1 może zostać pominięta
6. /api/cart/add - METODA POST - tworzenie koszyka - puste body
7. /api/cart/add_to_cart - METODA POST - dodawanie do koszyka - w body wysłać jsona z wartością produkt zawierającą ID dodawanego produktu
8. /api/cart/delete_product - METODA DELETE - usuwanie z koszyka - w body wysłać jsona z wartością produkt zawierającą ID usuwanego produktu
9. /api/cart/show - METODA GET - wyświetlanie koszyka - puste body

Testy:
Napisałem jeden test funkcjonalny do sprawdzania ilości produktów na stronie
Odpalanie z terminala php bin/phpunit
