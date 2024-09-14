//Minden egyes datatable alá bemásolja ennek a fájlnak a tartalmát a backend


var tableIdName = tableIdName;
var tableContainer = tableContainer;
var table = table;
var par = par;
var cover = cover;
var selectedItemNr = 0;


///////////////////////////////////////////////////////
// --- ÁLTALÁNOS FÜGGVÉNYEK ÉS MŰVELETEK ESEMÉNYEK (SELECTES ÉS CHECKBOXOS IS EGYARÁNT SZÜKSÉGESEK)------
////////////////////////////////////////////////////////

// az URLben lévő megadott nevű GET queryt parseolja és adja vissza returnben
function parseGet(name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    return (results ? results[1] : 0);
}


// Általános keresés mező kilövése (nincs rá szükség, mivel minden oszlophpz tartozik amihez kell
$("#" + tableIdName + "_filter").hide(); //dinamikusan létrehozott elem azért ne megy


//A datatablénál ha bármilyen művelet van akkor amíg tart a művelet, takarja el a coverrel és giffel
table.on('processing.dt', function (e, settings, processing) {
    if (par.data("table-type") !== "select") {
        cover.css('display', processing ? 'block' : 'none');
    }
});


// ID OSZLOP kikaőcsolása alaphelyzetbe helyezés
var column = table.column(parseInt(par.data("id-column")));
column.visible(!column.visible());

//id OSZLOP KI-BE KAPCSOLÁSA GOMBNYOMÁSRA
tableContainer.find("span.toggle-vis").on("click", function (e) {
    e.stopPropagation();
    column.visible(!column.visible());
});


//az action gomboknál ha disabledek akkor ne engedje a click eseményt, tehát hogy a modal felugorjon
$(".bulkaction").on("click", function (event) {
    if ($(this).hasClass("disabled")) {
        event.stopPropagation();
    }
    $('.model').show();
    $('.modal-content').show();
    $('.modal-backdrop').show();
});

///////////////////////////////////////////////////////
//--- CHECKBOXOS SEGÉDFÜGGVÉNYEK ------------------------------------------------------------------
///////////////////////////////////////////////////////

//(CB) A Datatable callback (draw callback)  fg-ei,  az összes datatable oldal betöltésekor (lapozásnál is) lefutnak


//Fejlécben a kereső mezők eseményeinek lekezelése
document.body.addEventListener("keyup", function (e) {
    if (!$(e.target).hasClass('datatable-search')) return;
    return;
});
document.body.addEventListener("change", function (e) {
    // e.target is start point of bubbling
//    console.log(e.target);
    if (!$(e.target).hasClass('datatable-search')) return;

    try {
        table.column($(e.target).data('index')).search($(e.target).val(), false, false).draw();
    } catch (e) {
        //TODO: valahogy elkapniha 500-al jön vissza az ajax és ilyenkor újratölteni az oldalt, itt nem kapja el
        // location.reload();
    }
});


//Hz összes kijelölése be van pipálva akkor az összes oldalon pipálja be és disabledre tegye be a checkboxot
function checkAndDisableAll(tableIdName) {
    if (tableContainer.find("#check-all").prop("checked") == true) {
        $(".rowCheckbox", tableContainer).prop("checked", true);
        $("#" + tableIdName + " tbody tr", tableContainer).addClass("selectedRow");
        $(".rowCheckbox", tableContainer).prop("disabled", "disabled");
    } else {
        $(".rowCheckbox", tableContainer).prop("checked", false);
        $("#" + tableIdName + " tbody tr", tableContainer).removeClass("selectedRow");
        $(".rowCheckbox", tableContainer).removeClass("disabled");
    }
}


