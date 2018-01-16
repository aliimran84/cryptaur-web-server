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
    var optionSelected = $('select.select-wallet :selected');
    $('.modal').modal({
        complete: function() {
            if ($('#warning_1').prop('checked') && $('#warning_1').prop('checked') && $('#warning_1').prop('checked')) {
                $('.block_external-wallet').css('display', 'block');
            } else {
                if (this.$el.closest('.settings-block').length) {
                    $('select.select-wallet').material_select();
                    $('.select-wallet .select-dropdown').val(optionSelected.text());
                    if (optionSelected.val() == 'inner-wallet') {
                        $('.block_inner-wallet').css('display', 'block');
                        $('.block_external-wallet').css('display', 'none');
                    } else if (optionSelected.val() == 'external-wallet') {
                        $('.block_inner-wallet').css('display', 'none');
                        $('.block_external-wallet').css('display', 'block');
                    }
                } else {
                    var optionDisabled = $('select.select-wallet option[value="choose"]');
                    $('select.select-wallet').material_select();
                    $('.select-wallet .select-dropdown').val(optionDisabled.text());
                }
            }
        }
    });
    $('ul.tabs').tabs();

    if ($('select.select-wallet').val() == 'inner-wallet') {
        $('.block_external-wallet').css('display', 'none');
    } else if ($('select.select-wallet').val() == 'external-wallet') {
        $('.block_inner-wallet').css('display', 'none');
    }

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

    $('.warning').change(function () {
        var checked = false;
        $('.warning').each(function (i, el) {
            checked += $(el).prop('checked');
        });
        if (checked == $('.warning').length)
            $('#modal_external-wallet').modal('close');
    });

    $('select.select-wallet').change(function () {
        var value = $(this).val();
        if (value == 'inner-wallet') {
            $('.block_inner-wallet').css('display', 'block');
            $('.block_external-wallet').css('display', 'none');
        } else if (value == 'external-wallet') {
            $('.block_inner-wallet').css('display', 'none');
            $('#modal_external-wallet').modal('open');
        }
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
    inputRangeLabel = $('form.reinvest-form input.percents');
$(inputRange).on("input change", function() {
    var percents = $(this).val(),
        tmpReinvest = (reinvestVal * (percents/100)).toFixed(8),
        tmpWithdraw = (reinvestVal - tmpReinvest).toFixed(8);

    inputRangeLabel.val(percents);
    reinvest.val(tmpReinvest);
    withdraw.val(tmpWithdraw);

});

window.intercomSettings = {
    app_id: "igb92dwc"
};
(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/igb92dwc';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})()