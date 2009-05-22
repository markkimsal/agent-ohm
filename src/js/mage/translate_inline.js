/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Js
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

var TranslateInline = Class.create();
TranslateInline.prototype = {
    initialize: function(trigEl, ajaxUrl, area){
        this.ajaxUrl = ajaxUrl;
        this.area = area;

        this.trigTimer = null;
        this.trigContentEl = null;

        $$('*[translate]').each(this.initializeElement.bind(this));
        var scope = this;
        Ajax.Responders.register({onComplete: function() {setTimeout(scope.reinitElements.bind(scope), 50)}});
        this.trigEl = $(trigEl);
        this.trigEl.observe('mouseover', this.trigHideClear.bind(this));
        this.trigEl.observe('mouseout', this.trigHideDelayed.bind(this));
        this.trigEl.observe('click', this.formShow.bind(this));

        this.helperDiv = document.createElement('div');
    },

    initializeElement: function(el) {
        if(!el.initializedTranslate) {
            el.addClassName('translate-inline');
            el.initializedTranslate = true;
            Event.observe(el, 'mouseover', this.trigShow.bind(this, el));
            Event.observe(el, 'mouseout', this.trigHideDelayed.bind(this));
        }
    },

    reinitElements: function (el) {
        $$('*[translate]').each(this.initializeElement.bind(this));
    },

    trigShow: function (el) {
        this.trigHideClear();

        var p = Element.cumulativeOffset(el);

        this.trigEl.style.left = p[0]+'px';
        this.trigEl.style.top = p[1]+'px';
        this.trigEl.style.display = 'block';

        this.trigContentEl = el;
    },

    trigHide: function() {
        this.trigEl.style.display = 'none';
        this.trigContentEl = null;
    },

    trigHideDelayed: function () {
        this.trigTimer = window.setTimeout(this.trigHide.bind(this), 500);
    },

    trigHideClear: function() {
        clearInterval(this.trigTimer);
    },

    formShow: function () {
        if (this.formIsShown) {
            return;
        }
        this.formIsShown = true;

        var el = this.trigContentEl;
        if (!el) {
            return;
        }

        eval('var data = '+el.getAttribute('translate'));

        var content = '<form id="translate-inline-form">';
        var t = new Template(
            '<div class="magento_table_container"><table cellspacing="0">'+
            '<tr><td class="label">Location: </td><td class="value">#{location}</td></tr>'+
            '<tr><td class="label">Scope: </td><td class="value">#{scope}</td></tr>'+
            '<tr><td class="label">Shown: </td><td class="value">#{shown_escape}</td></tr>'+
            '<tr><td class="label">Original: </td><td class="value">#{original_escape}</td></tr>'+
            '<tr><td class="label">Translated: </td><td class="value">#{translated_escape}</td></tr>'+
            '<tr><td class="label"><label for="perstore_#{i}">Store View Specific:</label> </td><td class="value">'+
                '<input id="perstore_#{i}" name="translate[#{i}][perstore]" type="checkbox" value="1"/>'+
            '</td></tr>'+
            '<tr><td class="label"><label for="custom_#{i}">Custom:</label> </td><td class="value">'+
                '<input name="translate[#{i}][original]" type="hidden" value="#{scope}::#{original_escape}"/>'+
                '<input id="custom_#{i}" name="translate[#{i}][custom]" class="input-text" value="#{translated_escape}"/>'+
            '</td></tr>'+
            '</table></div>'
        );
        for (i=0; i<data.length; i++) {
            data[i]['i'] = i;
            data[i]['shown_escape'] = this.escapeHTML(data[i]['shown']);
            data[i]['translated_escape'] = this.escapeHTML(data[i]['translated']);
            data[i]['original_escape'] = this.escapeHTML(data[i]['original']);
            content += t.evaluate(data[i]);
        }
        content += '</form><p class="a-center accent">Please refresh the page to see your changes after submitting this form.</p>';

        this.overlayShowEffectOptions = Windows.overlayShowEffectOptions;
        this.overlayHideEffectOptions = Windows.overlayHideEffectOptions;
        Windows.overlayShowEffectOptions = {duration:0};
        Windows.overlayHideEffectOptions = {duration:0};

        Dialog.confirm(content, {
            draggable:true,
            resizable:true,
            closable:true,
            className:"magento",
            title:"Translation",
            width:500,
            height:400,
            zIndex:1000,
            recenterAuto:false,
            hideEffect:Element.hide,
            showEffect:Element.show,
            id:"translate-inline",
            buttonClass:"form-button",
            okLabel:"Submit",
            ok: this.formOk.bind(this),
            cancel: this.formClose.bind(this),
            onClose: this.formClose.bind(this)
        });
    },

    formOk: function(win) {
        if (this.formIsSubmitted) {
            return;
        }
        this.formIsSubmitted = true;

        var inputs = $('translate-inline-form').getInputs(), parameters = {};
        for (var i=0; i<inputs.length; i++) {
            if (inputs[i].type == 'checkbox') {
                if (inputs[i].checked) {
                    parameters[inputs[i].name] = inputs[i].value;
                }
            }
            else {
                parameters[inputs[i].name] = inputs[i].value;
            }
        }
        parameters['area'] = this.area;

        new Ajax.Request(this.ajaxUrl, {
            method:'post',
            parameters:parameters,
            onComplete:this.ajaxComplete.bind(this, win)
        });

        this.formIsSubmitted = false;
    },

    ajaxComplete: function(win, transport) {
        win.close();
        this.formClose(win);
    },

    formClose: function(win) {
        Windows.overlayShowEffectOptions = this.overlayShowEffectOptions;
        Windows.overlayHideEffectOptions = this.overlayHideEffectOptions;
        this.formIsShown = false;
    },

    escapeHTML: function (str) {
       this.helperDiv.innerHTML = '';
       var text = document.createTextNode(str);
       this.helperDiv.appendChild(text);
       var escaped = this.helperDiv.innerHTML;
       escaped = escaped.replace(/"/g, '&quot;');
       return escaped;
    }
}
