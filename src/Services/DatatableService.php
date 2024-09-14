<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2021. 05. 07.
 * Time: 13:35
 */

namespace DelocalZrt\Datatable\Services;


use DelocalZrt\Datatable\Jobs\ProcessBigCsvExportToFile;
use Carbon\Carbon;
use DelocalZrt\Datatable\Contracts\DatatableServiceInterface;
use DelocalZrt\Datatable\Models\DatatableUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use function Symfony\Component\HttpFoundation\getRealMethod;
use function Symfony\Component\Routing\getMethods;


class DatatableService implements DatatableServiceInterface
{


    protected $tableIdName;
    protected $header;
    protected $headerInfo;
    protected $headerRelatNameColumn = [];
    protected $order;
    protected $type; //select, checkbox
    protected $data;
    protected $ajax;
    protected $ajaxUrl;
    protected $selectedToSession;
    protected $chooseType = false;

    protected $selectImage; //a selectbe tudunk tenni képeket is, data['image']
    protected $selectRightSide; // a select2be tudunk a jobbszélre tenni cuccot, pl árat, databan ['righside']
    protected $selectSubId; // a select2be be van-e állítva a kategória IDje, databan ['subid']
    protected $isSelect2Char; // a select2be be van-e állítva a ~ elő char a,it hakiválasztunk # lesz kivéve

    protected $toolbarAtBottom; //ha lentre karjuk a gombokat tenni akkor true
    protected $form; //legyen saját <form> vagy ha false, akkor meg simán beépül
    protected $paging; //lapozás BOOL
    protected $selectAll; //legyen-e mindent kijelöl gomb
    protected $csv; //legyen-e csvexport gomb

    protected $search = null; //melyik oszlop, ha utána van s pl 2s, akkor subid-re veszi
    protected $operation = []; //operationmetikai művelet elhelyezése a megadot oszlopokba
    protected $action = []; //actionök, ehhez gombok is társulnak meg form is true kell hogy legyen nyilván
    protected $selectedId = []; //előre megadhatjuk melyiket Xszelje be /jelenítse meg
    protected $eloquentClass;

    protected $description = '';

    protected $out = [];


    public function initTable(string $tableIdName, string $type, $data, array $header = [], $eloquentClass = null)
    {
        $this->setTableIdName($tableIdName);
        $this->setType($type);
        $this->setHeader($header);
        $this->setData($data);
        $this->setEloquentClass($eloquentClass);
        $this->setAjax((empty($data) || (is_string($data) && !class_exists($data))));
        if ($this->getAjax()) $this->setAjaxUrl($data);


        /*
               $this->setOrder((array_key_exists('order', $config)) ? $config['order'] : null);
               $this->setChooseType((array_key_exists('chooseType', $config)) ? $config['chooseType'] : null);
               $this->setCsv((array_key_exists('csv', $config)) ? $config['csv'] : null);

                     //a megaddott config adatokat set()-teli
                     if (isset($config[$this->type])) {
                         foreach ($config[$this->type] as $key => $val) {
                             $setFn = "set" . ucfirst($key);
                             $this->$setFn($val);
                         }
                     }


                             $this->view->headLink()->prependStylesheet($this->view->basePath() . '/css/datatable/dataTables.bootstrap.css');
                             $this->view->headScript()
                                 ->prependFile($this->view->basePath('/js/datatable/dataTables.bootstrap.js'))
                                 ->prependFile($this->view->basePath('/js/datatable/input-page.js'))//lehessen megadni inputba melyik oldalra menjen
                                 ->prependFile($this->view->basePath('/js/datatable/pipeline-data.js'))//előre letölti a megadott számú oldalt, nem oldalanként ajaxszol
                                 ->prependFile($this->view->basePath('/js/datatable/utf8-hun-type.js'))//ha ez nincs akkor az ékesezets karakterek a "z" után jönnek
                                 ->prependFile($this->view->basePath('/js/datatable/jquery.dataTables.min.js'));
                             */
        return $this;
    }

