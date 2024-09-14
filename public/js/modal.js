/**
 * Created by stellar on 2017.04.18..
 */

$('body').append('<div class="modal" data-keyboard="true" tabindex="-1" id="Modal"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"><h4 id="modalTitle" class="modal-title"></h4><p class="text-center font-normal-regular" id="dialogText"></p><span id="selected-elem-nr" class="selected-elem-nr text-center"></span></div><div class="text-center modal-button"><button type="button" name="submit-delete" id="ok-button" class="btn btn-primary" value="">Ok</button><button type="button" class="btn btn-default" id="cancel-button" data-dismiss="modal">Mégse</button></div></div></div></div>');

$(".modal").on("show.bs.modal", function (event) {

    var button = $(event.relatedTarget); // Button that triggered the modal
    console.log(button);
    var modal = this;
    var title = button.data("title");
    var warningText = button.data("warning-text");
    var tableIdName = button.data("table-id-name");
    var urlaction = button.data("url");
    var container = $('#' + tableIdName + '-datatableblock');
    var form = container.find('form');
    var okButtonLabel = button.data("ok-button-label");
    var cancelButtonLabel = button.data("cancel-button-label");
    var ok = $('#ok-button');
    var cancel = $('#cancel-button');

    var selectedElemNr = container.find('.cbhidden').length;
    if (container.find("#check-all:checked").length > 0) {
        selectedElemNr = container.find("#selected-nr").text();
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
        $('.modal-content').hide();
        $('.modal').off('click');
        form.submit();
    });


    $(document).on("keypress", function (e) {
        if (!($(modal).is(":hidden")) && e.which == 13) {
            ok.trigger('click');
        }
    });

});
