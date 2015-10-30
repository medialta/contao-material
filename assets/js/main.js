function hideUnnecessaryToggles()
{
    $('.js-toggle-subpanel').each(function()
    {
        if (!$('#' + $(this).data('toggle')).length)
        {
            $(this).hide()
        }
    })
}

var toggleSubpanel = function(e)
{
    e.preventDefault()

    var $toggle = $(e.currentTarget)
    $toggle.toggleClass('is-active')
    $subpanel = $('#' + $toggle.data('toggle'))

    $subpanel.toggleClass('is-active').slideToggle()

    if ($subpanel.is('.is-active'))
    {
        $('#submit-subpanel').addClass('is-active').slideDown()
    }
    else if ($('.js-subpanel.is-active').length === 1)
    {
        $('#submit-subpanel').removeClass('is-active').slideUp()
    }
}

$(document).ready(function() {
    $('select').select2();
    $('.tooltipped').tooltip({delay: 50});
    hideUnnecessaryToggles()
    $('.js-toggle-subpanel').click(toggleSubpanel)
});
