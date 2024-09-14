/**
 * Created by stellar on 2017.05.02..
 */

jQuery.extend(jQuery.fn.dataTableExt.oSort, {


    "localecompare-asc": function (a, b) {
        if (!isNaN(parseFloat(a)) && !isNaN(parseFloat(b))) {
            return ((parseFloat(a) < parseFloat(b)) ? -1 : ((parseFloat(a) > parseFloat(b)) ? 1 : 0));
        }

        a = a.replace(/(<([^>]+)>)/ig, "");
        b = b.replace(/(<([^>]+)>)/ig, "");
        return a.localeCompare(b);
    },
    "localecompare-desc": function (a, b) {

        if (!isNaN(parseInt(a.substr(0, 1))) && !isNaN(parseInt(b.substr(0, 1)))) {
            return ((parseFloat(a) > parseFloat(b)) ? -1 : ((parseFloat(a) < parseFloat(b)) ? 1 : 0));
        }

        a = a.replace(/(<([^>]+)>)/ig, "");
        b = b.replace(/(<([^>]+)>)/ig, "");
        return b.localeCompare(a);
    }
});


jQuery.fn.DataTable.ext.type.search.localecompare = function (data) {
    return !data ? '' : data.replace(/(<([^>]+)>)/ig, "");
};
