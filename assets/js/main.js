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

var Backend = {
    openModalIframe: function(e) {
        $('#modal').html('<iframe src="' + e.url + '" width="100%" height="100%" frameborder="0"></iframe>');
        $('#modal').openModal();
        return false;
        var t = e || {}
          , a = (window.getSize().y - 180).toInt();
        (!t.height || t.height > a) && (t.height = a);
        var n = new SimpleModal({
            width: t.width,
            hideFooter: !0,
            draggable: !1,
            overlayOpacity: .5,
            onShow: function() {
                document.body.setStyle("overflow", "hidden")
            },
            onHide: function() {
                document.body.setStyle("overflow", "auto")
            }
        });
        n.show({
            title: t.title,
            contents: '<iframe src="' + t.url + '" width="100%" height="' + t.height + '" frameborder="0"></iframe>'
        })
    }
};