// (CB) Megnézzük a hidden elemeket, és ha a szerepel az adott oldalon a sor, akkor be is pipálja a checkboxot
function checkCheckboxFromHidden(tableIdName) {
    var table = $("#" + tableIdName);
    var cb;

    tableContainer.find(".cbhidden").each(function () {
        cb = table.find("#cb" + this.value);
        if (cb.length != 0) {
            cb.prop("checked", "checked");
            cb.closest("tr").addClass("selectedRow");
        }
    });


    //ha az összes cb be van pipálva az oldalon, (és nem összes kijelölés) akkor a check-page cb is pipa legyen
    var isAll = true;
    if (table.find(".rowCheckbox").length > 0) {
        table.find(".rowCheckbox").each(function () {
            if (this.checked == false) {
                isAll = false;
            }
        });
    } else {
        isAll = false;
    }
    tableContainer.find("#check-page").prop("checked", isAll);
}

//(CB) A Datatable callback függyvéneyk vége --------


//--- (CHECKBOX fg) CB SEGÉDFÜGGVÉNYEK VÉGE ----------------------------------------------

///////////////////////////////////////////////////////
//--- (SELECT fg) SELECTES SEGÉDFÜGGVÉNYEK ----------------------------------------------
///////////////////////////////////////////////////////


//-- (SELECT fg) A CBHIDDENBEN LEVŐ (TEHÁT A KIJELÖLT ELEMEK) ÖSSZEGYÜJTÖTTE ÉS BEPARAMÉTEREZTE EZUTÁS
//ÖSSZEMERGELI  AZ AJAXBÓL LEHÍVOTTAKKAL selectedRowOut
function mergeInAnOutElement(selectedRowOut) {

    if ((selectedRowOut.length > 0)) {
        //letöltjük a hidden inputban jelzett ID-jű sorokat
        var purl = par.data('ajax-url').indexOf('http') === 0 ? par.data('ajax-url') : window.location.protocol + '//' + window.location.hostname + par.data('ajax-url');
        $.post(purl + "?s2=1&headerinfo=" + par.data('headerInfo'), {'iid[]': selectedRowOut},
            function (dataJson) {
                var attrDatas = [];
                var id;
                var data = dataJson;
                if (data.length == 0) {
                    cover.css('display', 'none');
                    return;
                }
                var len = data.items.length;

                // az ajaxszal letöltötteket bedobjuk ki a táblázatba
                for (var j = 0; j < len; j++) {
                    id = data.items[j].id;
                    attrDatas = [];

                    var headerCount = parseInt(par.data("header-count"));
                    for (var i = 1; i < headerCount; i++) {
                        attrDatas[i] = data.items[j].data[i];
                    }

                    addRowFromSelect(id, attrDatas, true);
                }

                cover.css('display', 'none');

            });
    }
}

//-- (SELECT fg) ROW hozzáadása, több esemény is ezt használja: erre a selectRow change (tehát elem kiváasztása)
//  és az előre betöltött input hiddeneknél is ez fut le
function addRowFromSelect(id, attrDatas, ignoreHidden) {
    if (tableContainer.find("#hcb" + id).length && ignoreHidden != true) {
        location.hash = "#row-" + id + "-" + tableIdName;
        return;
    }

    var data = [];
    for (var i = 1; i < parseInt(par.data("header-count")); i++) {
        data[i] = attrDatas[i];
    }


    //a checkbox létrehozása, amivel ki tudjuk törölni a sorból
    var rowElement = [
        "<span class=\"anchor\" id=\"row-" + id + "-" + tableIdName + "\"></span>" +
        "<input type='checkbox' checked='checked' class='delete-selectedrow' data-id='" + id + "' id='row-" + id + "'>",
        id
    ];


    for (var j = 1; j < parseInt(par.data("header-count")); j++) {
        rowElement.push(data[j]);
    }

    rowElement.push(newRowColumn);
    newRowColumn++;

    //ezt php foreachel szépen kitöltjük a datas array alapján
    table.row.add(rowElement).order([[rowElement.length - 1, 'desc']]).draw(false);


    //ha betöltéskor már megvolt a hidden input csinálva akkor nem kell újat
    if (ignoreHidden == true) {
        return;
    }

    tableContainer.find("#hiddeninputs").append(
        $("<input>")
            .attr("type", "hidden")
            .attr("name", tableIdName + "[]")
            .attr("class", "cbhidden")
            .attr("id", "hcb" + id)
            .val(id)
    );

    tableContainer.find('.datatable-extrainputs').first().focus().select();

}


