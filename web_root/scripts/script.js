$(document).ready(function () {
    $(".dropdown-button").dropdown();
    $('select').material_select();
    $('.collapsible').collapsible();
    $(".button-collapse").sideNav();

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