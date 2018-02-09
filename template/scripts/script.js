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
        var ua = navigator.userAgent;
        if (ua.search(/Firefox/) > 0)
            $(ulParticipants[i]).parent().prev().find('.line-bottom').css('height',positionTopLastLi - positionTopFirstLi + $(li[li.length-1]).height() + 86);
        else
            $(ulParticipants[i]).parent().prev().find('.line-bottom').css('height',positionTopLastLi - positionTopFirstLi + $(li[li.length-1]).height() + 85);
    }
}
$(document).ready(function(){
    var warningCheckBox = $('.warning-checkbox');
    var checked;
    var contract = '0x6f3a995e904c9be5279e375e79f3c30105efa618'.toUpperCase();
    var optionSelected = $('select.select-wallet :selected');
    $('.modal').modal({
        complete: function() {
            if ($('#warning_1').prop('checked') && $('#warning_2').prop('checked') && $('#warning_3').prop('checked')) {
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
            var ulParticipants = $('ul.third-level[data-level='+ level +']');
            var referrals = json['referrals'];
            $.each(referrals, function (index, element) {
                appendTreeBlock(element, ulParticipants);
            });

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

    warningCheckBox.change(function () {
        if (checkWarningCheckBox(warningCheckBox))
            $('#modal_external-wallet').modal('close');
    });

    $('select.select-wallet').change(function () {
        var value = $(this).val();
        if (value == 'inner-wallet') {
            $('.block_inner-wallet').css('display', 'block');
            $('.block_external-wallet').css('display', 'none');
        } else if (value == 'external-wallet') {
            $('.block_inner-wallet').css('display', 'none');
            checked = false;
            warningCheckBox.each(function (i, el) {
                checked += $(el).prop('checked');
            });
            if (checked != warningCheckBox.length)
                $('#modal_external-wallet').modal('open');
            else
                $('.block_external-wallet').css('display', 'block');
        }
    });

    $('#modal_cryptauretherwallet-info').modal({
            dismissible: false,
            inDuration: 300,
            outDuration: 200
        }
    ).modal('open');

    $("#2fa_method").change(function(){
        var value = $(this).val();
        if(value == 'SMS' || value == 'SMS&EMAIL')
            $("#phone_row").show();
        else
            $("#phone_row").hide();
    });

    function cryptaur_ether_wallet_checkAmount() {
        var sendBtn = $('#cryptaur_ether_wallet_send'),
            select = $("select.select-token"),
            value = select.val(),
            minimalAmountCPT = $('#warning-minimum-amount'),
            transactionFree = $('#cryptaur_ether_wallet_transaction_fee');
        if (value === 'ETH') {
            transactionFree.css('opacity', 1);
            var amount = parseFloat($('#cryptaur_ether_wallet_amount_to_send').val()) || 0;
            if (amount > parseFloat($('#cryptaur_ether_wallet_maximum_amount').html())) {
                transactionFree.css('color', 'red');
                sendBtn.attr('disabled', true);
            } else {
                transactionFree.css('color', '');
                sendBtn.attr('disabled', false);
            }
            $('#warning-wallet').css('display', 'none');
            minimalAmountCPT.css('display', 'none');
        } else if (value === 'CPT') {
            $('#warning-wallet').css('display', 'block');
            sendBtn.attr('disabled', true);
            transactionFree.css('opacity', 0);
            warningCheckBox.prop('checked', false);
            warningCheckBox.change(function () {
                if (checkWarningCheckBox(warningCheckBox)) {
                    if ($('input.address').val().toUpperCase() === contract && parseFloat($('#cryptaur_ether_wallet_amount_to_send').val()) < 5000) {
                        minimalAmountCPT.css('display', 'block');
                    } else {
                        minimalAmountCPT.css('display', 'none');
                        sendBtn.attr('disabled', false);
                    }
                }
            });
            if ($('input.address').val().toUpperCase() === contract && parseFloat($('#cryptaur_ether_wallet_amount_to_send').val()) < 5000) {
                sendBtn.attr('disabled', true);
                minimalAmountCPT.css('display', 'block');
            } else {
                minimalAmountCPT.css('display', 'none');
            }
        } else {
            minimalAmountCPT.css('display', 'none');
            $('#warning-wallet').css('display', 'none');
            sendBtn.attr('disabled', false);
            transactionFree.css('opacity', 0);
        }
    }

    $("#cryptaur_ether_wallet_amount_to_send").keyup(function () {
        cryptaur_ether_wallet_checkAmount();
    });

    $("select.select-token").change(function () {
        cryptaur_ether_wallet_checkAmount();
    });
});

function checkWarningCheckBox (warningCheckBox) {
    var checked = false;
    warningCheckBox.each(function (i, el) {
        checked += $(el).prop('checked');
    });
    if (checked == warningCheckBox.length)
        return true;
    else
        return false;
}

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

var json = {
    "firstName": "Test",
    "secondName": "Testovich 3",
    "id": 3,
    "total_CPT": 10,
    "referrals": [
        {
            "firstName": "Test",
            "secondName": "Testovich 4",
            "id": 4,
            "total_CPT": 10,
            "referrals": [
                {
                    "firstName": "Test",
                    "secondName": "Testovich 12",
                    "id": 12,
                    "total_CPT": 10,
                    "referrals": []
                },
                {
                    "firstName": "Test",
                    "secondName": "Testovich 13",
                    "id": 13,
                    "total_CPT": 10,
                    "referrals": []
                }
            ]
        },
        {
            "firstName": "Test",
            "secondName": "Testovich 7",
            "id": 7,
            "total_CPT": 10,
            "referrals": []
        },
        {
            "firstName": "Test",
            "secondName": "Testovich 8",
            "id": 8,
            "total_CPT": 10,
            "referrals": []
        },
        {
            "firstName": "Test",
            "secondName": "Testovich 9",
            "id": 9,
            "total_CPT": 10,
            "referrals": [
                {
                    "firstName": "Test",
                    "secondName": "Testovich 20",
                    "id": 20,
                    "total_CPT": 12,
                    "referrals": []
                },
                {
                    "firstName": "Test",
                    "secondName": "Testovich 21",
                    "id": 21,
                    "total_CPT": 123,
                    "referrals": []
                },
                {
                    "firstName": "Test",
                    "secondName": "Testovich 22",
                    "id": 22,
                    "total_CPT": 10,
                    "referrals": []
                },
                {
                    "firstName": "Test",
                    "secondName": "Testovich 23",
                    "id": 23,
                    "total_CPT": 10,
                    "referrals": []
                }
            ]
        },
        {
            "firstName": "Test",
            "secondName": "Testovich 10",
            "id": 10,
            "total_CPT": 10,
            "referrals": []
        }
    ]
};

var titleTreeBlock = '.second-level > .tree-block > h2';
var totalCPTTreeBlock = '.second-level > .tree-block > h3';
var liSecondLevel = $('li.second-level');
var ulSecondLevel = $('ul.second-level');
$(titleTreeBlock).text(json['firstName'] + ' ' + json['secondName']);
$(totalCPTTreeBlock).text('CPT ' + json['total_CPT']);
var level = 1;
if (json['referrals'].length) {
    liSecondLevel.append(
        '<div class="line-right"></div>' +
        '<div class="participants-block">' +
            '<h2>' + json['referrals'].length + ' participants in level<i class="material-icons" data-level="'+ level +'">expand_more</i></h2>' +
        '</div>' +
        '<div class="line-bottom" style="display:none;"></div>'
    );
    ulSecondLevel.append(
        '<li>' +
            '<ul class="third-level participants close" data-level="' + level + '"></ul>' +
        '</li>'
    );
}

function appendTreeBlock(object, ulParticipants) {
    var block =
        '<li class="third-level participants">' +
            '<div class="tree-block">' +
                '<h2>' + object['firstName'] + ' ' + object['secondName'] + '</h2>' +
                '<h3>CPT ' + object['total_CPT'] + '</h3>' +
            '</div>' +
            '<div class="line-left"></div>';
    if (object['referrals'].length) {
        level++;
        block +=
                '<div class="line-right"></div>' +
                '<div class="participants-block">' +
                    '<h2>' + object['referrals'].length + ' participants in level<i class="material-icons" data-level="'+ level +'">expand_more</i></h2>' +
                '</div>' +
                '<div class="line-bottom" style="display:none;"></div>' +
            '</li>' +
            '<li>' +
                '<ul class="third-level participants close" data-level="' + level + '"></ul>' +
            '</li>';
        ulParticipants.append(block);
        var ulParticipantsInside = $('ul.third-level[data-level='+ level +']');
        var referrals = object['referrals'];
        $.each(referrals, function (index, element) {
            appendTreeBlock(element, ulParticipantsInside);
        });
    } else {
        block += '</li>';
        ulParticipants.append(block);
    }
}

console.log(json);