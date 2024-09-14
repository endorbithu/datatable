# Datatable

Datatable.js integráció Laravel 8 -hoz.

## Telepítés git submodulként
Ha submodulként telepítettük, akkor a composer.json-ben fel kell tüntetni path-ként a mappát ahova clone-oztuk a repot.
``` 
 "require": {
        (...)
        "endorbithu/datatable": "*"
    },
"repositories": [
        {
            "type":"path",
            "url": "./app_endorbithu/datatable"
        }
    ]
```
aztán `composer update`

## Telepítés composerrel

https://app.repman.io/ (belépés szükséges)

- endorbithu organization-nél kell másolni a composer config parancsot
- és a project laravel root mappájában futtatni:

```
composer config --global --auth http-basic.endorbithu.repo.repman.io token 2640e..........
```

Laravel root `composer.json`-nál ha még nincs, akkor be kell állítani a `endorbithu` repot

```
"repositories": [
  {
    "type": "composer",
    "url": "https://endorbithu.repo.repman.io"
  }
]
```

Aztán a laravel rootban:

```
composer require endorbithu/datatable
```

Mivel a package composer.json -jában szerepel a

```
"extra": {
    "laravel": {
      "providers": [
        "Endorbit\\Datatable\\Providers\\DatatableServiceProvider"
      ]
    }
  }
```

rész, így a laravel meg fogja találni megadott provider-t, tehát regisztrálódnak az ott megadott dolgok.

## Inizializálás

Publikáljuk a packaget = alkalmazza a providert és tartalmát:
 - a saját public mappáját másolja a `{laravelroot}/public/vendor/datatable` mappába
 - db migációs fájlokat regisztrálja
 - config fájlt másolja a `{laravelroot}/config` -ba
 - seeder fájlt másolja a `{laravelroot}/db/seeder` mappába

```
php artisan vendor:publish
 Which provider or tag's files would you like to publish?:
  [0 ] Publish files from all providers and tags listed below
  [1 ] Provider: Endorbit\Datatable\Providers\DatatableServiceProvider
```

Végül
```
php artisan migrate
php artisan db:seed --class="DatatableSeeder"
```

## Használat

Eloquent modellből vagy sima (asszociatív) tömbből tudunk aktív adattáblát készíteni.  
**Feltételek:**
- Header tömb definiálása: 
    - asszociatív tömb: a kulcsok az eloquent mező nevek (illetve az adat tömb kulcsai)
    - Eloquent esetében is lehet tetszőleges oszlopot definiálni, csak azt a mezőt adatgenerálásnál értékkel kell ellátni
- Adatot szolgáltató végpont létrehozása
    - mivel ajax os hívásokkal tölti fel a táblázatot
    - FONTOS: Route::any -nek kell lennie, tehát a post és get is ide fusson be



- Élő példa lsd `IndexController.php`

### CSV export a háttérben
Ha az exportálandó mezők (soror * oszlopok száma) nagyobb, mint a config-ban megadott (`config('datatable.csv_in_background_from_fields')`)
akkor ha a XHR action-ben le van kezelve, háttérben fog a csv generálódni (`\Endorbit\Datatable\Jobs\ProcessBigCsvExportToFile`)     
```
if(Datatable::hasTriggeredBackgroundCsvGenerating(self::class, __FUNCTION__, $modelForQueryBuilder, $request))
   return back()->with('success', 'A csv fájl generálása folyamatban');

```
Fontos! 
- Az érintett controller konstruktorának nem szabad lennie paraméternek
- az XHR actionnek csak `Request $request` paramétere lehet: `public function dataXhr(Request $request)`
- CSV háttérben történő gereálása során az XHR actionben lévő adat módosítások nem fognak alkalmazódni (mivel nem bírná a memória, ami miatt futtatjuk a háttérben)

A generált fájlokat a  `$datatable->getCsvFiles()` -vel lehet elérni. (`app/datatable_csvs/table_id/...`) 
A configban beállított `config('datatable.keep_csv_files_per_datatable')` db fájlt hagy meg datatable-enként, 
a régieket törli.


### Datatable definiálása

