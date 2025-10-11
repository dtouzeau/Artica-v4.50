function exportTo(format,grid){
var tableDivHeader = $(grid).find('.hDiv');
var tableHeader = $(tableDivHeader).find('table');;
var header = Array();
$(tableHeader).find('th').each(function(i, v){
        header[i] = $(this).text();
})
var tableDiv = $(grid).find('.bDiv');
var tableData = $(tableDiv).find('table');
var convertTableData = $(tableData).tableToJSON({headings:header});
var stringTableData = JSON.stringify(convertTableData);
console.log(stringTableData)

var param = { 'filters' : stringTableData, 'format': format };
 OpenWindowWithPost('exec.exportTO.php', 'width=500, height=500, left=100, top=100, resizable=yes, scrollbars=yes', 'NewFile', param);
}

function OpenWindowWithPost(url, windowoption, name, params) {
 var form = document.createElement('form');
 form.setAttribute('method', 'post');
 form.setAttribute('action', url);
 form.setAttribute('target', name);
 for (var i in params)
 {
   if (params.hasOwnProperty(i))
   {
     var input = document.createElement('input');
     input.type = 'hidden';
     input.name = i;
     input.value = params[i];
     form.appendChild(input);
   }
 }
 document.body.appendChild(form);
 window.open('post.htm', name, windowoption);
 form.submit();
 document.body.removeChild(form);
}