//-------- (SELECT fg) VÉGE ----------------

////////////////////////////////////////////////////
//---- ======= SELECTES ESEMÉNYEK ================
////////////////////////////////////////////////////
if (par.data("table-type") === 'select') {
    var selectedRowIn = [];
    var selectedRowOut = [];
    var selectrow;


    //hozzáad egy segédoszlopot, ami segítségével tudjuk az újonnan hozzáadott elemet a sor elejére tenni, mivel
    //++ val szúrjuk be  és desc-el rendezzük ezt az oszlopot, így minfig a friss elem kerül legfelülre
    var columnAddNewRow = table.column(parseInt(par.data("header-count")) + 1);
    columnAddNewRow.visible(!columnAddNewRow.visible());
    var newRowColumn = 0;


    tableContainer.find("#datatable-toolbar").find(".bulkaction").addClass("disabled");


// (SELECT) a megadott selected sorokat, (amiket hiddenbe kiteszünk) létrehozza a táblázatban
    if (tableContainer.find(".cbhidden").length > 0) {
        tableContainer.find("#datatable-toolbar").find(".bulkaction").removeClass("disabled");
        tableContainer.find("#delete-selectedrow-all").prop("checked", true);
        tableContainer.find(".cbhidden").each(function () {
            var id = $(this).data("id");
            selectedRowOut.push(id);
        });
    }


    mergeInAnOutElement(selectedRowOut);


// ** (SELECT) ha az optionre kattintok, akkor hozzádja a sorhoz
    tableContainer.find("#selectrow").on("select2:select", function () {
        if ($(this).find("option:selected").val() == "") {
            return;
        }
        tableContainer.find("#delete-selectedrow-all").prop("checked", true);

        var attrDatas = [];

        var id;
        var data = $(this).select2('data')[0];
        id = data.id;
        for (var j = 0; j < parseInt(par.data("header-count")); j++) {
            attrDatas[j] = data.data[j];
        }


        if (par.data("is-select2-char") == "1") {
            //a select2 <li> ben átírjuk
            var optli = $('.' + par.data('table-id-name') + '-s2drop').find('#o' + id);
            if (optli.length > 0) {
                optli.text(optli.text().replace('~', '#'));
            }
        }
        addRowFromSelect(id, attrDatas);

        //azért kell, mert beelőzi azt, hogy a containerben legyen a text és nem cseréli ki, de
        // nem veszélyes ha beelőzi csak nem cseréli ki a karaktert
        setTimeout(function () {
            var rendered = tableContainer.find('#select2-selectrow-container');
            // var rclass = rendered.attr('class');
            rendered
                .text(rendered.text().replace('~', '#'))
                .attr('title', rendered.attr('title').replace('~', '#'));
            // .attr('class', '');
            //.attr('class', rclass + ' cuccli');
        }, 50);

        tableContainer.find("#datatable-toolbar").find(".bulkaction").removeClass("disabled");

    });


// ** (SELECT) HA AZ ELSŐ OSZLOPBAN LÉVŐ REMOVE-CHECKBOXRA RÁNYOMUNK TÖRÖLJÜK A SORT
    tableContainer.find('tbody').on("click", ".delete-selectedrow", function () {
        if (!confirm("Kijelölés törlése?")) {
            this.checked = true;
            return;
        }

        table
            .row($(this).parents("tr"))
            .remove()
            .draw();

        var id = $(this).data("id");
        tableContainer.find("#hcb" + id).remove();


        if (!(tableContainer.find('.delete-selectedrow').length > 0)) {
            tableContainer.find('#delete-selectedrow-all').prop('checked', false);
        }


        if (tableContainer.find(".cbhidden").length == 0) {
            tableContainer.find("#datatable-toolbar").find(".bulkaction").addClass("disabled");
        }

    });

    // ** (SELECT) ha az összes törlés CHECKBOXRA -er katt
    tableContainer.find("#" + tableIdName).find("thead").on("click", ".delete-selectedrow-all", function () {
        if (!confirm("Összes kijelölés törlése?")) {
            this.checked = true;
            return;
        }
        tableContainer.find(".cbhidden").remove();
        table
            .row()
            .remove()
            .draw();

        if (par.data("is-select2-char") == "1") {
            //a select2 <li> ben átírjuk
            $('.' + par.data('table-id-name') + '-s2drop').find('.select2-option-text').each(function () {
                $(this).text($(this).text().replace('#', '~'));
            });

        }

        tableContainer.find('#selectrow').val('').trigger('change');

        tableContainer.find("#datatable-toolbar").find(".bulkaction").addClass("disabled");

    });


    //A nagy select mező select2-sítése
    //TRÜKK VAN, NEM ZÁROM BE A SELECT2-T FOLYAMATOSAN NYITVA VAN, CSAK CSS-SEL VAN MEGOLDVA MINTHA BZÁRÓDNA, ÍGY
    //NEM BÉNÁZIK AZ AJAXSZAL
    var isUsed = false;
    var isOpen = false;
    selectrow = tableContainer.find("#selectrow").select2({
        dropdownAutoWidth: true,
        containerCssClass: par.data('table-id-name') + '-s2cont mobile-width100pc',
        dropdownCssClass: par.data('table-id-name') + '-s2drop',
        allowClear: false,
        language: 'hu',
        ajax: {
            url: par.data("datas"),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                    headerinfo: par.data("header-info") //TODO: szebben valahogy
                    //s2: 1
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * parseInt(par.data("select2-max-page"))) < data.total_count
                    }
                };
            },
            cache: true
        },
        templateResult: function (opt) {
            var data = jQuery.extend({}, opt['data']);
            var optimage = data['image'];
            var optrightside = data['rightside'];

            var image = (optimage ? '<img src="' + optimage + '" class="select-icon" /> ' : '');
            var rightside = (optrightside ? '<span class="select-rightside">' + optrightside + '</span>' : '');

            if (par.data("is-select2-char") == "1" && tableContainer.find("#hcb" + opt.id).length > 0) {
                opt.text = opt.text.replace('~', '#');
            }

            return $(image
                + ' <span id="o' + opt.id + '" data-id="' + opt.id + '" class="select2-option-text">'
                + opt.text + '</span> ' + rightside);
        },
        minimumInputLength: 0
    });


    selectrow.on("select2:closing", function (e) {
        //itt állítom meg, hogy ne záródjon be
        e.preventDefault();
        isUsed = true;
        var drop = $('.' + par.data('table-id-name') + '-s2drop');
        if (!(drop.is(':hidden'))) {
            isOpen = true;
            drop.hide();
            $('.' + par.data('table-id-name') + '-s2cont')
                .css('border-bottom-left-radius', '4px')
                .css('border-bottom-right-radius', '4px')
                .css('border-bottom-top-radius', '4px')
                .css('border-bottom-bottom-radius', '4px')
                .find('.select2-selection__arrow b')
                .css('border-color', 'rgb(136, 136, 136) transparent transparent transparent')
                .css('border-width', '5px 4px 0 4px');
        } else {
            isOpen = false;
        }
    });

    //ha niincsen "nyitva" tehát ha css-sel be van zárva a select2
    tableContainer.on('click', '.' + par.data('table-id-name') + '-s2cont', function () {
        if (isUsed && !isOpen) {
            $('.' + par.data('table-id-name') + '-s2drop').show();
            $(this)
                .css('border-bottom-left-radius', '0')
                .css('border-bottom-right-radius', '0')
                .find('.select2-selection__arrow b')
                .css('border-color', 'transparent transparent rgb(136, 136, 136) transparent')
                .css('border-width', '0px 4px 5px 4px');
        }
    });

    //Ennyi időt kell várni, hogy ne csússzon szét amíg Searching... -el,
    setTimeout(function () {
        selectrow.select2('open');
        selectrow.select2('close');
    }, 150);


}

