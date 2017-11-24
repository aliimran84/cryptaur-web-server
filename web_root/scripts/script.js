var mainPanel = $('.main-panel');
function resizeMainPanel() {
    mainPanel.each(function (index,element) {
        $(element).css('height',$(element).parent().height());
    });
}
if (window.outerWidth > 600) {
    resizeMainPanel();
    $(window).resize(function(){
        resizeMainPanel();
    });
}

$(document).ready(function(){
    $(".dropdown-button").dropdown();
    $('select').material_select();
    $('.collapsible').collapsible();
    $(".button-collapse").sideNav();
    var heightLineBottom;
    if ($('.referral-progam ul'))
        $('.referral-progam .line-bottom').css('height',$('.referral-progam ul').height());
    var thirdLevel = $('li.third-level');
    var fourthLevel = $('li.fourth-level');
    var fifthLevel = $('li.fifth-level');
    var sixthLevel = $('li.sixth-level');
    heightLineBottom = 85;
    var marginTop = 20;
    for (var i = 0; i < thirdLevel.length - 1; i++) {
        heightLineBottom += thirdLevel[i].clientHeight + marginTop;
    }
    $('li.second-level>.line-bottom').css('height',heightLineBottom);
    heightLineBottom = 85;
    for (var i = 0; i < fourthLevel.length - 1; i++) {
        heightLineBottom += fourthLevel[i].clientHeight + marginTop;
    }
    $('li.third-level>.line-bottom').css('height',heightLineBottom);
    heightLineBottom = 85;
    for (var i = 0; i < fifthLevel.length - 1; i++) {
        heightLineBottom += fifthLevel[i].clientHeight + marginTop;
    }
    $('li.fourth-level>.line-bottom').css('height',heightLineBottom);
    heightLineBottom = 85;
    for (var i = 0; i < sixthLevel.length - 1; i++) {
        heightLineBottom += sixthLevel[i].clientHeight + marginTop;
    }
    $('li.fifth-level>.line-bottom').css('height',heightLineBottom);
});

$(document).ready(function () {
    window.onAmountChange = function (input) {
        var val = parseFloat($(input).val());
        if (!val) {
            val = 0;
        }
        $('#selected_amount').html(val);
    };
    $('#select-coins').on('change', function () {
        var coin = $(this).val();
        $('.div-amount-coins').hide();
        $('#div-amount-' + coin).show();
        $('#selected_currency').text(coin);
        window.onAmountChange($('#input-amount-' + coin)[0]);
        var walletAddrText = 'Wallet registration in progress';
        if (window.investorWallets[coin]) {
            walletAddrText = window.investorWallets[coin];
        }
        $('#selected_wallet_addr').html(walletAddrText);
    }).change();
});