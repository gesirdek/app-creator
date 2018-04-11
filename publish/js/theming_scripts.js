import 'jquery-mask-plugin'
$(function(){
    $('body').on('keyup','.phone-placeholder',function(){
        $(this).val($(this).val().replace(/^\([^2,5]/, '('));
    });
});