////////////////////////////////////////////////
//----============ CHECKBOXOS ESEMÉNYEK MŰVELETEK  =======----------
/////////////////////////////////////////////////
if (par.data("table-type") === 'checkbox') {

    tableContainer.find("#datatable-toolbar").find(".bulkaction").addClass("disabled");

    tableContainer.find('.on-search').css('display', (parseGet(tableIdName + 'sub').length > 0 && parseGet(tableIdName + 'sub') != 0) ? 'inline-block' : 'none');


    //ne jelölje ki a sort, ha inputba kattintok
    $('.datatable-extrainputs').on('click', function (e) {
        e.stopPropagation();
    });


    //aktuális oldal kijelölése
    tableContainer.find("#check-page").on("click", function (e) {
        cover.css('display', 'block');
        e.stopPropagation();
        var tableIdName = $(this).closest('.datatableblock').data("idname");
        var that = this;

        tableContainer.find(".rowCheckbox").prop("checked", this.checked);
        if (that.checked == true) {
            tableContainer.find("#" + tableIdName).find("tbody").find("tr").addClass("selectedRow");
            tableContainer.find("#datatable-toolbar").find(".bulkaction").removeClass("disabled");
        } else {
            tableContainer.find("#" + tableIdName).find("tbody").find("tr").removeClass("selectedRow");
            tableContainer.find("#datatable-toolbar").find(".bulkaction").addClass("disabled");
        }

        tableContainer.find(".rowCheckbox").each(function () {
            var trcb = this;

            if (that.checked == true) {
                var hiddenInputs = tableContainer.find("#hiddeninputs");
                if (hiddenInputs.find("#hcb" + trcb.value).length == 0) {
                    hiddenInputs.append(
                        $("<input>")
                            .attr("type", "hidden")
                            .attr("name", $(trcb).data("nameforhidden"))
                            .attr("class", "cbhidden")
                            .attr("id", "hcb" + trcb.value)
                            .val(trcb.value)
                    );
                }
            } else {
                tableContainer.find("#hcb" + trcb.value).remove();
            }
        });

        if (tableContainer.find(".cbhidden").length > 0) {
            tableContainer.find("#datatable-toolbar").find(".bulkaction").removeClass("disabled");
        }

        selectedItemNr = tableContainer.find(".cbhidden").length;
        tableContainer.find("#selected-nr").text(selectedItemNr);
        cover.css('display', 'none');

    });


// ** (CHECKBOX) Összes kijelölése check-all ra pebipáltatjuk és beszinezzük az összes checkboxot
    tableContainer.find("#check-all").on("click", function (e) {

        //az összes hiden elemet töröljük:
        tableContainer.find(".cbhidden").remove();
        tableContainer.find(".rowCheckbox").prop("checked", this.checked);
        tableContainer.find("#check-page").prop("checked", this.checked);

        if (this.checked == true) {
            tableContainer.find("#" + tableIdName).find("tbody").find("tr").addClass("selectedRow");
            tableContainer.find(".rowCheckbox").prop("disabled", "disabled");
            tableContainer.find("#check-page").prop("disabled", "disabled");
            tableContainer.find("#datatable-toolbar").find(".bulkaction").removeClass("disabled");
            tableContainer.find(".check-page-btn").attr("disabled", "disabled");
            selectedItemNr = table.page.info().recordsDisplay;
            tableContainer.find("#selected-nr").text(selectedItemNr);
        } else {
            tableContainer.find("#" + tableIdName).find("tbody").find("tr").removeClass("selectedRow");
            tableContainer.find(".rowCheckbox").removeAttr("disabled");
            tableContainer.find("#check-page").removeAttr("disabled");
            tableContainer.find("#datatable-toolbar").find(".bulkaction").addClass("disabled");
            tableContainer.find(".check-page-btn").removeAttr("disabled");
            selectedItemNr = 0;
            tableContainer.find("#selected-nr").text(selectedItemNr);
        }
        e.stopPropagation(); //hogy ne kavarjon be hogy egy gombon belül van leiratkozunk ha a gombra katt ne csinálja
    });

    //ha be van pipálva az összes kijelölés akkor a oldal kijelölése is legyen
    tableContainer.find(".check-all-btn").on("click", function () {
        tableContainer.find("#check-all").trigger("click");
    });

    tableContainer.find(".check-page-btn").on("click", function () {
        tableContainer.find("#check-page").trigger("click");
    });


// ** (CHECKBOX)  Ha az egyikr kattintasz akkor hozza létre/törölje a hozzá tartozó hidden inputot a sor ID-jével + szinezze a sort stb
    tableContainer.on('click', '.rowCheckbox', function (e) {
        e.stopPropagation(); //hogy ha a sorra kattint akkor ne vegye duplán, hiszen triggerel

        tableContainer.find("#datatable-toolbar").find(".bulkaction").addClass("disabled");
        if ((this.checked)) {
            $(this).closest("tr").addClass("selectedRow");
            if (tableContainer.find("#hiddeninputs").find("#hcb" + this.value).length == 0) {
                tableContainer.find("#hiddeninputs").append(
                    $("<input>")
                        .attr("type", "hidden")
                        .attr("name", $(this).data("nameforhidden"))
                        .attr("class", "cbhidden")
                        .attr("id", "hcb" + this.value)
                        .val(this.value)
                );
            }
        } else {
            $(this).closest("tr").removeClass("selectedRow");
            tableContainer.find("#hcb" + this.value).remove();
        }

        if (tableContainer.find(".cbhidden").length > 0) {
            tableContainer.find("#datatable-toolbar").find(".bulkaction").removeClass("disabled");
        }


        var isAll = true;

        if (tableContainer.find(".rowCheckbox").length > 0) {
            tableContainer.find(".rowCheckbox").each(function () {
                if ($(this).prop("checked") == false) {
                    isAll = false;
                    return false;
                }
            });
        } else {
            isAll = false;
        }

        tableContainer.find("#check-page").prop("checked", isAll);

        selectedItemNr = tableContainer.find(".cbhidden").length;
        tableContainer.find("#selected-nr").text(selectedItemNr);

    });

    tableContainer.on('click', '.extrainput', function (e) {
        e.stopPropagation(); //hogy ne jelölje be a sort és a checkboxot
    });


    tableContainer.on('click', 'tr[role="row"]', function () {
        $(this).find('.rowCheckbox').trigger('click');
    });


// ** (CHECKBOX) Oszlop keresés actiok
//  kell ENTER, amúgy elég ha blur van (lemegy a fókusz)

    tableContainer.find("thead").find(".datatable-search").on("blur", function () {
        var that = this;
        if (that.value.length < 2 && that.value.length > 0) return true; //2 karaktertől kezdjen keresni,de az üreset is küldje el
        var y;
        var x = Number($(that).data("index"));
        y = 0;

        var cindex = x + y;

        if (table.column(cindex).search() !== that.value) {

            tableContainer.find(".cbhidden").remove();
            tableContainer.find("#selected-nr").text(0);

            table.column(cindex)
                .search(that.value)
                .draw();

            if (tableContainer.find("#check-all:checked").length > 0) {
                tableContainer.find("#check-all:checked").trigger("click");
            }
            if (tableContainer.find("#check-page:checked").length > 0) {
                tableContainer.find("#check-page:checked").trigger("click");
            }

        }

    });
    tableContainer.find("thead").find(".datatable-search").on("keypress", function (e) {
        //ha entert nyomunk akkor szüntesse meg a focust, és keressen,  de ne küldje el a formot
        if (e.which == 13) {
            $(this).trigger("blur");
            e.preventDefault();
            return false;
        }
    });


// ** (CHECKBOX) a check all gombok elhelyezése
    tableContainer.on("DOMNodeInserted load", ".dataTables_wrapper", function () {

        $(tableContainer).off("DOMNodeInserted", ".dataTables_wrapper");

        var checkAllDesti = tableContainer.find('.dataTables_wrapper').find('.row').find('div').first();
        var nextoPager = checkAllDesti.find('.dataTables_length');
        if (nextoPager.length > 0) {
            checkAllDesti = nextoPager;
        }
        tableContainer.find("#checkes").appendTo(checkAllDesti).css('display', 'inline-block');
    });

    //keresésnél az operátor megadása
    $('.filter_op').change(function () {
        op = $(this).val();
        opId = this.id;
        valId = opId.replace('filter-op-', '');
        sep = op.length ? ( op == '(-)' ? ' |           ꟷ ' : ' | ') : '';
        $('#columns-value-' + valId).val(op + sep);

        if(op == 'empty' || op == 'not empty') {
            $('#columns-value-' + valId).trigger("blur");
        }
    });

//jobbra balra keyre lapozzon a datatable, és a kijelölésre is legen gyors gomb
    $(document).keydown(function (e) {
        var tag = e.target.tagName.toLowerCase();
        if (tag != 'input' && tag != 'textarea' && cover.is(':hidden')
        ) {
            switch (e.which) {
                case 37: // left
                    $('#' + tableIdName + '_previous').trigger('click');
                    break;
                case 39: // right
                    $('#' + tableIdName + '_next').trigger('click');
                    break;
                case 36: // home
                    $('#' + tableIdName + '_first').trigger('click');
                    break;
                case 35: // end
                    $('#' + tableIdName + '_last').trigger('click');
                    break;
                default:
                    //SHIFT+A
                    if (e.keyCode == 65 && e.shiftKey) {
                        tableContainer.find('#check-all').trigger('click');
                    }
                    //SHIFT+S
                    if (e.keyCode == 83 && e.shiftKey) {
                        tableContainer.find('#check-page').trigger('click');
                    }

                    return; // exit this handler for other keys
            }
            e.preventDefault();
        }
    });
}

