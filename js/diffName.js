$('.row_result').mouseover(function() {

  var input_name = $(this).find("span[data]").attr('data');
  var matched = $(this).find("td")[3].innerHTML;
  var diff = JsDiff.diffChars(input_name, matched);
  var diff_matched = document.createElement('div');
  diff_matched.setAttribute("class", "name_diff");

//*
  diff.forEach(function(part){
    // green for additions, red for deletions
    // grey for common parts
    var color = part.added ? 'green' :
                part.removed ? 'red' : 'grey';
    var span = document.createElement('span');
    span.style.color = color;
    span.appendChild(document.createTextNode(part.value));
    diff_matched.appendChild(span);
  });
//*/
//
  if ($(this).find('.name_diff').length == 0) {
    $(this).find('td')[3].appendChild(diff_matched);
  }
  var ffss = 5566;
});
