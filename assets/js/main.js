var toggleSubpanel = function(e)
{
    e.preventDefault()
    $subpanel = $('#' + e.currentTarget.getAttribute('data-toggle'))
    $subpanel.slideToggle()

    if ($subpanel.is(':visible'))
    {
        $('#submit-subpanel').slideDown()
    }
}

$(document).ready(function() {
    $('select').select2();
    $('.tooltipped').tooltip({delay: 50});
    $('.js-toggle-subpanel').click(toggleSubpanel)
});
