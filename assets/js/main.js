'use strict';

$.fn.addEvent = function () {
    console.warn('Use deprecated function addEvent')
}

var AjaxRequest = {
    /**
     * The theme path
     * @member {string}
     */
    themePath: Contao.script_url + 'system/modules/contao-material/assets/images/',

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
        el.blur();

        var item = $('#' + id);
        var image = $(el).first('img');

        if (item.length) {
            if (item.css('display') == 'none') {
                item.css('display', 'block')
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'toggleStructure', id: id, state: 1, REQUEST_TOKEN:Contao.request_token},
                });
            } else {
                item.css('display', 'none')
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'toggleStructure', id: id, state: 0, REQUEST_TOKEN:Contao.request_token},
                });
            }
            $(el).closest('.collapsible-header').toggleClass('active')
            return false;
        }

        AjaxRequest.displayBox(Contao.lang.loading + ' …')
        $('.tooltipped').tooltip('remove');
        $.ajax({
            url: window.location,
            type: 'POST',
            dataType: 'HTML',
            data: {action: 'loadStructure', id: id, level: level, state: 1, REQUEST_TOKEN: Contao.request_token},
        })
        .done(function (res) {
            var div = '<div class="collapsible-body" id="' + id + '" style="display: block;"><ul class="level_' + level + ' collapsible" data-collapsible="expandable">'
            div += res
            div += '</ul></div>'
            $(el).closest('.collapsible-header').after(div)
            $(el).closest('.collapsible-header').toggleClass('active')
            $('.tooltipped').tooltip({delay: 50});
            AjaxRequest.hideBox();
        })
        return false;
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
        el.blur();

        var item = $('#' + id)

        if (item.length) {
            if (item.css('display') == 'none') {
                item.css('display', 'block')
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'toggleFileManager', id: id, state: 1, REQUEST_TOKEN:Contao.request_token},
                });
            } else {
                item.css('display', 'none')
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'toggleFileManager', id: id, state: 0, REQUEST_TOKEN:Contao.request_token},
                });
            }
            $(el).closest('.collapsible-header').toggleClass('active')
            return false;
        }

        AjaxRequest.displayBox(Contao.lang.loading + ' …')
        $('.tooltipped').tooltip('remove');
        $.ajax({
            url: window.location,
            type: 'POST',
            dataType: 'HTML',
            data: {action: 'loadFileManager', id: id, level: level, 'folder': folder, state: 1, REQUEST_TOKEN: Contao.request_token},
        })
        .done(function (res) {
            var div = '<div class="collapsible-body" id="' + id + '" style="display: block;"><ul class="level_' + level + ' collapsible" data-collapsible="expandable">'
            div += res
            div += '</ul></div>'
            $(el).closest('.collapsible-header').after(div)
            $(el).closest('.collapsible-header').toggleClass('active')
            $('.tooltipped').tooltip({delay: 50});
            AjaxRequest.hideBox();
        })
        return false;
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
        el.blur();
        Backend.getScrollOffset();

        var item = $('#' + id);

        if (item.length) {
            if (item.css('display') == 'none') {
                item.css('display', 'block')
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'togglePagetree', id: id, state: 1, REQUEST_TOKEN:Contao.request_token},
                });
            } else {
                item.css('display', 'none')
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'togglePagetree', id: id, state: 0, REQUEST_TOKEN:Contao.request_token},
                });
            }
            $(el).closest('.collapsible-header').toggleClass('active')
            return false;
        }

        AjaxRequest.displayBox(Contao.lang.loading + ' …')
        $('.tooltipped').tooltip('remove');
        $.ajax({
            url: window.location,
            type: 'POST',
            dataType: 'HTML',
            data: {action: 'loadPagetree', id: id, level: level, 'field':field, 'name':name, state: 1, REQUEST_TOKEN: Contao.request_token},
        })
        .done(function (res) {
            var div = '<div class="collapsible-body" id="' + id + '" style="display: block;"><ul class="level_' + level + ' collapsible" data-collapsible="expandable">'
            div += res
            div += '</ul></div>'
            $(el).closest('.collapsible-header').after(div)
            $(el).closest('.collapsible-header').toggleClass('active')
            $('.tooltipped').tooltip({delay: 50});
            AjaxRequest.hideBox();
        })
        return false;
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
        el.blur();
        Backend.getScrollOffset();

        var item = $('#' + id);

        if (item.length) {
            if (item.css('display') == 'none') {
                item.css('display', 'block')
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'toggleFiletree', id: id, state: 1, REQUEST_TOKEN:Contao.request_token},
                });
            } else {
                item.css('display', 'none')
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'toggleFiletree', id: id, state: 0, REQUEST_TOKEN:Contao.request_token},
                });
            }
            $(el).closest('.collapsible-header').toggleClass('active')
            return false;
        }

        AjaxRequest.displayBox(Contao.lang.loading + ' …')
        $('.tooltipped').tooltip('remove');
        $.ajax({
            url: window.location,
            type: 'POST',
            dataType: 'HTML',
            data: {action: 'loadFiletree', id: id, level: level, 'folder': folder, 'field':field, 'name':name, state: 1, REQUEST_TOKEN: Contao.request_token},
        })
        .done(function (res) {
            var div = '<div class="collapsible-body" id="' + id + '" style="display: block;"><ul class="level_' + level + ' collapsible" data-collapsible="expandable">'
            div += res
            div += '</ul></div>'
            $(el).closest('.collapsible-header').after(div)
            $(el).closest('.collapsible-header').toggleClass('active')
            $('.tooltipped').tooltip({delay: 50});
            AjaxRequest.hideBox();
        })
        return false;
    },

    /**
     * Toggle subpalettes in edit mode
     *
     * @param {object} el    The DOM element
     * @param {string} id    The ID of the target element
     * @param {string} field The field name
     */
    toggleSubpalette: function (el, id, field) {
        el.blur();
        var item = $('#' + id);
        if (item.length) {
            if (!el.value) {
                el.value = 1;
                el.checked = 'checked';
                item.css('display', 'block');
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'toggleSubpalette', id: id, field: field, state: 1, REQUEST_TOKEN:Contao.request_token},
                });
            } else {
                el.value = '';
                el.checked = '';
                item.css('display', 'none');
                $.ajax({
                    url: window.location,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {action: 'toggleSubpalette', id: id, field: field, state: 0, REQUEST_TOKEN:Contao.request_token},
                });
            }
            return;
        }

        AjaxRequest.displayBox(Contao.lang.loading + ' …')
        $.ajax({
            url: window.location,
            type: 'POST',
            dataType: 'HTML',
            data: {action: 'toggleSubpalette', id: id, field: field, load: 1, state: 1, REQUEST_TOKEN: Contao.request_token},
        })
        .done(function (res, json) {
            var div = $('<div id="' + id + '" style="display:block">' + res + '</div>');
            $(el).parent('div').parent('div').after(div);
            el.value = 1;
            el.checked = 'checked';
            div.find('a').each(function(index, el) {
                $(el).attr('href', $(el).attr('href').replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id));
            });
            $('select').select2();
            AjaxRequest.hideBox();
        });
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
        el.blur();
        var img = null,
            icon = $(el).find('i'),
            published = (icon.text() == 'visibility'),
            div = $(el).parent('div'),
            index, next, icone, icond, pa;

        // Find the icon depending on the view (tree view, list view, parent view)
        if (div.hasClass('actions')) {
            if (div.next('div.cte_type').length) {
                img = div.next('div.cte_type')
            }
            if (div.prev('div.item').length) {
                img = div.prev('div.item').find('img')
            }
        }
        // Change the icon
        if (img !== null) {
            if (img.parent('listing-container').hasClass('tree_view')) {
                alert('tree')
            }

            else if (img.hasClass('cte_type')) {
                if (!published) {
                    img.addClass('published')
                    img.removeClass('unpublished')
                } else {
                    img.addClass('unpublished')
                    img.removeClass('published')
                }
            }

            else {
                icone = img.data('icon')
                icond = img.data('icon-disabled')
                icone = icone.replace('gif', 'png')
                icond = icond.replace('gif', 'png')
                // Backwards compatibility
                if (img.data('icon') === null) {
                    icone = img.attr('src').replace(/.*\/([a-z0-9]+)_?\.(gif|png|jpe?g|svg)$/, '$1.$2')
                }
                if (img.data('icon-disabled') === null) {
                    icond = img.attr('src').replace(/.*\/([a-z0-9]+)_?\.(gif|png|jpe?g|svg)$/, '$1_.$2')
                }
                img.attr('src', AjaxRequest.themePath + (!published ? icone : icond))
            }
        }

        // Send request
        if (!published) {
            icon.text('visibility')
            $.ajax({
                url: window.location,
                type: 'GET',
                dataType: 'JSON',
                data: {tid: id, state: 1, rt: Contao.request_token},
            })
        } else {
            icon.text('visibility_off')
            $.ajax({
                url: window.location,
                type: 'GET',
                dataType: 'JSON',
                data: {tid: id, state: 0, rt: Contao.request_token},
            })
        }

        return false;
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
        var box = $('#tl_ajaxBox'),
            scroll = $(document).scrollTop();

        if (box.length == 0) {
            $('body').append('<div id="tl_ajaxBox"></div>')
            box = $('#tl_ajaxBox')
        }

        box.css('display', 'block')
        box.css('top', (scroll + 100) + 'px')
        box.html(message)
    },

    /**
     * Hide the "loading data" message
     */
    hideBox: function() {
        var box = $('#tl_ajaxBox')
        if (box.length) {
            box.css('display', 'none')
        }
    }
}

