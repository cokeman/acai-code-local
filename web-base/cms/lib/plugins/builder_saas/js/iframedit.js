
var actualizaCampos = () => {
    $(".textarea-codigo").each(function(){
        $(this).val(Base64.encode($(this).val()));

    });

    for(var instanceName in CKEDITOR.instances) {
        var datos = CKEDITOR.instances[instanceName].getData();
        var nombre = instanceName;
        $("textarea[name="+nombre+"]").val(datos);

    }

    $(".textarea-editor").each(function(){
        var datos = $(this).parent().find("iframe").contents().find('.wysihtml5-editor').html();
        $("textarea[name="+$(this).attr("for")+"]").val(datos);

    });
    return true;
}
function guardar(){
    actualizaCampos();
    $("FORM").ajaxSubmit(function(){
        $(".textarea-codigo").each(function(){
            $(this).val(Base64.decode($(this).val()));

        });
        parent.toggleEditModuleModal(true);
    });

}