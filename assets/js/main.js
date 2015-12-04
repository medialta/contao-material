'use strict';
var AjaxRequest = {
    /**
     * The theme path
     * @member {string}
     */
    themePath: Contao.script_url + 'system/themes/' + Contao.theme + '/images/',

    /**
     * Toggle the navigation menu
     *
     * @param {object} el The DOM element
     * @param {string} id The ID of the menu item
     *
     * @returns {boolean}
     */
    toggleNavigation: function(el, id) {
        
    },

    /**
     * Toggle the site structure tree
     *
     * @param {object} el    The DOM lement
     * @param {string} id    The ID of the target element
     * @param {int}    level The indentation level
     * @param {int}    mode  The insert mode
     *
     * @returns {boolean}
     */
    toggleStructure: function (el, id, level, mode) {
        
    },

    /**
     * Toggle the file manager tree
     *
     * @param {object} el     The DOM element
     * @param {string} id     The ID of the target element
     * @param {string} folder The folder's path
     * @param {int}    level  The indentation level
     *
     * @returns {boolean}
     */
    toggleFileManager: function (el, id, folder, level) {
        
    },

    /**
     * Toggle the page tree input field
     *
     * @param {object} el    The DOM element
     * @param {string} id    The ID of the target element
     * @param {string} field The field name
     * @param {string} name  The Ajax field name
     * @param {int}    level The indentation level
     *
     * @returns {boolean}
     */
    togglePagetree: function (el, id, field, name, level) {
        
    },

    /**
     * Toggle the file tree input field
     *
     * @param {object} el     The DOM element
     * @param {string} id     The ID of the target element
     * @param {string} folder The folder name
     * @param {string} field  The field name
     * @param {string} name   The Ajax field name
     * @param {int}    level  The indentation level
     *
     * @returns {boolean}
     */
    toggleFiletree: function (el, id, folder, field, name, level) {
        
    },

    /**
     * Toggle subpalettes in edit mode
     *
     * @param {object} el    The DOM element
     * @param {string} id    The ID of the target element
     * @param {string} field The field name
     */
    toggleSubpalette: function (el, id, field) {
        
    },

    /**
     * Toggle the visibility of an element
     *
     * @param {object} el    The DOM element
     * @param {string} id    The ID of the target element
     * @param {string} table The table name
     *
     * @returns {boolean}
     */
    toggleVisibility: function(el, id, table) {
        
    },

    /**
     * Feature/unfeature an element
     *
     * @param {object} el The DOM element
     * @param {string} id The ID of the target element
     *
     * @returns {boolean}
     */
    toggleFeatured: function(el, id) {
        
    },

    /**
     * Toggle the visibility of a fieldset
     *
     * @param {object} el    The DOM element
     * @param {string} id    The ID of the target element
     * @param {string} table The table name
     *
     * @returns {boolean}
     */
    toggleFieldset: function(el, id, table) {
        
    },

    /**
     * Toggle a group of a multi-checkbox field
     *
     * @param {object} el The DOM element
     * @param {string} id The ID of the target element
     *
     * @returns {boolean}
     */
    toggleCheckboxGroup: function(el, id) {
        
    },

    /**
     * Store the Live Update ID
     *
     * @param {object} el The DOM element
     * @param {string} id The ID of the input field
     */
    liveUpdate: function(el, id) {
        
    },

    /**
     * Display the "loading data" message
     *
     * @param {string} message The message text
     */
    displayBox: function(message) {
        
    },

    /**
     * Hide the "loading data" message
     */
    hideBox: function() {
        
    }
}
var Backend = {

    getScrollOffset: function() {
        //document.cookie = "BE_PAGE_OFFSET=" + window.getScroll().y + "; path=" + (Contao.path || '/');
        //TODO
    },

    autoSubmit: function(el) {
        Backend.getScrollOffset();
        var hidden = $('<input type="hidden" name="SUBMIT_TYPE" value="auto">');

        var form = $('#'+el);
        hidden.appendTo(form);
        form.submit();
    },

    openModalIframe: function(e) {
        $('#modal').html('<iframe src="' + e.url + '" width="100%" height="100%" frameborder="0"></iframe>');
        $('#modal').openModal();
        return ;
    },

    openModalSelector: function(options) {
        $('#modal').html('<iframe src="' + options.url + '" width="100%" height="100%" frameborder="0"></iframe>');
        $('#modal').openModal();
        return ;
        var opt = options || {},
            max = (window.getSize().y-180).toInt();
        if (!opt.height || opt.height > max) opt.height = max;
        var M = new SimpleModal({
            'width': opt.width,
            'btn_ok': Contao.lang.close,
            'draggable': false,
            'overlayOpacity': .5,
            'onShow': function() { document.body.setStyle('overflow', 'hidden'); },
            'onHide': function() { document.body.setStyle('overflow', 'auto'); }
        });
        M.addButton(Contao.lang.close, 'btn', function() {
            this.hide();
        });
        M.addButton(Contao.lang.apply, 'btn primary', function() {
            var frm = window.frames['simple-modal-iframe'],
                val = [], inp, field, i;
            if (frm === undefined) {
                alert('Could not find the SimpleModal frame');
                return;
            }
            if (frm.document.location.href.indexOf('contao/main.php') != -1) {
                alert(Contao.lang.picker);
                return; // see #5704
            }
            inp = frm.document.getElementById('tl_select').getElementsByTagName('input');
            for (i=0; i<inp.length; i++) {
                if (!inp[i].checked || inp[i].id.match(/^check_all_/)) continue;
                if (!inp[i].id.match(/^reset_/)) val.push(inp[i].get('value'));
            }
            if (opt.tag) {
                $(opt.tag).value = val.join(',');
                if (frm.document.location.href.indexOf('contao/page.php') != -1) {
                    $(opt.tag).value = '{{link_url::' + $(opt.tag).value + '}}';
                }
                opt.self.set('href', opt.self.get('href').replace(/&value=[^&]*/, '&value='+val.join(',')));
            } else {
                field = $('ctrl_' + opt.id);
                field.value = val.join("\t");
                var act = (frm.document.location.href.indexOf('contao/page.php') != -1) ? 'reloadPagetree' : 'reloadFiletree';
                new Request.Contao({
                    field: field,
                    evalScripts: false,
                    onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
                    onSuccess: function(txt, json) {
                        $('ctrl_'+opt.id).getParent('div').set('html', json.content);
                        json.javascript && Browser.exec(json.javascript);
                        AjaxRequest.hideBox();
                        window.fireEvent('ajax_change');
                    }
                }).post({'action':act, 'name':opt.id, 'value':field.value, 'REQUEST_TOKEN':Contao.request_token});
            }
            this.hide();
        });
        M.show({
            'title': opt.title,
            'contents': '<iframe src="' + opt.url + '" name="simple-modal-iframe" width="100%" height="' + opt.height + '" frameborder="0"></iframe>',
            'model': 'modal'
        });
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

    toggleVersion: function(e)
    {
        e.preventDefault()

        var $toggle = $(e.currentTarget)
        $toggle.toggleClass('is-active')
        var $subpanel = $('.js-version-panel');

        $subpanel.toggleClass('is-active').slideToggle()
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

            //TODO title tooltip

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

            $(this).after(toggler);
        });
    },

    initPanels: function()
    {
        var limit = $('#limit-subpanel').data('limit');
        if (limit) {
            $('#toggle-limit-subpanel').trigger('click');
        }

        var search = $('#search-subpanel').data('search');
        if (search) {
            $('#toggle-search-subpanel').trigger('click');
        }

        var filter = $('#filter-subpanel').data('filter');
        if (filter) {
            $('#toggle-filter-subpanel').trigger('click');
        }

        var sort = $('#sorting-subpanel').data('sort');
        if (sort) {
            $('#toggle-sort-subpanel').trigger('click');
        }
    },

    initialize: function()
    {
        Backend.hideUnnecessaryToggles()

        // Bind events
        $('.js-toggle-subpanel').click(Backend.toggleSubpanel)
        $('.js-toggle-version').click(Backend.toggleVersion)
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
    Backend.initPanels();
})