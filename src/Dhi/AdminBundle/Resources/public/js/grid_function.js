    function deleterecord(id,headTitle,msg) {
    
    $.confirm({
        title: headTitle,
        content: msg,
        icon: '',
        confirmButton: 'Okay',
        cancelButton: 'Cancel',
        confirmButtonClass: 'btn-info',
        cancelButtonClass: 'btn-danger',
        theme: 'white',
        animation: 'scale',
        animationSpeed: 400,
        animationBounce: 1.5,
        keyboardEnabled: false,
        container: 'body',
        confirm: function() {
            $.ajax({
            type: "POST",
            url: deleteAjaxSource,
            data: "id=" + id,
            success: function(data) {
            //    alert(data.message);
                dTable.fnDraw(true);
                deleteMessage(data);
                $('#checkall').prop('checked', false);

            }
        });
            
        },
        cancel: function() {
        },
        contentLoaded: function() {
        },
        backgroundDismiss: false,
        autoClose: false,
        closeIcon: true,
    });

   /*
    if (confirm("Are you sure you want to delete?")) {

        $.ajax({
            type: "POST",
            url: deleteAjaxSource,
            data: "id=" + id,
            success: function(data) {

                dTable.fnDraw(true);
                deleteMessage(data);
                $('#checkall').prop('checked', false);

            }
        });
    } */
}


function activeInactiverecord(id,val,ajaxActionUrl,headTitle,msg) {
	
	$.confirm({
        title: headTitle,
        content: msg,
        icon: '',
        confirmButton: 'Okay',
        cancelButton: 'Cancel',
        confirmButtonClass: 'btn-info',
        cancelButtonClass: 'btn-danger',
        theme: 'white',
        animation: 'scale',
        animationSpeed: 400,
        animationBounce: 1.5,
        keyboardEnabled: false,
        container: 'body',
        confirm: function() {
            $.ajax({
            type: "POST",
            url: ajaxActionUrl,
            data: "id=" + id+"&status="+val,
            success: function(data) {

                dTable.fnDraw(true);
                deleteMessage(data);
                $('#checkall').prop('checked', false);

            }
        });
            
        },
        cancel: function() {
        },
        contentLoaded: function() {
        },
        backgroundDismiss: false,
        autoClose: false,
        closeIcon: true,
    });
}

function deleteMessage(data) {

    var delmsg = '<div class="alert alert-' + data.type + '">';
    delmsg += '<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>';
    delmsg += data.message;
    delmsg += '</div>';
    $("div.alert-" + data.type).remove();
    $("div.delBoxCont").prepend(delmsg);
}