    protected function generateDatatableServiceObject(): string
    {
        $obj = [
            'table_id_name' => $this->tableIdName,
            'header' => $this->getHeader(),

            'header_count' => count($this->header),
            'header_info' => $this->headerInfo,
            'header_info_with_url_encode' => urlencode(json_encode($this->headerInfo)),
            'max_element_per_ajax_loading' => config('datatable.max_element_per_ajax_loading'),

            'order' => $this->getOrder(),
            'type' => $this->getType(),
            'data' => $this->getData(),
            'choose_type' => $this->getChooseType(),
            'toolbar_at_bottom' => $this->getToolbarAtBottom(),
            'form' => $this->getForm(),
            'paging' => $this->getPaging(),
            'csv' => $this->getCsv(),
            'search' => $this->getSearch(),
            'operation' => $this->getOperations(),
            'action' => $this->action,
            'selected_ids' => $this->getSelectedIds(),
        ];
        return json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function render($dataTable = null): string
    {
        if ($dataTable instanceof DatatableService) {
            return $dataTable->render();
        }

        $render = '<div id="' . $this->getTableIdName() . '-datatableblock" class="datatableblock" data-ajax="' .
            (int)$this->getAjax() . '" data-idname="' . $this->getTableIdName() . '">';

        $render .= '<input type="hidden" id="datatable-service-object" value=\'' . $this->generateDatatableServiceObject() . '\'>';

        //a js blokk ebből dolgozik
        $render .= '<input type="hidden" id="jsinject" ';
        $render .= 'data-table-id-name="' . $this->getTableIdName() . '"
                    data-header-count="' . count($this->header) . '"
                    data-header-info="' . urlencode(json_encode($this->headerInfo)) . '"
                    data-select2-max-page="' . config('datatable.max_element_per_ajax_loading') . '"
                    data-is-select2-char="' . (int)$this->getIsSelect2Char() . '"
                    data-id-column="' . $this->getIdColumn() . '"
                    data-lang="hu_HU"
                    data-short-lang="hu"
                    data-is-ajax="' . (int)$this->getAjax() . '"
                    data-ajax-url="' . $this->getAjaxUrl() . '"
                    data-csv="' . $this->getCsv() . '"
                    data-selected-to-session="' . (int)$this->getSelectedToSession() . '"
                    data-table-type="' . $this->type . '"
                    data-search="' . count($this->getSearch()) . '"
                    data-datas="' . (!is_string($this->getData()) ? '' : $this->getData()) . '"
        >';

        $render .= ($this->getForm()) ? '<form id="' . $this->getTableIdName() . '-form" method="post">' : '';
        $render .= '<input type="hidden" name="_token" value="' . Request::session()->token() . '" />';
        $render .= '<input type="hidden" name="headerinfo" value=\'' . json_encode($this->headerInfo) . '\' />';
        $render .= '<input type="hidden" name="tableidname" value="' . $this->tableIdName . '" />';


        //... gif és elhomályosítás, amíg dolgozik adatatable
        $render .= '<div class="cover-container">';
        $render .= '<div class="cover-datatable"> <div class="loading-dot"> </div> </div>';

        if (empty($this->getToolbarAtBottom())) {
            $render .= '<div class="datatable-toolbar" id="datatable-toolbar">';
            foreach ($this->getAction() as $action) $render .= $action;
            $render .= '</div>';
        }

        $render .= $this->getSelectAll();

        $render .= $this->getSelect2Field();

        $render .= '<table id="' . $this->getTableIdName() . '"  class="dataTable table table-bordered table-hover width100">' . "\n";

        $render .= '<thead class="font-small-bold">' . "\n";
        $render .= '<tr>' . "\n";
        $render .= $this->getHeaderHtml();
        $render .= '</tr>' . "\n";
        $render .= $this->getSearchAndOperation();
        $render .= '</thead>' . "\n";

        $render .= '<tbody class="font-small-regular">' . "\n";
        $render .= $this->getRow();
        $render .= '</tbody>' . "\n";

        $render .= '</table>' . "\n";


        if ($this->getToolbarAtBottom()) {
            $render .= '<div class="datatable-toolbar" id="datatable-toolbar">';
            foreach ($this->getAction() as $action) $render .= $action;
            $render .= '</div>';
        }

        $render .= '</div>' . "\n";

        $render .= '<div id="hiddeninputs">' . "\n";
        $render .= $this->getSelectedHidden();
        $render .= '</div>' . "\n";

        $render .= ($this->getForm()) ? $this->getModalPopup() . "\n" : '';
        $render .= ($this->getForm()) ? '</form>' . "\n" : '';

        $render .= $this->getJavascriptBlock();


        $render .= '</div>' . "\n";

        return $render;
    }


    //----------------------------------------------------------------------------
    //---- SEGÉDFÜGGVÉNYEK  ------------------------------------------------------
    //----------------------------------------------------------------------------

    public function setData($data)
    {
        if ($data instanceof \Illuminate\Database\Eloquent\Builder) {
            $this->data = $this->getDatasFromCollection($data->get());
        } elseif ($data instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->data = $this->getDatasFromCollection($data);
        } elseif ($data instanceof Model || (is_string($data) && class_exists($data)) !== false) {
            $this->data = $this->getDatasFromCollection($data::all());
        } else {
            $this->data = $data;
        }

        $this->setAjax((empty($data) || (is_string($data) && !class_exists($data))));
    }


    protected function getDatasFromCollection(\Illuminate\Database\Eloquent\Collection $collection)
    {
        $out = [];
        $predefiniedHeader = !empty($this->header);
        foreach ($collection->toArray() as $i => $data) {
            if ($i == 0 && !$predefiniedHeader) $this->header = array_keys($data);

            $row = [$data[array_key_first($data)]];
            if ($predefiniedHeader) {
                $row = $row + array_intersect_key($data, $this->header);
            } else {
                $row = $row + $data;
            }

            $out[] = array_values($row);
        }
        if ($predefiniedHeader) $this->header = array_values($this->header);
        return $out;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getTableIdName()
    {
        return $this->tableIdName;
    }

    /**
     * @param mixed $tableIdName
     */
    public function setTableIdName($tableIdName)
    {
        $this->tableIdName = $tableIdName;
    }


    /**
     * @param mixed $header
     */
    public function setHeader(array $header)
    {
        $this->headerInfo = $header;
        $nestedHeader = [];
        foreach ($header as $field => $title) {
            $field = explode('|', $field)[0];
            $fieldExpl = explode('.', $field);
            $nestedHeader[$fieldExpl[0]] = $title;

            if (isset($fieldExpl[1])) {
                $this->headerRelatNameColumn[$fieldExpl[0]] = $fieldExpl[1];
            }
        }
        $this->header = $nestedHeader;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @return mixed
     */
    public function getType()
    {


        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        if ($givenType = Request::query($this->getTableIdName() . '-tabletype')) {
            $this->type = $givenType;
        } else {
            $this->type = $type;
        }
    }

    /**
     * @return mixed
     */
    protected function getSelectImage()
    {
        return (!$this->getAjax() && array_key_exists(0, $this->getData()) && array_key_exists('image', $this->getData()[0]));
    }

    /**
     * @param mixed $selectImage
     */
    protected function setSelectImage($selectImage)
    {

        $this->selectImage = $selectImage;
    }

    /**
     * @return mixed
     */
    protected function getSelectRightSide()
    {
        return (!$this->getAjax() && array_key_exists(0, $this->getData()) && array_key_exists('rightside', $this->getData()[0]));
    }

    /**
     * @param mixed $selectRightSide
     */
    protected function setSelectRightSide($selectRightSide)
    {
        $this->selectRightSide = $selectRightSide;
    }

    /**
     * @return mixed
     */
    protected function getSelectSubId()
    {
        return (!$this->getAjax() && array_key_exists(0, $this->getData()) && array_key_exists('subid', $this->getData()[0]));
    }

    /**
     * @param mixed $selectSubId
     */
    protected function setSelectSubId($selectSubId)
    {
        $this->selectSubId = $selectSubId;
    }


    public function setSearch($search)
    {
        $this->search = $search;
    }

    protected function getSearch()
    {
        if ($this->type === 'select') {
            return [];
        } elseif (is_null($this->search) && $this->eloquentClass) {
            $search = [];
            $headerField = array_keys($this->header);
            foreach ($headerField as $col => $field) {
                /** @var Model $model */
                $model = (new $this->eloquentClass());


                if ($this->isPublicMethodOf($model, $field)) {
                    $rel = $model->$field();

                    if ($rel instanceof BelongsTo || $rel instanceof BelongsToMany) {
                        $relModel = $rel->getModel();
                        $nameField = Schema::hasColumn($relModel->getTable(), 'name') ? 'name' : $relModel->getKeyName();
                        if ($rel->getModel()->count() <= config('datatable.max_element_in_select_in_search_column')) {
                            $search[$col . 's'] = ([0 => 'Összes...'] + $relModel::all()->pluck($nameField, $rel->getModel()->getKeyName())->toArray());
                        } else {
                            $search[$col] = $col;
                        }
                    }

                } elseif (Schema::hasColumn($model->getTable(), $field)
                    && (DB::connection()->getDoctrineColumn($model->getTable(), $field)->getType()->getName() == 'boolean')
                ) {
                    $search[$col . 's'] = (['' => 'Összes...', 0 => 'NEM', 1 => 'IGEN']);
                } elseif (Schema::hasColumn($model->getTable(), $field)) {
                    $search[$col] = $col;
                }
            }

            $this->search = $search;
        }
        return ($this->search ?? []);
    }

    public function setPaging($paging)
    {
        $this->paging = $paging;
    }

    protected function getPaging()
    {
        if (($this->paging !== false) && $this->type === 'checkbox') return true;
        if ($this->type === 'select') return false;

        return $this->paging;
    }


    public function getCsv()
    {
        if ($this->csv === false) return $this->csv;
        $this->csv = $this->getData() . (strpos($this->getData(), '?') ? '&' : '?') . 'csvexport=1&headerinfo=' . urlencode(json_encode($this->headerInfo));
        return $this->csv;
    }


    public function setCsv($csv)
    {
        $this->csv = $csv;
    }

    public function disableCsv()
    {
        $this->csv = false;
    }


    public function getAjaxUrl()
    {
        if (empty($this->ajaxUrl)) return '';
        return $this->ajaxUrl;
    }


    public function setAjaxUrl($ajaxUrl)
    {
        $this->ajaxUrl = $ajaxUrl;
    }


    protected function getSelectedToSession()
    {
        if ($this->selectedToSession === null) return true;
        return $this->selectedToSession;
    }


    protected function setSelectedToSession($selectedToSession)
    {
        $this->selectedToSession = $selectedToSession;
    }


    public function setToolbarAtBottom($toolbarAtBottom = true)
    {
        $this->toolbarAtBottom = $toolbarAtBottom;
    }

    public function getToolbarAtBottom()
    {
        return $this->toolbarAtBottom;
    }


    public function getChooseType()
    {
        return (bool)$this->chooseType;
    }


    public function setChooseType($chooseType)
    {
        $this->chooseType = $chooseType;
    }


    protected function setSelectAll($isSelectAll)
    {
        $this->selectAll = $isSelectAll;
    }


    public function setSelectedIds($selectedIds)
    {
        if (!is_array($selectedIds)) $selectedIds = [$selectedIds];
        $this->selectedId = $selectedIds;
    }


    public function getSelectedIds()
    {

        if ($this->type !== 'select') {
            return [];
        } else {
            if (empty($this->selectedId)) {
                $this->selectedId = Session::get('selected_rows') ?? $this->selectedId;
            }
            return $this->selectedId;
        }
    }


    //a ~ karakter a select optiöjei előtt amire ha katt akkor #-re változik
    protected function setIsSelect2Char($isSelect2Char)
    {
        $this->isSelect2Char = $isSelect2Char;
    }

    protected function getIsSelect2Char()
    {
        if ($this->isSelect2Char === false) return false;
        return true;
    }


    public function setForm($form)
    {
        $this->form = $form;
    }

    public function getForm()
    {
        if ($this->form === null) return true;

        return $this->form;
    }


    public function setOperations($operation)
    {
        if (!is_array($operation)) $operation = [$operation];

        $keys = array_keys($this->header);
        $assocTonumberKey = [];
        foreach ($operation as $column) {
            $assocTonumberKey[] = array_search($column, $keys);
        }

        $this->operation = $assocTonumberKey;
    }

    public function getOperations()
    {
        return $this->operation;
    }

    public function setAction(array $actions)
    {
        $this->action = $actions;
    }

    public function addAction(array $actions, ?string $keyName = null)
    {
        if($keyName) {
            $this->action[$keyName] = $actions;
        } else {
            $this->action[] = $actions;
        }
    }

    public function deleteAction(string $key)
    {
        unset($this->action[$key]);
    }


    public function setOrder($order)
    {
        if ((is_array($order) && count($order) == 0) || $order === null) {
            $order = [1 => 'asc'];
        }

        $keys = array_keys($this->header);
        $assocTonumberKey = [];
        foreach ($order as $column => $dir) {
            if ($column === 1) {
                $assocTonumberKey[$column] = $dir;
            } else {
                $assocTonumberKey[array_search($column, $keys)] = $dir;
            }
        }

        $this->order = $assocTonumberKey;
    }


    protected function getOrder()
    {
        if ($this->order === false) return false;
        if ((is_array($this->order) && count($this->order) == 0) || $this->order === null) return [1 => 'asc'];

        return $this->order;
    }


    public function setAjax($isAjax)
    {
        $this->ajax = $isAjax;
    }

    public function getAjax()
    {
        return $this->ajax;
    }

    //OPTIONÖK SETTER/GETTER VÉGE


    protected function getSelectAll()
    {
        if ($this->selectAll === false) return '';
        if ($this->type !== 'checkbox') return '';
        if ($this->getPaging() != true) return '';

        $output = '';
        $output .= '<span id="checkes" class="checkes" style="display: none">';
        $output .= '<a class="btn btn-md btn-default check-all-btn"> <input type="checkbox" name="check-all" id="check-all"> Összes <span class="on-search" style="display:none"> találat</span><span class="no-mobile"> kijelölése</span></a>' . "\n";
        $output .= '<a class="btn btn-md btn-default check-page-btn"> <input type="checkbox" name="check-page"  id="check-page"> Aktuális oldal <span class="no-mobile"> kijelölése</span></a>' . "\n";
        $output .= '</span>';

        return $output;
    }

    protected function getAction()
    {
        $actions = [];

        foreach ($this->action as $key => $action) {
            //először a nem post formos sima linkeket tesszük ki (tehát nem submit)
            if (!isset($action['href'])) continue;

            $actions[] = '
                 <a class="btn btn-sm btn-primary"  href="' . $action['href'] . '">
                    <span class="glyphicon glyphicon-' . ($action['icon'] ?? 'flash') . '"></span> ' . $action['name'] . '
                 </a>';
        }

        if (!empty($this->getChooseType())) {

            $type = ($this->getType() == 'select') ? 'checkbox' : 'select';
            $actions[] = '
                 <a class="btn btn-sm btn-default"  href="?' . $this->getTableIdName() . '-tabletype=' . $type . '">
                    <span class="glyphicon glyphicon-list"></span> Nézet váltása
                 </a>';
        }

        if (!empty($this->getCsv())) {

            $actions[] = '

             <a class="btn btn-sm btn-default bulkaction"
                       id="action-999"
                       data-title="CSV export"
                       data-table-id-name="' . $this->getTableIdName() . '"
                       data-url="' . $this->getCsv() . '"
                       data-warning-text="Kijelölt sorok exportálása CSV-be"
                       data-count-elem=""
                       data-ok-button-label="Letöltés"
                       data-cancel-button-label="Mégse"
                       data-keyboard="true"
                       data-toggle="modal"
                       data-target="#Modal-' . $this->getTableIdName() . '"
                       href="#Modal-' . $this->getTableIdName() . '"
           >
                        <span class="glyphicon glyphicon-list"></span>
            CSV export
            </a>

         ';
        }


        foreach ($this->action as $key => $action) {

            if (isset($action['href'])) continue;

            //formhoz kapocsló submitok
            $actionHtml = '
                 <a class="btn btn-sm btn-primary bulkaction"
                       id="action-' . $key . '"
                       data-title="' . $action['name'] . '"
                       data-table-id-name="' . $this->getTableIdName() . '"
                       data-url="' . $action['action'] . '"
                       data-warning-text="' . $action['warning'] . '"
                       data-count-elem="Érintett elemek száma: "
                       data-ok-button-label="' . ((array_key_exists('okButton', $action) && !empty($action['okButton'])) ? $action['okButton'] : 'OK') . '"
                       data-cancel-button-label="' . ((array_key_exists('cancelButton', $action) && !empty($action['cancelButton'])) ? $action['cancelButton'] : 'Mégse') . '"
                       data-keyboard="true"
                       data-toggle="modal"
                       data-target="#Modal-' . $this->getTableIdName() . '"
                       href="#Modal-' . $this->getTableIdName() . '"
           >
                        <span class="glyphicon glyphicon-' . ($action['icon'] ?? 'flash') . '"></span>
            ' . $action['name'] . '
            </a> ' . "\n";
            $actions[] = $actionHtml;

        }

        return $actions;


    }


    protected function getSelectedHidden()
    {

        $selectedHidden = '';
        foreach ($this->getSelectedIds() as $selectedId) {
            $selectedHidden .= ' <input type = "hidden" name = "' . $this->getTableIdName() . '[]" class="cbhidden" id = "hcb' . $selectedId . '" data-id = "' . $selectedId . '" value = "' . $selectedId . '"> ' . "\n";
        }

        return $selectedHidden;
    }


    protected function getHeaderHtml()
    {
        $headerTh = '';
        $headerTh .= '
            <td  class="text-center width20 delete-selectedrow-all-td"> ';
        if ($this->type === 'select') $headerTh .= ' <input id = "delete-selectedrow-all" class="delete-selectedrow-all" type = "checkbox"> ' . "\n";

        if ($this->type === 'checkbox' && $this->getPaging() != true) {
            $headerTh .= ' <input id = "check-page" type = "checkbox"> ' . "\n";
        } else {
            $headerTh .= '<span class"selected-nr" id = "selected-nr"></span> ' . "\n";
        }

        $headerTh .= '</td> ' . "\n";

        if (!key_exists(0, $this->header)) $this->header = array_values($this->header);

        for ($key = 0; $key < count($this->header); $key++) {
            $headerTh .= '<th class="nowrap" data-nr="' . $key . '"> ' . "\n";
            $headerTh .= (!($key == 1)) ? '' : ' <span class="toggle-vis" data-column = "' . $this->getIdColumn() . '"> [ID] </span> ' . "\n";
            $headerTh .= $this->header[$key] . '</th> ' . "\n";
        }

        //Ez azért kell, hogy ha új sort szúrnkbe, akkor mindig legfelül legyen, beszúrunk ++ számot, és utána ORDER DESC
        if (($this->type === 'select')) {
            $headerTh .= ' <th  class="order-new"> Sorting</th> ' . "\n";
        }

        return $headerTh;
    }


    protected function getRowData()
    {
        //ha ajax, akkor úgyis ajaxba kapja az anyagot
        if ($this->getAjax()) return [];

        return $this->getData();
    }


    protected function getRow()
    {
        if ($this->type === 'select') return '';
        $rowDatas = $this->getRowData();
        $trRend = '';

        $countheader = count($this->header) + (int)$this->getIdColumn(); //trükk lehet 0 és 1 is
        foreach ($rowDatas as $rowKey => $rowValues) {
            $trRend .= ' <tr id = "tr' . $rowValues[0] . '" data-id = "' . $rowValues[0] . '"> ' . "\n";
            for ($i = 0; $i < $countheader; $i++) {
                $s = ($i - 1) . 's';
                $trRend .= ' <td class="nowrap" ';
                $trRend .= (array_key_exists($s, $rowValues)) ? 'data-search = "' . $rowValues[$s] . '"> ' : '>';
                $trRend .= $rowValues[$i];
                $trRend .= ' </td> ' . "\n";
            }
            $trRend .= '</tr> ' . "\n";
        }

        return $trRend;
    }


    protected function getSelect2Field()
    {
        if ($this->type !== 'select') return '';
        $selectField = '';

        //a második keresés selectet ide tesszük select2-be
        foreach ($this->header as $key => $val) {
            if (array_key_exists($key . 's', $this->getSearch())) {
                $selectField .= ' <select class="form-control mobile-width100pc select-category" data-search-column = "' . $key . '"> ' . "\n";
                foreach ($this->getSearch()[$key . 's'] as $skey => $sval) {
                    $selectField .= ' <option value = "' . $skey . '">' . $sval . '</option>' . "\n";
                }
                $selectField .= '</select>' . "\n";
            }
        }

        if (!empty($columnsContent = $this->getRowData())) {
            $selectField .= '<select id="selectrow" class="' . $this->getTableIdName() . '-select-row mobile-width100pc">' . "\n";

            $selectField .= '<option id="empty-opt" value="">' . "\n";
            foreach ($columnsContent as $rowKey => $val) {
                $selectField .= '<option class="add-opt-row" data-id= "' . $val[0] . '"  id="row-opt' . $val[0] . '" ';
                foreach ($val as $valkey => $valval) {
                    if ($valkey === 0) continue;
                    if (is_int($valkey)) $valkey--;
                    $selectField .= 'data-' . $valkey . '="' . htmlspecialchars($valval) . '" ';
                }
                $selectField .= '>' . ($this->getIsSelect2Char() ? '~ ' : '') . strip_tags($val[2]) . '</option>' . "\n";
            }
            $selectField .= '</select>' . "\n";

        } else {
            $selectField .= '<select id="selectrow" class="select2-custom ' . $this->getTableIdName() . '-select-row mobile-width100pc">' . "\n";
            $selectField .= '</select>' . "\n";
        }

        return $selectField;
    }


    protected function getSearchAndOperation()
    {
        if ((empty($this->getSearch()) && empty($this->getOperations()))) return '';

        $out = '';
        $out .= '<tr class="search-column" role="row">' . "\n";

        foreach ($this->header as $key => $val) {
            if ($key == 0) {
                $out .= '<td></td>' . "\n";
            }

            $out .= '<td>' . "\n";

            if (in_array($key, $this->getSearch()) && $this->type !== 'select') {
                $out .= '<select id="filter-op-' . (($key - 1) + $this->getIdColumn()) . '" class="filter_op form-control">
                        <option value="">Alapért.</option>
                        <option value="=">=</option>
                        <option value="!=">!=</option>
                        <option value="like">tartalmazza</option>
                        <option value="not like">nem tartalmazza</option>
                        <option value=">">nagyobb mint</option>
                        <option value="<">kisebb mint</option>
                        <option value="(-)">tól-ig</option>
                        <option value="empty">üres</option>
                        <option value="not empty">nem üres</option>
                    </select><br>
                    <input type=\'text\' class=\'form-control datatable-search width100pc\' id="columns-value-' . (($key - 1) + $this->getIdColumn()) . '" data-index="' . (($key - 1) + $this->getIdColumn()) . '" name=\'columns[' . $key . '][search][value]\' placeholder=\'Keresés...\' />' . "\n";
            }

            if (in_array($key, $this->getOperations())) {
                $out .= '<div class="operationm">
                            <select class="width60px form-control inline-block" name="operation_operator[' . (($key - 1) + $this->getIdColumn()) . ']">
                                <option value="" disabled selected>Operátor...</option>
                                <option value=""">Nincs</option>
                                <option value="del">Meglévő érték törlése</option>
                                <option value="set">Meglévő érték felülírása</option>
                                <option value="prepend">Szöveg hozzáadása az elejéhez</option>
                                <option value="append">Szöveg hozzáadása a végéhez</option>
                                <option value="+">+</option>
                                <option value="-">-</option>
                                <option value="*">*</option>
                                <option value="/">/</option>
                                <option value="%">%</option>
                            </select>
                            <input type=\'text\' class=\'form-control operation-value width100px\'  placeholder=\'Érték\' name=\'operation_value[' . $key . ']\' step=\'0.01\' /></div>' . "\n";

            } elseif ($this->type !== 'select' && array_key_exists((($key - 1) + $this->getIdColumn()) . 's', $this->getSearch())) {
                $out .= '<select class="form-control select-category datatable-search width100pc" name="columns[' . $key . '][search][value]" data-index="' . $key . '">' . "\n";
                foreach ($this->getSearch()[$key . 's'] as $skey => $sval) {
                    $out .= '<option value="' . $skey . '">' . $sval . '</option>' . "\n";
                }
                $out .= '</select>' . "\n";
            }

            $out .= '</td>' . "\n";
        }
        if (($this->type === 'select')) {
            $out .= '<td class="order-new"> </td>' . "\n";
        }
        $out .= '</tr>' . "\n";

        return $out;

    }


    protected function getIdColumn()
    {
        return 1;
    }

    /**
     * @return Model
     */
    public function getEloquentClass(): ?string
    {
        return $this->eloquentClass;
    }

    /**
     * @param Model $eloquentClass
     */
    public function setEloquentClass(?string $eloquentClass): void
    {
        $this->eloquentClass = $eloquentClass;
    }

    public function enableChooseType()
    {
        $this->chooseType = true;
    }

    public function disableChooseType()
    {
        $this->chooseType = false;
    }

    public function enableFormTag()
    {
        $this->form = true;
    }

    public function disableFormTag()
    {
        $this->form = false;
    }

    public function setTypeToSelect()
    {
        $this->type = 'select';
    }

    public function setTypeToCheckbox()
    {
        $this->type = 'checkbox';
    }


    protected function getModalPopup()
    {
        return '<div class="modal" data-keyboard="true" tabindex="-1" id="Modal-' . $this->getTableIdName() . '">
        <div class="modal-dialog"><div class="modal-content"><div class="modal-body">
        <h4 id="modalTitle" class="modal-title"></h4>
        <p class="text-center font-normal-regular" id="dialogText"></p>
        <span id="selected-elem-nr" class="selected-elem-nr text-center"></span></div>
        <div class="text-center modal-button">
        <button type="button" name="submit-delete" id="ok-button" class="btn btn-primary" value="">Ok</button>
        <button type="button" class="btn btn-default" id="cancel-button" data-dismiss="modal">Mégse</button>
        </div></div></div></div>';
    }


    protected function getJavascriptBlock()
    {
        /*
              $this->view->headScript()->prependFile($this->view->basePath('js/select2/i18n/' . $this->view->translate('hu') . '.js'));
              $this->view->headScript()->prependFile($this->view->basePath('js/select2/select2.min.js'));
              $this->view->inlineScript()->appendFile($this->view->basePath('/js/select2/select2.init.js'));

      */
        $datatableInit = '<script>' . "\n";

        ////////////////////////////////////////////////////////////////////////////////
        // ALAP DATATABLE KONFIGURÁLÁS
        ////////////////////////////////////////////////////////////////////////////////

        $datatableInit .= '

$(document).ready(function () {
//TODO: valamiért nem kapja el a hibát, elvileg, ha a response ban van error property akkor le kéne kezelnie, és jeéezni, hogy nem OK valami
//de most csak simán betölti az üres tömböt (data property kötelező mező errornál is)
$.fn.dataTable.ext.errMode = function ( settings, helpPage, message ) { alert(message)};

    var tableIdName = "' . $this->getTableIdName() . '";
    var tableContainer = $("#" + tableIdName + "-datatableblock");
    var cover = tableContainer . find(".cover-datatable");
    cover . css("display", "block");

    var par = tableContainer . find("#jsinject");
    var form = tableContainer . find("#" + tableIdName + "-form");
    var table = $("#" + tableIdName + "", "#" + tableIdName + "-datatableblock") . DataTable({';

        $datatableInit .= ('hu_HU' == 'en_US') ? '' : '
        "language": {
                "url": "/vendor/datatable/js/datatables-lang/" + par . data("lang") + ".json"
        },
';

        $datatableInit .= '
        "stateSave": false,
        "bSortCellsTop": true,
        "pagingType": "input",
        "paging":   ' . (($this->getPaging()) ? 'true' : 'false') . ',
        "ordering": ' . (($this->getOrder() !== false) ? 'true' : 'false') . ',
        "info":  ' . (($this->type !== 'select') ? 'true' : 'false') . ',
        "searching": true, ';


        $datatableInit .= ($this->type !== 'checkbox') ? ' ' : '
        "drawCallback": function (settings ) {
                checkAndDisableAll(tableIdName);
                checkCheckboxFromHidden(tableIdName);
            },  ';


        $datatableInit .= (!empty($this->getAjax()) && ($this->type !== 'select')) ? '
        "processing": true,
        "serverSide": true,
        "ajax": $.fn.dataTable.pipeline( {
            "url": "' . $this->getData() . (strpos($this->getData(), '?') ? '&' : '?') . 'headerinfo=' . urlencode(json_encode($this->headerInfo)) . '" , //TODO:valahogy szebben megoldani
            "type": "POST",
           	xhrFields: { withCredentials: true },
            /*
            "error": function(jqXHR, ajaxOptions, thrownError) {
              alert(thrownError + "\r\n" + jqXHR.statusText + "\r\n" + jqXHR.responseText + "\r\n" + ajaxOptions.responseText);
                alert("Hiba történt az adatok betöltése során, talán rosszul lett megadva egy keresési feltétel (pl. dátum szűrésnél érvénytelen érték lett megadva stb")
                window.location.reload();
             }, */
            "pages": ' . (config('datatable.max_element_per_ajax_loading') / 10) . ' // number of pages to download per ajax loading
        }),


    ' : '';


        $datatableInit .= '
        "columnDefs": ';

        if ($this->type === 'checkbox') {
            $datatableInit .= '
            [{
                "width": "20px",
                "className": "text-center",
                "targets": 0,
                "searchable": false,
                "orderable": false,
                //"dom": \'<"top"fpl>rt<"bottom"><"clear">\',
                //"className": "dt-body-center",
                "render": function (data, type, full, meta) {
                return "<div class=\"coverCheckboxCont\">" +
                    "<input type=\"checkbox\" class=\"rowCheckbox\" data-nameforhidden=\" " + tableIdName + "[]\" " +
                    "value=\"" + $("<div/>") . text(data) . html() + "\" " +
                    "id=\"cb" + $("<div/>") . text(data) . html() . replace(/^\D +/g, "") +"\">" +
                    "</div>";
                }
            },
';

            $datatableInit .= '{ "targets": "_all", "type": "localecompare" }';

            $datatableInit .= '],';


        } elseif ($this->type === 'select') {
            $datatableInit .= '[{
                "width": "20px",
            "className": "text-center",
            "targets": 0,
            "searchable": false,
            "orderable": false,
        }],';
        } else {
            $datatableInit .= '[{ "targets": "_all", "type": "localecompare" }],';
        }

        $order = '';
        if (is_array($this->getOrder())) {
            foreach ($this->getOrder() as $key => $val) {
                $c = ($key + $this->getIdColumn());
                $order .= '[' . ($c) . ',"' . $val . '"],';
            }
            $order = rtrim($order, ',');
        }
        $datatableInit .= '
        "order": [' . $order . ']
    });
        ';

//ALAP DATATABLE KONFIGURÁLÁS VÉGE
        $datatableInit .= file_get_contents(__DIR__ . '/../Js/datatable-include.js');

        $datatableInit .= '
 }); //document loadot tettem a legelejére

        </script> ' . "\n";

        return $datatableInit;
    }

    public function __toString()
    {
        return $this->render();
    }


    public function getModifiedValue($value, $operator, $givenValue)
    {

        switch ($operator) {
            case "+":
                return $value + $givenValue;
            case "-":
                return $value - $givenValue;
            case "*":
                return $value * $givenValue;
            case "/":
                return $value / $givenValue;
            case "%":
                return $value % $givenValue;
            case "del":
                return null;
            case "set":
                return $givenValue;
            case "prepend":
                return $givenValue . $value;
            case "append":
                return $value . $givenValue;
            default:
                return $value;

        }
    }

    public function getFilteredIds($eloquent, array $params)
    {
        $tableId = $params['tableidname'];
        if (isset($params[$tableId])) {
            return $params[$tableId];
        } elseif (isset($params['check-all']) && $params['check-all'] == 'on') {
            return $this->getFilteredRows($eloquent, $params, true);
        }

        return [];

    }

    public function hasTriggeredBackgroundCsvGenerating(string $controllerClass, string $actionMethod, $data, $request)
    {

        $params = ($request instanceof \Illuminate\Http\Request) ? $request->all() : $request;

        if ($data instanceof \Illuminate\Database\Eloquent\Builder) {
            $modelForQueryBuilder = $data;
        } elseif ($data instanceof Model || (is_string($data) && class_exists($data))) {
            $modelForQueryBuilder = $data::query();
        } else {
            throw new \Exception('Not valid Eloquent or Builder for first argument');
        }


        $header = json_decode(urldecode($params['headerinfo']), true);

        $count = (isset($params['tableidname']) && isset($params[$params['tableidname']]))
            ? count($params[$params['tableidname']])
            : $modelForQueryBuilder->count();

        if (!isset($params['from_queue']) && isset($params['csvexport']) && $params['csvexport'] && (($count * count($header)) > config('datatable.csv_in_background_from_fields'))) {
            ProcessBigCsvExportToFile::dispatch($controllerClass, $actionMethod, $params);
            return true;
        }

        return false;
    }


    /**
     * Itt azért kell összeömleszteni mindent, hogy tutira mindig ugyanazokat a
     * találatokat adja vissza, négy eseményt dolgoz fel:
     *
     * checkbox táblához adatszolgáltatás
     * select táblához adatszolgáltatás
     * actionhöz ID-k visszaadása getFilteredIds hívja meg
     * export csv-hez adatszolgáltatás
     *
     * @param $data
     * @param array $params
     * @param false $onlyIds
     * @return array
     * @throws \Exception
     */
    public function getFilteredRows($data, array $params, $onlyIds = false)
    {

        try {

            $saveToCsvInBackground = isset($params['from_queue']);
            //Datatable-től ajax paraméterben ilyenek jöhetnek számmal azonosítva az oszlopot (az első ID = 0. oszlop):
            //columns[1][data]: 1
            //columns[1][name]:
            //columns[1][searchable]: true
            //columns[1][orderable]: true
            //columns[1][search][value]:"beírtkeresésszövegaz 1.oszlophoz" // (ID oszlop = 0. oszlop)
            //columns[1][search][regex]: false
            //order[0][column]: 2  // ==> ez itt az 1 oszlopot jelenti...
            //order[0][dir]: asc
            //start: 0   //= offset
            //length: 10

            //-----------------------------------------------------------------

            //Actionhöz mehet ilyen, és onnan meg ide irányítja, hogy ez alapján adja vissza az id-ket
            //$ajaxParam[SearchCrit][1] = "beírtkeresésszövegaz 1.oszlophoz" // (ID oszlop = 0. oszlop)

            $headerAssoc = json_decode(urldecode($params['headerinfo']), true);

            $this->setHeader($headerAssoc);
            $headerAssoc = $this->header;
            $limit = floor(config('datatable.csv_in_background_from_fields') / count($headerAssoc));
            $offset = null; //ebben az esetben végig kell mennünk (csvexport), ha lett beállítva, akkor csak a konkrét oldallal felül lesz írva

            if ($data instanceof \Illuminate\Database\Eloquent\Builder) {
                $builder = $data;
            } elseif ($data instanceof Model || (is_string($data) && class_exists($data))) {
                $builder = $data::query();
            } else {
                throw new \Exception('Not valid Eloquent or Builder for first argument');
            }
            $elModel = $builder->getModel();
            $idField = $elModel->getKeyName();
            $columns = Schema::getColumnListing($elModel->getTable());
            $header = array_keys($headerAssoc);

            //Exportnál van olyna, hogy küldjük az ID-t, hogy melyiket irassa ki
            if (isset($params['tableidname']) && isset($params[$params['tableidname']]) && !empty($params[$params['tableidname']])) {
                $builder->whereIn($idField, $params[$params['tableidname']]);
            } else {

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//select2 -nél van amikor üresen jön
                if (!isset($params['columns'])) {
                    //először a selectedId-ben beállított ID-ket lekérdezzük, ennél ez a mező jön, a többi szűrővel nyílván nem kell foglalkozni
                    if (isset($params['iid']) && is_array($params['iid'])) {
                        $builder->whereIn($idField, $params);
                    } else {
                        if (isset($params['q']) && !empty($params['q'])) {
                            $builder->where('name', 'like', ('%' . $params['q'] . '%'));
                        }
                        $count = $builder->count();
                        $offset = config('datatable.max_element_per_ajax_loading') * ($params['page'] ?? 0);
                        $limit = (config('datatable.max_element_per_ajax_loading'));
                    }
                } //select2 vége

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//sima ajax datatable kérés checkbox

                if (isset($params['columns'])) {
                    foreach ($params['columns'] as $col => $item) {
                        $term = $item['search']['value'];
                        if ($term === '' || is_null($term)) continue;

                        if (in_array($header[$col], $columns)) {
                            //$builder->where('email', 'like', 'gulya');
                            $this->setWhereByOperate($builder, $header[$col], $term);
                        } else {
                            //idegen kulcs ID a mező
                            if (!empty($term)) {
                                $builder->whereHas($header[$col], function ($q) use ($term,$idField) {
                                    $q->where($idField, $term);
                                });
                            }
                        }
                    }

                    if ($onlyIds) {
                        return $builder->pluck($idField)->toArray(); //ha actionből jön a kérés, akkor csak az id-ket adjuk neki vissza
                    }

                    $count = $builder->count();

                    if (isset($params['order'])) {
                        foreach ($params['order'] as $param) {
                            $col = ($param['column'] - 1); // valamiért itt elcsúszik ezért kell kivonni
                            if (in_array($header[$col], $columns)) {
                                $builder->orderBy($header[$col], $param['dir']);
                            }
                        }
                    }

                    if (!(isset($params['csvexport'])) && isset($params['length'])) {
                        $limit = $params['length'] ?? config('datatable.max_element_per_ajax_loading');
                        $offset = $params['start'] ?? null;
                    }
                } //checkboxos vége
            }////idk megadva formázott sorok lekéréséhez vége


            //checkbox + select2 közös rész
            $fileRes = null;
            if ($saveToCsvInBackground) {
                if (!file_exists(storage_path('app/datatable_csvs/' . $params['tableidname']))) {
                    mkdir(storage_path('app/datatable_csvs/' . $params['tableidname']), 0777, true);
                }

                $uniquePart = strtolower(Str::random(6)) . '_utc_' . date('Ymd_His');
                $csvFilename = storage_path('app/datatable_csvs/' . $params['tableidname'] . '/' . $params['tableidname'] . '_' . $uniquePart . '.csv');
                touch($csvFilename);
                $fileRes = fopen($csvFilename, 'w');
                fprintf($fileRes, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($fileRes, array_values($headerAssoc), ';');
            }

            $this->out = [];
            $count = $count ?? $builder->count();

            //ha van be-llítva oldal (pager) akkor azt a oldalt csk tehát max offset = offset
            $maxOffset = $offset ?? $count;
            $offset = $offset ?? 0;

            //if ($saveToCsvInBackground)
            //   Log::error(print_r([$offset, $maxOffset, $count, $limit], true));

            $builder->skip(intval($offset));
            $builder->take(intval($limit));

            $idName = $builder->getModel()->getKeyName();

            //ha sima tól ig olda van akkor is működik, meg ha csv-nél nagyobb szeleteket akarunk
            while ($offset <= $maxOffset) {
                foreach ($builder->get() as $row) {
                    //ha checkbox, akkor kell +1 id oszlop
                    $modifiedRow = isset($params['columns']) && !(isset($params['csvexport'])) ? [$row->{$idName}] : [];
                    foreach ($headerAssoc as $field => $nice) {
                        $field = trim($field);
                        $relatNameColumn = $this->headerRelatNameColumn[$field] ?? 'id';
                        if (key_exists($field, $row->getAttributes()) || is_scalar($row->{$field})) {
                            if ($row->{$field} instanceof \DateTime) {
                                $modifiedRow[$field] = $row->{$field}->format('Y-m-d H:i:s');
                            } else {
                                $modifiedRow[$field] = $row->{$field};
                            }
                            //N+1 lekérdezési hiba elhárítva, ha behúztuk a query builderbe: with('kapcsoltatMegvalósítóFg')
                        } elseif (array_key_exists($field, $row->getRelations()) && ($row->{$field} instanceof Model || is_null($row->{$field}))) {
                            $field2 = $row->{$field};
                            $modifiedRow[$field] = $field2 ? $field2->{$relatNameColumn} : '';

                        } elseif (array_key_exists($field, $row->getRelations()) && $row->{$field} instanceof Collection) {
                            if ($row->{$field}->first() && isset($row->{$field}->first()->{$relatNameColumn})) {
                                $modifiedRow[$field] = $row->{$field}->pluck($relatNameColumn)->implode('<br>');
                            } else {
                                $modifiedRow[$field] = '';
                            }

                        } elseif ($this->isPublicMethodOf($row, $field) && $row->{$field}() instanceof BelongsTo) {
                            $field2 = $row->{$field}()->first();
                            $modifiedRow[$field] = $field2 ? $field2->{$relatNameColumn} : '';
                        } elseif (
                            $this->isPublicMethodOf($row, $field) && $row->{$field}() instanceof BelongsToMany
                            || $this->isPublicMethodOf($row, $field) && $row->{$field}() instanceof HasMany
                        ) {
                            if ($row->{$field}()->first() && isset($row->{$field}()->first()->{$relatNameColumn})) {
                                $modifiedRow[$field] = $row->{$field}()->pluck($relatNameColumn)->implode('<br>');
                            } else {
                                $modifiedRow[$field] = '';
                            }

                        } elseif ($this->isPublicMethodOf($row, $field) && $row->{$field}() instanceof Model) {
                            $modifiedRow[$field] = $row->{$field}()->{$relatNameColumn};
                        } elseif ($this->isPublicMethodOf($row, ('get' . Str::camel($field) . 'Attribute'))) {
                            $meth = ('get' . Str::camel($field) . 'Attribute');
                            if (is_array($row->$meth())) {
                                $modifiedRow[$field] = implode('<br>', $row->{$meth}());
                            } elseif ($row->$meth() instanceof \Illuminate\Support\Collection) {
                                $modifiedRow[$field] = $row->$meth()->implode('<br>');
                            } else {
                                $modifiedRow[$field] = $row->$meth();
                            }
                        } else {
                            $modifiedRow[$field] = '-';
                        }
                    }
                    if ($saveToCsvInBackground) {
                        foreach ($modifiedRow as &$col) {
                            $col = str_replace('<br>', ' | ', $col);
                            $col = strip_tags(preg_replace('/\s+/', ' ', $col));
                        }

                        fputcsv($fileRes, $modifiedRow, ';');
                    } else {
                        $this->out[] = $modifiedRow;
                    }

                }
                $offset += $limit;
                $builder->skip(intval($offset));
            }

            if ($saveToCsvInBackground) {
                $this->deleteOldCsvFiles($params['tableidname']);
            }

            if (!config('datatable.max_element_per_ajax_loading')) throw new \Exception('Nince beállítva a datatable.max_element_per_ajax_loading config');
            return [
                'count' => ($count ?? 0),
                'offset' => ($offset ?? 0),
                'limit' => config('datatable.max_element_per_ajax_loading'),
                'data' => $this->out,
                'csvexport' => isset($params['csvexport']),
                'header' => $this->header
            ];
        } catch (\Throwable $e) {

            dd($e);
            die(json_encode([
                'draw' => (Request::post('draw') ?? 1),
                //'recordsTotal' => 0,
                //'recordsFiltered' => 0,
                //TODO itt kéne jelezni a hibát a datatable felé de nem kapja el, scak kiírtja az üres data-t, úgyhogy inkább nem
                //küldöm egyelőre és hadd fusson hibéra a frontend, hogy ne legyen megtévesztő
                //'data' => [],
                'error' => $e]));
        }
    }

    protected function setWhereByOperate(Builder $builder, $column, $term)
    {
        if (strpos($term, '|') === false) {
            if (is_numeric($term)) {
                $builder->where($column, $term);
            } else {
                $builder->where($column, 'like', '%' . $term . '%');
            }
        } else {
            //lett megadva operátor
            $operatorExp = explode('|', $term);
            $op = trim($operatorExp[0]);
            $val = trim($operatorExp[1]);

            if ($op === 'empty') {
                $builder->where(function ($qNull) use ($column) {
                    $qNull->whereNull($column)->orWhere($column, '');
                });
            } elseif ($op === 'not empty') {
                $builder->whereNotNull($column)->where($column, '!=', '');
            } elseif ($op === '(-)') {
                $fromTo = explode('ꟷ', $val);
                $from = trim($fromTo[0]);
                $to = isset($fromTo[1]) ? trim($fromTo[1]) : null;

                if ($from) $builder->where($column, '>=', $from);
                if ($to) $builder->where($column, '<', $to);

            } else {
                if ($val == '') return;

                $pc = strpos($op, 'like') !== false ? '%' : '';
                $builder->where($column, $op, ($pc . $val . $pc));
            }

        }
    }

    /**
     * Itt is azért van összeömlesztve, hogy minden esetnél ugyanaz  a folyamat fusson le
     * @param $data
     * @return array|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function getResponseForDatatable($data, $eloquentClass = null)
    {
        if ($data['csvexport'] ?? false) {
            if (isset($data['job_to_queue'])) {
                return back()->with('success', 'A CSV fájl készítése folyamatban vna, ha elkészül lent lesz látható!');
            }

            foreach ($data['data'] as &$item) {
                foreach ($item as &$it) {
                    $it = str_replace('<br>', ' | ', $it);
                    $it = (preg_replace('/\s+/', ' ', $it)); //a sortörésekből spacet csinál
                    $it = trim(strip_tags($it));
                }
            }

            return $this->getCsvResponse($data, $eloquentClass);
        } else {
            if (Request::post('columns')) {
                $out = [];
                foreach ($data['data'] as $item) {
                    $arr = ($data['data'] instanceof Collection) ? array_values($item->toArray()) : array_values($item);
                    $out[] = $arr;
                }
                $draw = Request::post('draw') ?? 1;
                return [
                    'draw' => $draw,
                    'recordsFiltered' => $data['count'],
                    'recordsTotal' => $data['count'],
                    'data' => $out
                ];
            } else {
                //select2
                $out = [];
                $secondField = null;
                foreach ($data['data'] as $item) {
                    if (!$secondField) {
                        $fields = array_keys($item);
                        $secondField = $fields[1];
                    }

                    $arr['id'] = $item['id'];
                    $arr['text'] = $item['id'] . ' | ' . strip_tags($item[$secondField]);
                    $arr['data'] = array_values($item);
                    $out[] = $arr;
                }
                return ['items' => $out, 'total_count' => $data['count']];
            }
        }
    }


    protected function getCsvResponse($data, $eloquentClass)
    {
        $table = $eloquentClass ? (new $eloquentClass())->getTable() : '';
        $fileName = $table . '_' . Carbon::now()->format('Y-m-d_His');
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0'
            , 'Content-type' => 'text/csv'
            , 'Content-Disposition' => 'attachment; filename=' . $fileName . '.csv'
            , 'Expires' => '0'
            , 'Pragma' => 'public'
        ];


        $list = $data['data'];
        # add headers for each column in the CSV download
        array_unshift($list, $data['header']);

        $callback = function () use ($list) {
            $FH = fopen('php://output', 'w');
            fprintf($FH, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($list as $row) {
                fputcsv($FH, $row, ';', '"');
            }
            fclose($FH);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function geCsvtFiles()
    {

    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array
     */
    public function getCsvFiles(): array
    {
        $dir = storage_path('app/datatable_csvs/' . $this->tableIdName);

        if (!file_exists($dir)) return [];
        $files = [];
        foreach (new \DirectoryIterator($dir) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            $files[$fileInfo->getMTime()] = strstr($fileInfo->getPathname(), 'app/datatable_csvs');

        }
        krsort($files);
        return $files;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }


    protected function isPublicMethodOf(object $obj, $methodName): bool
    {
        if (!method_exists($obj, $methodName)) return false;

        $publicMeths = (new \ReflectionClass($obj))->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($publicMeths as $met) {
            if ($met->name === $methodName) return true;
        }

        return false;
    }

    protected function deleteOldCsvFiles($tableIdName)
    {
        $directory = storage_path('app/datatable_csvs/' . $tableIdName);
        $iterator = new \DirectoryIterator($directory);
        $filenames = [];
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $filenames[$fileinfo->getMTime()] = $fileinfo->getPathName();
            }
        }
        krsort($filenames);

        $oldFiles = array_slice($filenames, config('datatable.keep_csv_files_per_datatable', 10));

        foreach ($oldFiles as $oldFile) {
            if (file_exists($oldFile)) unlink($oldFile);
        }

    }

}


