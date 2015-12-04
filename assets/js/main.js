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
        el.blur();
        var fs = $('#pal_' + id + ' .collapsible-header');

        if (fs.hasClass('active')) {
            $.ajax({
                url: window.location,
                dataType: 'JSON',
                type: 'POST',
                data: {action: 'toggleFieldset', id: id, table: table, state: 1, REQUEST_TOKEN: Contao.request_token},
            });
        } else {
            $.ajax({
                url: window.location,
                dataType: 'JSON',
                type: 'POST',
                data: {action: 'toggleFieldset', id: id, table: table, state: 0, REQUEST_TOKEN: Contao.request_token},
            });
        }

        return true;
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
    /**
     * The current ID
     * @member {string}
     */
    currentId: null,

    /**
     * The x mouse position
     * @member {int}
     */
    xMousePosition: 0,

    /**
     * The Y mouse position
     * @member {int}
     */
    yMousePosition: 0,

    /**
     * The popup window
     * @member {object}
     */
    popupWindow: null,

    /**
     * The theme path
     * @member {string}
     */
    themePath: Contao.script_url + 'system/themes/' + Contao.theme + '/images/',

    /**
     * Get the current mouse position
     *
     * @param {object} event The event object
     */
    getMousePosition: function(event) {
        
    },

    /**
     * Open a new window
     *
     * @param {object} el     The DOM element
     * @param {int}    width  The width in pixels
     * @param {int}    height The height in pixels
     *
     * @deprecated Use Backend.openModalWindow() instead
     */
    openWindow: function(el, width, height) {
        
    },

    /**
     * Open a modal window
     *
     * @param {int}    width   The width in pixels
     * @param {string} title   The window's title
     * @param {string} content The window's content
     */
    openModalWindow: function(width, title, content) {
        
    },

    /**
     * Open an image in a modal window
     *
     * @param {object} options An optional options object
     */
    openModalImage: function(options) {
        
    },

    /**
     * Open an iframe in a modal window
     *
     * @param {object} options An optional options object
     */
    openModalIframe: function(e) {
        $('#modal').html('<iframe src="' + e.url + '" width="100%" height="100%" frameborder="0"></iframe>');
        $('#modal').openModal();
        return ;
    },

    /**
     * Open a selector page in a modal window
     *
     * @param {object} options An optional options object
     */
    openModalSelector: function(options) {
        
    },

    /**
     * Open a TinyMCE file browser in a modal window
     *
     * @param {string} field_name The field name
     * @param {object} url        An URI object
     * @param {string} type       The picker type
     * @param {object} win        The window object
     */
    openModalBrowser: function(field_name, url, type, win) {
        
    },

    /**
     * Get the current scroll offset and store it in a cookie
     */
    getScrollOffset: function() {
        
    },

    /**
     * Automatically submit a form
     *
     * @param {object} el The DOM element
     */
    autoSubmit: function(el) {
        Backend.getScrollOffset();
        var hidden = $('<input type="hidden" name="SUBMIT_TYPE" value="auto">');

        var form = $('#'+el);
        hidden.appendTo(form);
        form.submit();
    },

    /**
     * Scroll the window to a certain vertical position
     *
     * @param {int} offset The offset to scroll to
     */
    vScrollTo: function(offset) {
        
    },

    /**
     * Limit the height of the preview pane
     */
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

    /**
     * Toggle checkboxes
     *
     * @param {object} el The DOM element
     * @param {string} id The ID of the target element
     */
    toggleCheckboxes: function(el, id) {
        
    },

    /**
     * Toggle a checkbox group
     *
     * @param {object} el The DOM element
     * @param {string} id The ID of the target element
     */
    toggleCheckboxGroup: function(el, id) {
        
    },

    /**
     * Toggle checkbox elements
     *
     * @param {string} el  The DOM element
     * @param {string} cls The CSS class name
     */
    toggleCheckboxElements: function(el, cls) {
        
    },

    /**
     * Toggle the line wrapping mode of a textarea
     *
     * @param {string} id The ID of the target element
     */
    toggleWrap: function(id) {
        
    },

    /**
     * Toggle the synchronization results
     */
    toggleUnchanged: function() {
        
    },

    /**
     * Toggle the opacity of the paste buttons
     *
     * @deprecated Not required anymore
     */
    blink: function() {},

    /**
     * Initialize the mootools color picker
     *
     * @returns {boolean}
     *
     * @deprecated Not required anymore
     */
    addColorPicker: function() {
        return true;
    },

    /**
     * Open the page picker wizard in a modal window
     *
     * @param {string} id The ID of the target element
     *
     * @deprecated Use Backend.openModalIframe() instead
     */
    pickPage: function(id) {
        
    },

    /**
     * Open the file picker wizard in a modal window
     *
     * @param {string} id     The ID of the target element
     * @param {string} filter The filter value
     *
     * @deprecated Use Backend.openModalIframe() instead
     */
    pickFile: function(id, filter) {
        
    },

    /**
     * Collapse all palettes
     */
    collapsePalettes: function() {
        
    },

    /**
     * Add the interactive help
     */
    addInteractiveHelp: function() {
        
    },

    /**
     * Make parent view items sortable
     *
     * @param {object} ul The DOM element
     *
     * @author Joe Ray Gregory
     * @author Martin Auswöger
     */
    makeParentViewSortable: function(ul) {
        
    },

    /**
     * Make multiSRC items sortable
     *
     * @param {string} id  The ID of the target element
     * @param {string} oid The DOM element
     */
    makeMultiSrcSortable: function(id, oid) {
        
    },

    /**
     * Make the wizards sortable
     */
    makeWizardsSortable: function() {
        
    },

    /**
     * List wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    listWizard: function(el, command, id) {
        
    },

    /**
     * Table wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    tableWizard: function(el, command, id) {
        
    },

    /**
     * Resort the table wizard fields
     *
     * @param {object} tbody The DOM element
     */
    tableWizardResort: function(tbody) {
        
    },

    /**
     * Resize the table wizard fields on focus
     *
     * @param {float} factor The resize factor
     */
    tableWizardResize: function(factor) {
        
    },

    /**
     * Module wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    moduleWizard: function(el, command, id) {
        
    },

    /**
     * Options wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    optionsWizard: function(el, command, id) {
        
    },

    /**
     * Key/value wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    keyValueWizard: function(el, command, id) {
        
    },

    /**
     * Checkbox wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    checkboxWizard: function(el, command, id) {
        
    },

    /**
     * Meta wizard
     *
     * @param {object} el The select element
     * @param {string} ul The DOM element
     */
    metaWizard: function(el, ul) {
        
    },

    /**
     * Remove a meta entry
     *
     * @param {object} el The DOM element
     */
    metaDelete: function(el) {
        
    },

    /**
     * Toggle the "add language" button
     *
     * @param {object} el The DOM element
     */
    toggleAddLanguageButton: function(el) {
        
    },

    /**
     * Update the "edit module" links in the module wizard
     *
     * @param {object} el The DOM element
     */
    updateModuleLink: function(el) {
        
    },

    /**
     * Convert the "enable module" checkboxes
     */
    convertEnableModules: function() {
        
    },

    /**
     * Update the fields of the imageSize widget upon change
     */
    enableImageSizeWidgets: function() {
        
    },

    /**
     * Allow to toggle checkboxes or radio buttons by clicking a row
     *
     * @author Kamil Kuzminski
     */
    enableToggleSelect: function() {
        
    },

    /**
     * Allow to mark the important part of an image
     *
     * @param {object} el The DOM element
     */
    editPreviewWizard: function(el) {
        
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