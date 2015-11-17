'use strict';
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
    },

    hideUnnecessaryToggles: function()
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
    },

    toggleSubpanel: function(e)
    {
        e.preventDefault()

        var $toggle = $(e.currentTarget)
        $toggle.toggleClass('is-active')
        var $subpanel = $('#' + $toggle.data('toggle'))

        $subpanel.toggleClass('is-active').slideToggle()

        if ($subpanel.is('.is-active'))
        {
            $('#submit-subpanel').addClass('is-active').slideDown()
        }
        else if ($('.js-subpanel.is-active').length === 1)
        {
            $('#submit-subpanel').removeClass('is-active').slideUp()
        }
    },

    limitPreviewHeight: function() {
        var hgt = 0;

        $('.limit_height').each(function() {
            var toggler = null,
            style = '';

            var limitheight = $(this);

            //size = div.getCoordinates();
            var size = {height : $(this).height()};
            if (hgt === 0) {
                hgt = parseInt($(this).attr('class').replace(/[^0-9]*/, ''));
            }

            // Return if there is no height value
            if (!hgt) return;

            $(this).css('height', hgt);

            toggler = $('<a class="btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped limit_toggler" data-delay="50" data-position="right" data-tooltip=""><i class="material-icons">expand_more</i></a>');


            // Disable the function if the preview height is below the max-height
            if (size.height < hgt) {
                return;
            }


            toggler.css('cursor', 'pointer');

            toggler.on('click', function (e) {
                e.preventDefault();
                if ($(this).hasClass('open')) {
                    $(this).removeClass('open');
                    limitheight.css('height', hgt);
                } else {
                    $(this).addClass('open');
                    limitheight.css('height', size.height);
                }
            });

            /*toggler.addEvent('click', function() {
                style = this.getPrevious('div').getStyle('height').toInt();
                this.getPrevious('div').setStyle('height', ((style > hgt) ? hgt : ''));

                if (this.get('data-state') == 0) {
                    this.src = Backend.themePath + 'collapse.gif';
                    this.set('data-state', 1);
                    this.store('tip:title', Contao.lang.collapse);
                } else {
                    this.src = Backend.themePath + 'expand.gif';
                    this.set('data-state', 0);
                    this.store('tip:title', Contao.lang.expand);
                }
            });*/

            $(this).after(toggler);
        });
    },

    initialize: function()
    {
        Backend.hideUnnecessaryToggles()

        // Bind events
        $('.js-toggle-subpanel').click(Backend.toggleSubpanel)
    }
}

$(function() 
{
    $(".button-collapse").sideNav()
    $('#modules-nav .collapsible-header').click(function(e) { e.preventDefault() })

    Backend.initialize()

    $('select').select2()
    $('.tooltipped').tooltip({delay: 50})

    Backend.limitPreviewHeight();
})