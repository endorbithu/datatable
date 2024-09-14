/**
 * Created by stellar on 2016.09.21..
 */


$.fn.select2.defaults.set("minimumResultsForSearch", "20"); //hány elemtől legyen keresés mező
$.fn.select2.defaults.set("maximumInputLength", "20");
$.fn.select2.defaults.set("dropdownAutoWidth", true);
$.fn.select2.defaults.set("placeholder", "...");


//ha szeretnénk, hogy legyen üres option az elején akkor jelezzük a classban nullable-vel és rak nekünk resetelő X-et hozzá
$('.select2').each(function () {
    if ($(this).hasClass('nullable')) {
        $(this).select2({"allowClear": true});
    } else {
        $(this).select2();
    }
});

//szutyvákolás, mert a select2-ben nem lehet megadni classt a ciontainernek csak szélességet, ha explicit megadok
//classt a containerCssClass optionben azt is a container gyermekének a select2-selectionba szúrja be és azzal nem sokra megyünk
$(function () {
    $('.select2-container').each(function () {
        adjustSelectContainer(this);
    });

});


function adjustSelectContainer(selectCont) {
    var selection = $(selectCont).find('.select2-selection');
    $(selectCont).css('width', 'auto');

    $(selectCont).attr('class', $(selectCont).attr('class') + ' ' + selection.attr('class'));
    $(selectCont).removeClass('select2-selection');
    $(selectCont).removeClass('select2-selection--single');
    $(selectCont).removeClass('select2-selection--multiple');
    $(selectCont).removeClass('form-control');

    selection.attr('class', selection.attr('class') + ' width100pc');
    selection.css('width', '100%');
}








