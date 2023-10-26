# Implementační dokumentace k 1. úloze do IPP 2021/2022
Jméno a příjmení: Josef Kuba

Login: xkubaj03

## Popis
Skript parse.php má za úkol provést syntaktickou a lexikální analýzu jazyka IPPcode22. Moje implementace obsahuje pouze základní zadání bez rozšíření. Pokoušel jsem se zpřehlednit kód pomocí vytvoření a používání pomocných funkcí. Pro kontrolu argumentů: IsVar, IsSymb, IsLabel a IsType. Ostatní funkce: SpecChars a PrintSymb.

Z počátku skript kontroluje svoje parametry. Jediným povoleným parametrem je „--help“, který vypíše nápovědu. Jiný počet argumentů nebo jiný argument vede k chybě 10. Pokud není zadán argument, potom je očekáván vstup ze STDIN. Vstup je zpracováván po řádcích. Nejprve dojde k odstranění komentářů (pokud je řádek obsahuje), odstranění vícenásobných mezer, odstranění bílých znaků na začátku a na konci řádku, potom se řádek rozdělí podle mezer. Nejprve je očekávána hlavička „.IPPcode22“ pokud není nalezena následuje chyba 21. V dalších řádcích pokračuje kontrolou jednotlivých příkazů. První slovo porovnávám se známými příkazy. Pokud není příkaz nalezen následuje chyba 22. Následuje kontrola správného počtu argumentů (pro konkrétní příkaz) jinak následuje chyba 23. Další na řadě je kontrola správnosti argumentů pomocí regulárních výrazů (použití funkcí IsVar, IsSymb, IsLabel a IsType) jinak následuje chyba 23. Nakonec dojde k přepsání speciálních znaků (funkce SpecChars) a k vypsání XML na STDOUT.
