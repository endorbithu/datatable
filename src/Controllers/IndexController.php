<?php

namespace DelocalZrt\Datatable\Controllers;

use App\Models\User;
use DelocalZrt\Datatable\Models\DatatableUser;
use DelocalZrt\Datatable\Services\Datatable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class IndexController extends BaseController
{

    public function index(Request $request)
    {
        if (!config('app.debug')) die('ONLY IN DEBUG MODE!');

        //Először definiálnunk kell a mezőket, ahol nem mező vanb, hanem belongsto vagy hasmany-t megvalósító függvény, ott "." -tal jelöljük, hogy a
        //kapcsolódó táblának melyik mezőjét írjuk ki, jellemzően a "name" lesz az
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


        $datatable = Datatable::create('users', 'checkbox', '/datatableusers', $headerInfo, DatatableUser::class);
        $datatable->setOrder(['updated_at' => 'desc']);
        $datatable->setOperations(['net_wage_demand']);
        $datatable->setSelectedIds([1, 101]);
        //$datatable->setCsv(false);
        $datatable->setAction([
            [
                'name' => 'Törlés',
                'action' => '/users-delete',
                'warning' => 'Biztos, hogy törlöd',
                'icon' => 'remove'
            ],
            [
                'name' => 'Bérigény módosítás',
                'action' => '/users-operation',
                'warning' => '',
                'icon' => 'usd'
            ],
            [
                'name' => 'Mentés',
                'action' => '/users-save',
                'warning' => '',
                'icon' => 'usd'
            ],
            [
                'name' => 'Új',
                'href' => '/users-new',
                'icon' => 'usd'
            ],

        ]);


        $generatedDatatable = $datatable->render();

        return view('datatable::index')->with([
            'datatable' => $generatedDatatable,
            'csvfiles' =>  $datatable->getCsvFiles()
        ]);
    }


    //végpont az adatok begyüjtéséhez és csvexporthoz,
    public function datatableusers(Request $request)
    {
        $data = Datatable::getFilteredRows(DatatableUser::class, $request->all());

        //ha ez ki van hagyva, nem fogja megpróbálni a háttérben generálni a csv-t
        if(Datatable::hasTriggeredBackgroundCsvGenerating(self::class, __FUNCTION__, DatatableUser::class, $request))
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
            $row['is_newsletter'] = ($row['is_newsletter'] ? 'IGEN' : 'NEM');
            //$row['datatableAttrSchoolDegree'] = ...
        }

        return (Datatable::getResponseForDatatable($data, DatatableUser::class));
    }


//===================================================================================================================
//===================================================================================================================
//ACTION végpontok
//===================================================================================================================
//===================================================================================================================


    public function usersSave(Request $request)
    {
        //ha "Összes kijelölése" volt, akkor $request->check-all = "on",

        //Ha nem volt "Összes kijelölése" akkor jönnek egyenként az ID-k:
        // {tableIDname}[] = 1
        //  {tableIDname}[] = 2
        // ilyenkor egyértelmű melyik iD-ket kell feldolgozni

        //ha mindent kijelölés volt

        $ids = Datatable::getFilteredIds(DatatableUser::class, $request->all());


        Session::flash('message', count($request->users) . 'db User sikeresen törölve!');
        Session::flash('alert-class', 'alert-info');
        Session::flash('selected_rows', $request->users);

        return back();
    }

    public function usersDelete(Request $request)
    {
        if ((!$request->users || empty($request->users))) {
            return back();
        }

        User::whereIn('id', $request->users)->delete();
        Session::flash('message', count($request->users) . 'db User sikeresen törölve!');
        Session::flash('alert-class', 'alert-info');
        return back();
    }


    public function usersOperation(Request $request)
    {
        if ((!$request->users || empty($request->users))) {
            return back();
        }
        $ops = $request->operation_operator;
        $values = $request->operation_value;
        $op = array_shift($ops);
        $val = array_shift($values);

        $users = User::whereIn('id', $request->users)->get();

        foreach ($users as $user) {
            $user->net_wage_demand = Datatable::operate($user->net_wage_demand, $op, $val);
            $user->save();
        }

        Session::flash('message', count($request->users) . 'db fizetési igénye módosítva lett!');
        Session::flash('alert-class', 'alert-info');
        return Redirect::back()->with('selected_rows', $request->users);
    }


}
