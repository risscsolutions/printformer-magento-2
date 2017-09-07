/**
 * Created by fabian on 07.09.17.
 */

require(
    [
'jquery',
'Magento_Ui/js/modal/modal'
],
    function($,modal) {

        //modal configuration
        var options = {
            type: 'popup',
            responsive: true,
            modalClass: 'printformer-editor-main-modal',
            innerScroll: false,
            title: 'Draft Editor',
            buttons: []
        };

        var popup = modal(options, $('#printformer-editor-main'));

        //create iFrame when the user is clicking on the editor button
        $("#openModal").on("click",function() {
            var url = $(this).attr("data-url");
            var i = document.createElement("iframe");
            i.src = url;
            i.id = "iframe";
            i.scrolling = "auto";
            i.frameborder = "0";
            i.width = "100%";
            i.height = "100%";
            document.getElementById("printformer-editor-main").appendChild(i);

            $('#printformer-editor-main').modal('openModal');
        });


        //remove iframe on X click event
        $('.action-close').click(function(){
            //remove iframe on close
            var iframe = document.getElementById("iframe");
            iframe.parentNode.removeChild(iframe);
        });

        //remove iframe on esc klick event
        $(document).keyup(function(e) {
            if (e.keyCode == 27) {
                //remove iframe on close
                var iframe = document.getElementById("iframe");
                iframe.parentNode.removeChild(iframe);
            }
        });
    }
);