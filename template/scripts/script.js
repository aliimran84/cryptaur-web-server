function conversionHeightLineBottom() {
    var ul = $('ul.second-level');
    var li = ul.children('li.'+ul.attr('class'));
    var positionTopFirstLi = $(li[0]).offset().top;
    var positionTopLastLi = $(li[li.length-1]).offset().top;
    ul.parent().prev().find('.line-bottom').css('height',positionTopLastLi - positionTopFirstLi + 84);
    ul = $('ul.participants');
    for (var i = 0; i < ul.length; i++) {
        li = $(ul[i]).children('li.'+$(ul[i]).attr('class').split(' ')[0]);
        positionTopFirstLi = $(li[0]).offset().top;
        positionTopLastLi = $(li[li.length-1]).offset().top;
        $(ul[i]).parent().prev().find('.line-bottom').css('height',positionTopLastLi - positionTopFirstLi + 211);
    }
}
$(document).ready(function(){
    $(".dropdown-button").dropdown();
    $('select').material_select();
    $('.collapsible').collapsible();
    $(".button-collapse").sideNav();
    var heightLineBottom;
    if ($('.referral-progam ul'))
        $('.referral-progam .line-bottom').css('height',$('.referral-progam ul').height());
    var secondLevel = $('li.second-level');
    heightLineBottom = 85;
    var marginTop = 20;
    for (var i = 0; i < secondLevel.length - 1; i++) {
        heightLineBottom += secondLevel[i].clientHeight + marginTop;
    }
    $('li.first-level>.line-bottom').css('height',heightLineBottom);

    $('.dashboard .participants-block i').click(function () {
        var element = $(this).closest('.participants').next().children();
        if ($(element).hasClass('close')) {
            $($(this).closest('.participants').find('.line-bottom')[0]).css('display','block');
            $(element).removeClass('close');
        } else {
            $($(this).closest('.participants').find('.line-bottom')[0]).css('display','none');
            $(element).addClass('close');
        }
        var treeBlock = $('.main-panel-block.tree');
        treeBlock.closest('.main-panel').css('height',treeBlock.closest('.my-group').height());
        conversionHeightLineBottom();
    });
});