```
  public function index(Request $request)
    {

        //Először definiálnunk kell a mezőket, első mezőnek mindig az "id"-nek kell lennie
         //ahol nem mező van, hanem belongsto vagy hasmany-t megvalósító függvény, ott "." -tal jelöljük, hogy a
        //kapcsolódó táblának melyik mezőjét írjuk ki, jellemzően a "name" lesz az
        //lehet hozzáírni pluszban tetszőleges számú mezőt, viszont azt a adatot generáló végoldalon le kell kezelni, tehát értéket kell adni ott neki
        $headerInfo = [
            'id' => 'id',
            'name' => 'Név',
            'phone' => 'Telefon',
            'net_wage_demand' => 'Bérigény',
            'datatableAttrSchoolDegree.name' => 'Legmagasabb iskola',
            'datatableAttrJobCategories.name' => 'Kategóriák', //ha hasMany tehát több a többhöz kapcsolat, akkor azt ezt lekezelő fg neve legyen a kulcs
            'is_newsletter' => 'Hírlevél',
            'birthday' => 'Születésnap',
            'updated_at' => 'Utoljára módosítva',
            'tetszoleges_oszlop' => 'Tetszőleges oszlop'

        ];

        //ezzekkel a paraméterekkel kell inicializálni egy datatable-t
        $datatable = Datatable::create(
            //azért kell, mert ha több datatable van egy html oldalon a javascript meg tudja őket különböztetni
            'users',
            
            //milyen típusú (select, checkbox, naked)
            'checkbox',
            
            //a tartalmat nyújtó végoldal, de lehet sima tömb is és akkor nincs ajax, sőt eloquent model/class + query builder példány is
            '/datatableusers',

            $headerInfo,
            
            //OPCIONÁLIS ha eloquent mpdelből készül a datatable itt megadhatjukj melyik class az , akkor a keresés mezőket össze tudja rakni
            DatatableUser::class
        );

        //rendezés a 0. oszlop az ID, utána a többi a header tömbben megadott sorrend szerint alapértelmezetten ID asc
        $datatable->setOrder(['updated_at' => 'desc']);

        //melyik oszlopoknál legyen operating rész a headerben ( 0. oszlop az ID, utána a többi a header tömbben megadott szerint)
        $datatable->setOperations(['net_wage_demand']);

        //beállíthatjuk, hogy melyik sor(oka)t (ID)-ket tegye előre checked-re (csak select nézetnél működik)
        $datatable->setSelectedIds([1, 101]);

        //Itt lehet megadni művelet gombokat a feldolgozandó végpontokkal ikonnal stb
        //ezekre a végpontokra minden <input> elem el lesz küldve ami látható, (operation a headerben, amit az adat sorokban mi csináltunk stb.)
        //csak a végponttól függ, hogy melyik input mező lesz feldolgozva az összesből
        $datatable->setAction([
            [
                'name' => 'Törlés',
                'action' => '/users-delete',
                'warningText' => 'Biztos, hogy törlöd',
                'icon' => 'remove'
            ],
            [
                'name' => 'Bérigény módosítás',
                'action' => '/users-operation',
                'warningText' => '',
                'icon' => 'usd'
            ],
            [
                'name' => 'Mentés',
                'action' => '/users-save',
                'warningText' => '',
                'icon' => 'usd'
            ],

        ]);


        //Alapértlemezett értékkel rendelkező mezők módosítása:


        //$datatable
        //legyen önálló <form a datatablehez (ezt a formot küldi el az művelet gombok végpontjaira),
        // Ha false, akkor az adott html oldalon heéyeztünk már el a datatable köré <form>-ot
        //ha nem állítjuk be, akkor alapértelmezetten true
        $datatable->setForm(true);
        //legyen lapozás vagy mutassa az egészet, ha nem állítjuk be, akkor alapértelmezetten true

        //le lehet tenni a toolbart az aljára, alapértelmezetten false
        $datatable->setToolbarAtBottom(false);

        //checkbox / select nézet váltó gomb,
        // FONTOS! ha a tartalmi sorokba is tettünk input elemet, akkor tegyük false-ra
        //alapértelmezetten true
        $datatable->setChooseType(true);

        //melyik oszlopoknál legyen keresés ( 0. oszlop az ID, utána a többi a header tömbben megadott szerint)
        //ha az oszlopszám után egy "s" van és kulcsként szerpel, akkor select keresés lesz, és meg kell adnunk a selecthez szükséges tömböt
        //alapértelmezetten kitölti az összes oszloponál, és a relat columnokat is megoldja select fielddel HA:
        //a config('datatable.max_element_in_select_in_search_column') nál kevesebb sor van benne
        // így kell egyébként kézzel:
        $datatable->setSearch([
            1,
            '4s' => ([0 => 'Összes'] + DatatableAttrSchoolDegree::all()->pluck('name', 'id')->toArray()),
            '5s' => ([0 => 'Összes'] + DatatableAttrJobCategory::all()->pluck('name', 'id')->toArray()),
            '6s' => ([0 => 'Összes', 1 => 'NEM', 2 => 'IGEN'])
        ]);
        $datatable->setSearch(null); //alapértelmezettre rakjuk
        
        
        $generatedDatatable = $datatable->render();
        
         return view('datatable::index')->with([
                    'datatable' => $generatedDatatable,
                    'csv_files' =>  $datatable->getCsvFiles()
                ]);
        
```

### Végpont az adatok betöltéséhez (XHR action)
```
    //végpont az adatok begyüjtéséhez, 
    //FONTOS: Route::any -nek kell lennie, tehát a post és get is ide fusson be
    
    
    public function datatableusers(Request $request, $params = null)
    {
        $data = Datatable::getFilteredRows(DatatableUser::class, $request->all());
        
        //ha ez ki van hagyva, nem fogja megpróbálni a háttérben generálni a csv-t
        if(Datatable::hasTriggeredBackgroundCsvGenerating(self::class, __FUNCTION__, $modelForQueryBuilder, $request))
            return back()->with('success', 'A csv fájl generálása folyamatban');

        //Opcionális:
        // a $headerInfo ban megadott mezőnév / függvény név kulcsokkal hivatkozva lehet müdosítani a tartalmon,
        // itt lehet formázni a számokat, inputot csinálni a mezőből stb. ha a háttérben van a csv lementve, 
        // Datatable::hasTriggeredBackgroundCsvGenerating() === true, akkor az alábbi módosítások nem fognak a csv-ben 
        //látszani
        foreach ($data['data'] as &$row) {
            $row['phone'] = '<input type="text" name="phone[' . $row['id'] . ']" value="' . $row['phone'] . '">';
            $row['net_wage_demand'] = number_format($row['net_wage_demand'], 0, ',', '.');
            $row['tetszoleges_oszlop'] = 'blabla';
            //$row['datatableAttrSchoolDegree'] = ...
        }

        return (Datatable::getResponseForDatatable($data));
    }
```

