

$(document).ready(function(){
  showHideNoUploadsMessage();
  initSortable();

  var fieldName = $('#fieldName').val();
  self.parent.resizeIframe(fieldName + '_iframe');
});

//
function initSortable() {
  var container   = $('#sortableRows');
  var containerEl = container.get(0);

  container.Sortable({
    accept      : 'sortableRow',
    helperclass : 'sortHelper',
    axis        : 'vertically',
    containment : [containerEl.offsetLeft, containerEl.offsetTop, containerEl.offsetWidth, containerEl.offsetHeight],  // left, top, width, height
    handle      : '.dragHandle',
    opacity     : 0.5,
    onChange    : function(serialized) {
      updateRowBackgroundColors();

      // update hidden field with field order
      var orderedRowIds = serialized[0]['o']['sortableRows'];

      // save changes via ajax
      $.ajax({
        url: '?',
        type: "POST",
        data: {
          menu:            $('#tableName').val(),
          action:          'uploadListReOrder',
          fieldName:       $('#fieldName').val(),
          num:             $('#num').val(),
          preSaveTempId:   $('#preSaveTempId').val(),
          newOrder:        serialized[0]['o']['sortableRows'] + "" // force array to string
        },
        error:  function(XMLHttpRequest, textStatus, errorThrown){
          alert("There was an error sending the request! (" +XMLHttpRequest['status']+" "+XMLHttpRequest['statusText'] +")");
        },
        success: function(msg){ if (msg) { alert("Error: " + msg); }}
      });
    } // end onChange
  });
}

//
function updateRowBackgroundColors() { // NOTE: This function is different from editTable_functions.js
  $(".sortableRow table").removeClass('oddRow evenRow');
  $(".sortableRow:odd table").addClass('evenRow');
  $(".sortableRow:even table").addClass('oddRow');
}

//
function showHideNoUploadsMessage() {

  if ($(".sortableRow").size() == 0) { $(".noUploads").show(); }
  else                               { $(".noUploads").hide(); }

}

function removeUpload(uploadNum, filename, rowChildEl) {

  // confirm erase
  var message = lang_confirm_erase_image.replace("%s", filename);
  if (!confirm(message)) { return false; }

  // erase record
  var eraseUrl = "?menu=" + $('#tableName').val()
               + "&action=uploadErase"
               + "&fieldName=" + $('#fieldName').val()
               + "&uploadNum=" + uploadNum
               + "&num=" + $('#num').val()
               + "&preSaveTempId=" + $('#preSaveTempId').val()

  $.ajax({
    url: eraseUrl,
    error:  function(msg){ alert("There was an error sending the request!"); },
    success: function(msg){
      if (msg) { return alert("Error: " + msg); };

      // remove field html
      $(rowChildEl).parents(".sortableRow").slideUp('normal', function() {  // slideUp works on Divs but not tables
        $(rowChildEl).parents(".sortableRow").remove();
        updateRowBackgroundColors();
        showHideNoUploadsMessage();

        // resize parent iframe
        var iframeName = $('#idTemp').val() + '_iframe';
        document.body.style.height = "auto";
        self.parent.resizeIframe(iframeName);
      });
    }
  });

  return false;
}


//
function modifyUpload(uploadNum, filename, rowChildEl) {

  var modifyUrl = "?menu=" + $('#tableName').val()
                + "&action=uploadModify"
                + "&fieldName=" + $('#fieldName').val()
                + "&infoLabels=" + JSON.stringify(infoLabels)
                + "&num=" + $('#num').val()
                + "&preSaveTempId=" + $('#preSaveTempId').val()
                + "&uploadNums=" + uploadNum
                + "&TB_iframe=true&width=700&height=350&modal=true";
                

  // show thickbox
  var caption    = null;
  var imageGroup = false;
  self.parent.tb_show(caption, modifyUrl, imageGroup);

}

//
function modifyTranslate(uploadNum, filename, rowChildEl) {

  var modifyUrl = "?menu=" + $('#tableName').val()
                + "&action=translateModify"
                + "&fieldName=" + $('#fieldName').val()
                + "&type=upload"
                + "&num=" + $('#num').val()
                + "&uploadNum=" + uploadNum                
                + "&preSaveTempId=" + $('#preSaveTempId').val()
                + "&TB_iframe=true&width=700&height=350&modal=true";
  
  // show thickbox
  var caption    = null;
  var imageGroup = false;
  self.parent.tb_show(caption, modifyUrl, imageGroup);

}