//modal,ha van saját formja a táblázatnak
if (tableContainer.find('form').length > 0) {
    $("#Modal-" + tableIdName).on("show.bs.modal", function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var modal = this;
        var title = button.data("title");
        var warningText = button.data("warning-text");
        var urlaction = button.data("url");
        var form = tableContainer.find('form');
        var okButtonLabel = button.data("ok-button-label");
        var cancelButtonLabel = button.data("cancel-button-label");
        var ok = tableContainer.find('#ok-button');
        var cancel = tableContainer.find('#cancel-button');

        var selectedElemNr = tableContainer.find('.cbhidden').length;
        if (tableContainer.find("#check-all:checked").length > 0) {
            selectedElemNr = tableContainer.find("#selected-nr").text();
        }

        $("#modalTitle").text(title);
        $("#dialogText").text(warningText);

        if (button.data("count-elem").length > 0 && selectedElemNr > 0) {
            $("#selected-elem-nr").text(button.data("count-elem") + selectedElemNr);
        } else {
            $("#selected-elem-nr").text('');
        }

        if (okButtonLabel.length > 0) {
            ok.text(okButtonLabel);
        } else {
            ok.text('Ok');
        }

        if (cancelButtonLabel.length > 0) {
            cancel.text(cancelButtonLabel);
        } else {
            cancel.text('Mégse');
        }

        form.attr("action", urlaction);

        ok.focus();

        ok.on('click', function () {
            $('.modal').hide();
            $('.modal-content').hide();
            $('.modal-backdrop').hide();
            $('.modal').off('click');
            form.submit();
        });


        $(document).on("keypress", function (e) {
            if (!($(modal).is(":hidden")) && e.which == 13) {
                ok.trigger('click');
            }
        });

    });
}



