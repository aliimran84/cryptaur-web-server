function conversionHeightLineBottom(tree) {

    var ul = tree.find('ul.second-level');
    var li = ul.children('li.'+ul.attr('class'));
    var positionTopFirstLi = $(li[0]).offset().top;
    var positionTopLastLi = $(li[li.length-1]).offset().top;
    ul.parent().prev().find('.line-bottom').css('height',positionTopLastLi - positionTopFirstLi + 84);
    var ulParticipants = ul.find('ul.participants');
    for (var i = 0; i < ulParticipants.length; i++) {
        li = $(ulParticipants[i]).children('li.'+$(ulParticipants[i]).attr('class').split(' ')[0]);
        positionTopFirstLi = $(li[0]).offset().top;
        positionTopLastLi = $(li[li.length-1]).offset().top;
        $(ulParticipants[i]).parent().prev().find('.line-bottom').css('height',positionTopLastLi - positionTopFirstLi + 211);
    }
}
$(document).ready(function(){
    $('ul.tabs').tabs();

    $(".dropdown-button").dropdown();
    $('select').material_select();
    $('.collapsible').collapsible();
    $(".button-collapse").sideNav();
    var heightLineBottom;
    if ($('.referral-progam ul'))
        $('.referral-progam .line-bottom').css('height',$('.referral-progam ul').height());
    var trees = $('.main-panel-block.tree');
    for (var n = 0; n < trees.length; n++) {
        var secondLevel = $(trees[n]).find('li.second-level');
        heightLineBottom = 85;
        var marginTop = 20;
        for (var i = 0; i < secondLevel.length - 1; i++) {
            heightLineBottom += secondLevel[i].clientHeight + marginTop;
        }
        $('li.first-level>.line-bottom').css('height', heightLineBottom);

        $(trees[n]).find('.participants-block i').click(function () {
            var element = $(this).closest('.participants').next().children();
            if ($(element).hasClass('close')) {
                $($(this).closest('.participants').find('.line-bottom')[0]).css('display', 'block');
                $(element).removeClass('close');
            } else {
                $($(this).closest('.participants').find('.line-bottom')[0]).css('display', 'none');
                $(element).addClass('close');
            }
            var treeBlock = $('.main-panel-block.tree');
            treeBlock.closest('.main-panel').css('height', treeBlock.closest('.my-group').height());
            conversionHeightLineBottom($(this).closest('.tree'));
        });
    }
    $('#after-compression').change(function () {
        var trees = $('.main-panel-block.tree');
        trees.each(function (i, tree) {
            if($(tree).hasClass('active')) {
                $(tree).removeClass('active');
                trees.closest('.main-panel').css('height', trees.closest('.my-group').height());
            } else {
                $(tree).addClass('active');
                trees.closest('.main-panel').css('height', trees.closest('.my-group').height());
            }
        });
    });
});

function catalogItemCounter(field){

    var fieldCount = function(el) {
        var min = el.attr('min') || false,
            max = el.attr('max') || false,
            step = Number(el.attr('step')) || 1,
            btnUp = el.parent().find('span.btn-up'),
            btnDown = el.parent().find('span.btn-down');

        function init(el) {
            if(!el.attr('disabled')){
                btnDown.on('click', decrement);
                btnUp.on('click', increment);
            }

            function decrement() {
                var value = Number(el[0].value);
                value = (value - step).toFixed(8);
                if(!min || value >= min)
                    el[0].value = value;
            };

            function increment() {
                var value = Number(el[0].value);
                value = (value + step).toFixed(8);
                if(!max || value <= max)
                    el[0].value = value;
            };
        }
        el.each(function() {
            init($(this));
        });
    };
    $(field).each(function(){
        fieldCount($(this));
    });
}

catalogItemCounter('.wallet_view-new_contribution-input_amount');

var inputRange = $('.range-field input[type=range]');
var reinvest = $('form.reinvest-form input.reinvest'),
    reinvestVal = reinvest.val(),
    withdraw = $('form.reinvest-form input.withdraw'),
    inputRangeLabel = $('.range-field-percents');
$(inputRange).on("input change", function() {
    var percents = $(this).val(),
        tmpReinvest = (reinvestVal * (percents/100)).toFixed(8),
        tmpWithdraw = (reinvestVal - tmpReinvest).toFixed(8);

    inputRangeLabel.text(percents + "%");
    reinvest.val(tmpReinvest);
    withdraw.val(tmpWithdraw);

});