var Theme = {
    hoverRow: function () {

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
    themePath: Contao.script_url + 'system/modules/contao-material/assets/images/',

    focusInput: function (input) {

    },

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
        $('#modal').html('<iframe src="' + e.url + '" width="100%" height="100%" frameborder="0"></iframe><div class="modal-footer"><a class="modal-action modal-close btn-flat" title="' + Contao.lang.close +'">' + Contao.lang.close +'</a></div>');
        $('#modal').openModal();
        return ;
    },

    /**
     * Open a selector page in a modal window
     *
     * @param {object} options An optional options object
     */
    openModalSelector: function(options) {
        var opt = options || {},
            max = (screen.height*0.45);
        if (!opt.height || opt.height > max) opt.height = max;
        var html = '<div class="modal-content"><h4>' + opt.title + '</h4><iframe src="' + opt.url + '" name="simple-modal-iframe" width="100%" height="100%" frameborder="0"></iframe></div>';

        html += '<div class="modal-footer"><a class="modal-action modal-apply btn orange lighten-2" title="' + Contao.lang.apply + '">' + Contao.lang.apply + '</a><a class="modal-action modal-close btn-flat" title="' + Contao.lang.close +'">' + Contao.lang.close +'</a></div>';
        $('#modal').html(html);
        $('#modal').openModal();
        $('#modal .modal-apply').on('click', function () {
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
                $('#' + opt.tag).val(val.join(','));
                if (frm.document.location.href.indexOf('contao/page.php') != -1) {
                    $('#' + opt.tag).val('{{link_url::' + $('#' + opt.tag).val() + '}}');
                }
                $(opt.self).attr('href', $(opt.self).attr('href').replace(/&value=[^&]*/, '&value='+val.join(',')));
            } else {
                field = $('ctrl_' + opt.id);
                field.value = val.join("\t");
                var act = (frm.document.location.href.indexOf('contao/page.php') != -1) ? 'reloadPagetree' : 'reloadFiletree';
                $.ajax({
                    url: window.location,
                    dataType: 'HTML',
                    type: 'POST',
                    data: {action: act, name: opt.id, value: field.value, REQUEST_TOKEN: Contao.request_token},
                })
                .done(function (res, json) {
                    $('#ctrl_'+opt.id).parent('div').html(res)
                });
            }
            $('#modal').closeModal();
        });

        return ;
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
        var file = 'file.php',
            swtch = (type == 'file' ? '&amp;switch=1' : ''),
            isLink = (url.indexOf('{{link_url::') != -1);
        if (type == 'file' && (url == '' || isLink)) {
            file = 'page.php';
        }
        if (isLink) {
            url = url.replace(/^\{\{link_url::([0-9]+)\}\}$/, '$1');
        }

        var html = '<div class="modal-content"><h4>' + $('.mce-title').text() + '</h4><iframe src="contao/' + file + '?table=tl_content&amp;field=singleSRC&amp;value=' + url + swtch + '" name="simple-modal-iframe" width="100%" height="100%" frameborder="0"></iframe></div>';

        html += '<div class="modal-footer"><a class="modal-action modal-apply btn orange lighten-2" title="' + Contao.lang.apply + '">' + Contao.lang.apply + '</a><a class="modal-action modal-close btn-flat" title="' + Contao.lang.close +'">' + Contao.lang.close +'</a></div>';
        $('#modal').html(html);
        $('#modal').openModal();
        $('#modal .modal-apply').on('click', function () {
            var frm = window.frames['simple-modal-iframe'],
                val = [], inp, field, i;
            if (frm === undefined) {
                alert('Could not find the SimpleModal frame');
                return;
            }
            inp = frm.document.getElementById('tl_select').getElementsByTagName('input');
            for (i=0; i<inp.length; i++) {
                if (inp[i].checked && !inp[i].id.match(/^reset_/)) {
                    val = inp[i].get('value');
                    break;
                }
            }
            if (!isNaN(val)) {
                val = '{{link_url::' + val + '}}';
            }
            $('#' + field_name).val(val);
            $('#modal').closeModal();
        });
    },

    /**
     * Get the current scroll offset and store it in a cookie
     */
    getScrollOffset: function() {
        document.cookie = "BE_PAGE_OFFSET=" + $(document).scrollTop() + "; path=" + (Contao.path || '/');
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
        jQuery(document).ready(function($) {
            $('html,body').animate({ scrollTop: parseInt(offset) }, 600);
        });
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
        var items = $('input'),
            status = $(el).is(':checked') ? 'checked' : '',
            iter = 0;

        for (var i=0; i<items.length; i++) {
            if ($(items[i]).attr('type') != 'checkbox') {
                continue;
            }
            iter++
            (function (x) {
                setTimeout(function () {
                    $(items[x]).prop('checked', (status == 'checked' ? true : false))
                }, 50*iter)
            })(i);
        }
    },

    /**
     * Toggle a checkbox group
     *
     * @param {object} el The DOM element
     * @param {string} id The ID of the target element
     */
    toggleCheckboxGroup: function(el, id) {
        var cls = $(el).attr('class'),
            status = $(el).is(':checked') ? 'checked' : ''
        if (cls == 'tl_checkbox') {
            var cbx = $('#' + id).length ? $('#' + id + ' .tl_checkbox') : $(el).parent('fieldset').children('.tl_checkbox')
            cbx.each(function(index, checkbox) {
                setTimeout(function () {
                    $(checkbox).prop('checked', (status == 'checked' ? true : false))
                }, 50*index)
            });
        } else if (cls == 'tl_tree_checkbox') {
            $('#' + id + ' .parent .tl_tree_checkbox').each(function(index, checkbox) {
                setTimeout(function () {
                    $(checkbox).prop('checked', (status == 'checked' ? true : false))
                }, 50*index)
            });
        }
    },

    /**
     * Toggle checkbox elements
     *
     * @param {string} el  The DOM element
     * @param {string} cls The CSS class name
     */
    toggleCheckboxElements: function(el, cls) {
        var status = $(el).is(':checked') ? 'checked' : ''

        $('.' + cls).each(function(index, checkbox) {
             if ($(checkbox).hasClass('tl_checkbox')) {
                setTimeout(function () {
                    $(checkbox).prop('checked', (status == 'checked' ? true : false))
                }, 50*index)
             }
        });

        Backend.getScrollOffset();
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
        $('#result-list .tl_confirm').each(function(index, el) {
            $(el).toggleClass('hidden');
        });
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
        $('.tl_tip').each(function(index, el) {
            $(this).attr('data-title', $(this).html().replace('<i class="tiny material-icons help-icon">info_outline</i>', ''))
        });
    },

    /**
     * Make parent view items sortable
     *
     * @param {object} ul The DOM element
     *
     * @author Joe Ray Gregory
     * @author Martin Auswöger
     */
    makeParentViewSortable: function(ul)
    {
        var drag = dragula([document.getElementById(ul)],
        {
            moves: function (el, container, handle)
            {
                return $(handle).is('.drag-handle') || $(handle).closest('.drag-handle').length > 0;
            }
        })

        drag.on('drop', function(el, target, source, sibling)
        {
            var pid, mode;
            var id = el.id.replace(/li_/, '');
            var href = window.location.href.replace(/\?.*$/, '');

            var $prevLi = $(el).prev('li');
            var $parentUl = $(el).parent('ul');

            if ($prevLi.length)
            {
                pid = $prevLi.attr('id').replace(/li_/, '');
                mode = 1;
            }
            else if ($parentUl.length)
            {
                pid = $parentUl.attr('id').replace(/ul_/, '');
                mode = 2;
            }

            if (mode)
            {
                $.ajax({
                    url: href,
                    dataType: 'JSON',
                    type: 'GET',
                    data: window.location.search.replace(/id=[0-9]*/, 'id=' + id).replace('?', '') + '&act=cut&mode=' + mode + '&pid=' + pid
                });
            }
        })
    },

    /**
     * Make multiSRC items sortable
     *
     * @param {string} id  The ID of the target element
     * @param {string} oid The DOM element
     */
    makeMultiSrcSortable: function(id, oid)
    {
        var drag = dragula([document.getElementById(id)]);

        $('#' + id + ' > li').addClass('drag-handle')

        drag.on('drop', function(el, target, source, sibling)
        {
            var els = [];

            $('#' + id).children('li').each(function(index)
            {
                els.push(this.getAttribute('data-id'));
            })

            document.getElementById(oid).value = els.join(',');
        });
    },

    /**
     * Make the wizards sortable
     */
    makeWizardsSortable: function()
    {
        $('.tl_listwizard, .tl_tablewizard, .tl_modulewizard, .tl_optionwizard, .tl_checkbox_wizard').each(function()
        {
            if (!$(this).is('.tl_checkbox_wizard.sortable-done'))
            {
                var drag = dragula([$(this).find('.sortable').get(0)],
                {
                    moves: function (el, container, handle)
                    {
                        return $(handle).is('.drag-handle') || $(handle).closest('.drag-handle').length > 0;
                    }
                });

                if ($(this).is('.tl_checkbox_wizard'))
                {
                    $(this).addClass('sortable-done');
                }

                if ($(this).is('.tl_tablewizard'))
                {
                    drag.on('drop', function(el, target, source, sibling)
                    {
                        Backend.tableWizardResort($(drag.containers[0]));
                    });
                }
            }
        })
    },

    /**
     * List wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    listWizard: function(el, command, id) {
        var list = $('#' + id),
            parent = $(el).closest('li'),
            items = list.children(),
            tabindex = list.data('tabindex'),
            input, previous, next, rows, i;

        Backend.getScrollOffset();

        switch (command) {
            case 'copy':
                var clone = parent.clone(true)
                parent.before(clone);
                if (parent.find('input').length) {
                    input = parent.find('input')
                    clone.find('input').val(input.val());
                }
                break;
            case 'up':
                if (parent.prev('li').length) {
                    parent.prev('li').before(parent)
                } else {
                    list.append(parent)
                }
                break;
            case 'down':
                if (parent.next('li').length) {
                    parent.next('li').after(parent)
                } else {
                    list.prepend(parent)
                }
                break;
            case 'delete':
                if (items.length > 1) {
                    parent.remove();
                }
                break;
        }

        rows = list.children();

        for (i=0; i<rows.length; i++) {
            if ($(rows[i]).find('input[type="text"]').length) {
                input = $(rows[i]).find('input[type="text"]')
                input.attr('tabindex', tabindex++)
            }
        }
    },

    /**
     * Table wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    tableWizard: function(el, command, id) {
        var table = $('#' + id),
            tbody = table.find('tbody'),
            rows = tbody.children(),
            parentTd = $(el).closest('td'),
            parentTr = parentTd.closest('tr'),
            headTr = table.find('thead').find('tr'),
            cols = parentTr.children(),
            index = 0,
            textarea, previous, next, current, i, tr;
        for (i=0; i<cols.length; i++) {

            if (parentTr.children()[i] == parentTd.get(0)) {
                break;
            }
            index++;
        }

        Backend.getScrollOffset();

        switch (command) {
            case 'rcopy':
                tr = $('<tr/>')
                for (i=0; i<cols.length; i++) {
                    next = $(cols[i]).clone(true).appendTo(tr);
                    if (textarea = $(cols[i]).find('textarea')) {
                        next.find('textarea').val(textarea.val());
                    }
                }
                parentTr.after(tr);
                break;
            case 'rup':
                if (parentTr.prev('tr').length) {
                    parentTr.prev('tr').before(parentTr);
                } else {
                    tbody.append(parentTr);
                }
                break;
            case 'rdown':
                if (parentTr.next('tr').length) {
                    parentTr.next('tr').after(parentTr);
                } else {
                    tbody.prepend(parentTr);
                }
                break;
            case 'rdelete':
                if (rows.length > 1) {
                    parentTr.remove();
                } else {
                    parentTr.find('textarea').val('');
                }
                break;
            case 'ccopy':
                for (i=0; i<rows.length; i++) {
                    current = $(rows[i]).children()[index];
                    next = $(current).after($(current).clone(true));
                    if (textarea = $(current).find('textarea')) {
                        next.find('textarea').val(textarea.val());
                    }
                }
                //headTr.getFirst('td').clone(true).inject(headTr.getLast('td'), 'before');
                headTr.children().last().before(headTr.children().first().clone(true));
                break;
            case 'cmovel':
                if (index > 0) {
                    for (i=0; i<rows.length; i++) {
                        current = $(rows[i]).children()[index];
                        $(current).prev().before(current)
                    }
                } else {
                    for (i=0; i<rows.length; i++) {
                        current = $(rows[i]).children()[index];
                        $(rows[i]).children().last().before(current)
                    }
                }
                break;
            case 'cmover':
                if (index < (cols.length - 2)) {
                    for (i=0; i<rows.length; i++) {
                        current = $(rows[i]).children()[index];
                        $(current).next().after(current)
                    }
                } else {
                    for (i=0; i<rows.length; i++) {
                        current = $(rows[i]).children()[index];
                        $(rows[i]).children().first().before(current)
                    }
                }
                break;
            case 'cdelete':
                if (cols.length > 2) {
                    for (i=0; i<rows.length; i++) {
                        $(rows[i]).children()[index].remove();
                    }
                    headTr.find('td').first().remove();
                } else {
                    for (i=0; i<rows.length; i++) {
                        $(rows[i]).find('textarea').val('');
                    }
                }
                break;

        }

        Backend.tableWizardResort(tbody);

        Backend.tableWizardResize();
    },

    /**
     * Resort the table wizard fields
     *
     * @param {object} tbody The DOM element
     */
    tableWizardResort: function(tbody) {
        var rows = tbody.children(),
            tabindex = tbody.data('tabindex'),
            textarea, childs, i, j;
        for (i=0; i<rows.length; i++) {
            childs = $(rows[i]).children()
            for (j=0; j<childs.length; j++) {
                if ($(childs[j]).find('textarea').length) {
                    textarea = $(childs[j]).find('textarea')
                    textarea.attr('tabindex', tabindex++)
                    textarea.attr('name', textarea.attr('name').replace(/\[[0-9]+\][[0-9]+\]/g, '[' + i + '][' + j + ']'))
                }
            }
        }
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
        var table = $('#' + id),
            tbody = table.find('tbody'),
            parent = $(el).closest('tr'),
            rows = tbody.children(),
            tabindex = tbody.data('tabindex'),
            input, select, childs, a, i, j, tr;
        switch (command) {
            case 'copy':
                parent.find('select').each(function(index, ele) {
                    $(ele).select2('destroy')
                });
                tr = $('<tr/>')
                childs = parent.children()
                for (i=0; i<childs.length; i++) {
                    var next = $(childs[i]).clone(true).appendTo(tr)
                }
                parent.after(tr)
                break;
            case 'up':
                if (parent.prev('tr').length) {
                   parent.prev('tr').before(parent)
                } else {
                    tbody.append(parent)
                }
                break;
            case 'down':
                if (parent.next('tr').length) {
                    parent.next('tr').after(parent)
                } else {
                    tbody.prepend(parent)
                }
                break;
            case 'delete':
                if (rows.length > 1) {
                    parent.remove();
                }
                break;
        }

        rows = tbody.children()

        for (i=0; i<rows.length; i++) {
            childs = $(rows[i]).children()
            for (j=0; j<childs.length; j++) {
                if ($(childs[j]).find('select').length) {
                    select = $(childs[j]).find('select')
                    select.attr('name', select.attr('name').replace(/\[[0-9]+\]/g, '[' + i + ']'))
                }
                if ($(childs[j]).find('input[type="checkbox"]').length) {
                    input = $(childs[j]).find('input[type="checkbox"]')
                    input.attr('tabindex', tabindex++)
                    input.attr('name', input.attr('name').replace(/\[[0-9]+\]/g, '[' + i + ']'))
                }
            }
        }
        $('select').select2()
    },

    /**
     * Options wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    optionsWizard: function(el, command, id) {
        var table = $('#' + id),
            tbody = table.find('tbody'),
            parent = $(el).closest('tr'),
            rows = tbody.children(),
            tabindex = tbody.data('tabindex'),
            input, childs, i, j, tr;

        switch (command) {
            case 'copy':
                tr = $('<tr/>')
                childs = parent.children()
                for (i=0; i<childs.length; i++) {
                    var next = $(childs[i]).clone(true).appendTo(tr)
                }
                parent.after(tr)
                break;
            case 'up':
                if (parent.prev('tr').length) {
                   parent.prev('tr').before(parent)
                } else {
                    tbody.append(parent)
                }
                break;
            case 'down':
                if (parent.next('tr').length) {
                    parent.next('tr').after(parent)
                } else {
                    tbody.prepend(parent)
                }
                break;
            case 'delete':
                if (rows.length > 1) {
                    parent.remove();
                }
                break;
        }

        rows = tbody.children()

        for (i=0; i<rows.length; i++) {
            childs = $(rows[i]).children()
            for (j=0; j<childs.length; j++) {
                if ($(childs[j]).find('input').length) {
                    input = $(childs[j]).find('input')
                    input.attr('tabindex', tabindex++)
                    input.attr('name', input.attr('name').replace(/\[[0-9]+\]/g, '[' + i + ']'))
                    if (input.attr('type') == 'checkbox') {
                        input.attr('id', input.attr('name').replace(/\[[0-9]+\]/g, '').replace(/\[/g, '_').replace(/\]/g, '') + '_' + i)
                    }
                }
            }
        }
    },

    /**
     * Key/value wizard
     *
     * @param {object} el      The DOM element
     * @param {string} command The command name
     * @param {string} id      The ID of the target element
     */
    keyValueWizard: function(el, command, id) {
        var table = $('#' + id),
            tbody = table.find('tbody'),
            parent = $(el).closest('tr'),
            rows = tbody.children(),
            tabindex = tbody.data('tabindex'),
            input, childs, i, j, tr;

        Backend.getScrollOffset();

        switch (command) {
            case 'copy':
                tr = $('<tr/>')
                childs = parent.children();
                for (i=0; i<childs.length; i++) {
                    var next = $(childs[i]).clone(true).appendTo(tr)
                    if ($(childs[i]).find('input').length) {
                        input = $(childs[i]).find('input')
                        next.first().val(input.val())
                    }
                }
                parent.after(tr)
                break;
            case 'up':
                if (parent.prev('tr').length) {
                   parent.prev('tr').before(parent)
                } else {
                    tbody.append(parent)
                }
                break;
            case 'down':
                if (parent.next('tr').length) {
                    parent.next('tr').after(parent)
                } else {
                    tbody.prepend(parent)
                }
                break;
            case 'delete':
                if (rows.length > 1) {
                    parent.remove();
                }
                break;
        }

        rows = tbody.children();

        for (i=0; i<rows.length; i++) {
            childs = $(rows[i]).children();
            for (j=0; j<childs.length; j++) {
                if ($(childs[j]).find('input').length) {
                    input = $(childs[j]).find('input')
                    input.attr('tabindex', tabindex++)
                    input.attr('name', input.attr('name').replace(/\[[0-9]+\]/g, '[' + i + ']'))
                }
            }
        }
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

    selectCheckboxRadio: function(el) {
        var input = $(el).find('.actions').find('input[type="checkbox"],input[type="radio"]');

        // Radio buttons
        if (input.attr('type') == 'radio') {
            if (!input.attr('checked')) {
                input.prop('checked', true);
            }
            return;
        }

        // Checkboxes
        if (input.attr('type') == 'checkbox') {

            if (!input.prop('checked')) {
                input.prop('checked', true);
            } else {
                input.prop('checked', false);
            }
            return;
        }
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

    disableCollapseActions: function ()
    {
        $('.listing .collapsible-header').unbind('click').on('click', function (e) {
        })
    },

    enableBadCronstructedCheckboxes: function ()
    {
        var id;
        var i = 0;
        $('input[type=checkbox]:not(.mw_enable)').each(function(index, el) {
            if ($(el).next('label').length == 0) {
                if ($(el).attr('id')) {
                    id = $(el).attr('id')
                }
                else {
                    id = 'checkboxdynamic_'+i
                    i++
                    $(el).attr('id', id)
                }
                $(el).after('<label for="' + id + '"></label>')
            }
        });
    },

    editBadHeaderBack: function ()
    {
        $('.header_back').each(function () {
            $(this).removeClass('header_back');
            $(this).addClass('header-back btn-flat btn-icon waves-effect waves-circle waves-orange tooltipped grey lighten-5');
            $(this).attr('data-tooltip', $(this).attr('title'));
            $(this).html('<i class="material-icons black-text">keyboard_backspace</i>');
        });
    },

    scriptComposer: function ()
    {
        if ($('table.composer_package_list input[name="select"]').length > 0) {
            var inputs = $('table.composer_package_list input[name="select"]')
            inputs.removeAttr('disabled')
            inputs.change(function(event) {
                var names = []
                inputs.each(function(index, el) {
                    if ($(el).is(':checked')) {
                        names.push($(el).val())
                    }
                });
                $('.package_names').val(names.join(','))
                if (names.length) {
                    $('#tl_composer_actions > button').removeAttr('disabled');
                } else {
                    $('#tl_composer_actions > button').attr('disabled', true);
                }
            });
        }

        $('#ctrl_show_dependants').change(function(event) {
            $('tr.dependant').css('display', $(this).is(':checked') ? '' : 'none')
        });

        $('#ctrl_show_dependencies').change(function(event) {
            $('tr.dependency').css('display', $(this).is(':checked') ? '' : 'none')
        });
    },

    initialize: function()
    {
        $('body').addClass('js');

        Backend.hideUnnecessaryToggles();
        Backend.enableToggleSelect();
        Backend.disableCollapseActions();
        Backend.enableBadCronstructedCheckboxes();
        Backend.editBadHeaderBack();

        // Bind events
        $('.js-toggle-subpanel').click(Backend.toggleSubpanel);
        $('.js-toggle-version').click(Backend.toggleVersion);

        $('select:not(.browser-default)').select2();
        $('.tooltipped').tooltip({delay: 50});

        Backend.limitPreviewHeight();
        Backend.initPanels();
        Backend.makeWizardsSortable();

        Backend.addInteractiveHelp();
        $('.tl_tip').mouseenter(function (e) {
            var tip = $(this).html()
            $(this).append('<div class="help-tooltip">' + tip + '</div>')
        }).mouseleave(function() {
            $(this).children('.help-tooltip').remove()
        });

        Backend.scriptComposer();
    },

    fixPrimaryAction: function ()
    {
        scroll = $('body').scrollTop();
        if ($('.card.-main .header-new').length) {
            if (scroll > 280) {
                $('.card.-main .header-new').addClass('fixed');
            } else {
                $('.card.-main .header-new').removeClass('fixed');
            }

        }
    }
}

$(function()
{
    $(".button-collapse").sideNav();
    $('#modules-nav .collapsible-header').click(function(e) { e.preventDefault() });

    Backend.initialize();

    window.onscroll = function()
    {
        Backend.fixPrimaryAction();
    }
})
