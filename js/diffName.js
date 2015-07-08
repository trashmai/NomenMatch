$('.row_result').mouseenter(function() {

  if ($(this).find('.name_diff').length == 0) {
    var input_name = $(this).find("span[name_cleaned]").attr('name_cleaned');
    var td_matched = $(this).find('td')[3];
    var matched = td_matched.innerHTML;
    var diff = JsDiff.diffChars(input_name, matched);
    var diff_matched = document.createElement('div');
    diff_matched.setAttribute("class", "name_diff");

//*
    diff.forEach(function(part){
    // green for additions, red for deletions
    // grey for common parts
      var color = part.added ? 'blue' :
                  part.removed ? 'red' : 'grey';
      var span = document.createElement('span');
      span.style.color = color;
      span.appendChild(document.createTextNode(part.value));
      diff_matched.appendChild(span);
    });
//*/

    td_matched.appendChild(diff_matched);
  }
});
