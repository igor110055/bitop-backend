    if ($('.flot-line')[0]) {
        $('.flot-line').bind('plothover', function (event, pos, item) {
            if (item) {
                var x = item.datapoint[0],
                    y = item.datapoint[1];
                $('.flot-tooltip').html(item.series.label + ', ' + ticks[x][1] + ' = ' + y).css({top: item.pageY+5, left: item.pageX+5}).show();
            }
            else {
                $('.flot-tooltip').hide();
            }
        });

        $('<div class="flot-tooltip"></div>').appendTo('body');
    }