function hideUnnecessaryToggles()
{
    var $parent = $('.js-toggle-subpanel').parent()

    $('.js-toggle-subpanel').each(function()
    {
        if (!$('#' + $(this).data('toggle')).length)
        {
            $(this).detach()
        }
    })

    if (!$parent.children().length)
    {
        $parent.html('')
    }
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
    $(".button-collapse").sideNav()
    $('#modules-nav .collapsible-header').click(function(e) { e.preventDefault() })

    hideUnnecessaryToggles()
    $('.js-toggle-subpanel').click(toggleSubpanel)

    $('select').select2();
    $('.tooltipped').tooltip({delay: 50});
});
