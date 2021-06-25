/* global ko _ */
(function () {
    'use strict';

    ko.bindingHandlers.blockLoader = {
        /**
         * Process loader for block
         * @param {String} element
         * @param {Boolean} displayBlockLoader
         */
        update: function (element, displayBlockLoader) {
            if (ko.unwrap(displayBlockLoader())) {
                $(element).spinner(true);
            } else {
                $(element).spinner(false);
            }
        }
    };

    ko.bindingHandlers.i18n = {
        /**
         * @param {Object} element
         * @param {Function} value
         */
        update: function (element, value) {
            $(element).text($.__(ko.unwrap(value() || '')));
        }
    };

    ko.bindingHandlers.bindHtml = {
        /**
         * Scope binding's init method.
         */
        init: function () {
            return {
                controlsDescendantBindings: true
            };
        },

        /**
         * Reads params passed to binding.
         * Set html to node element, apply bindings and call magento attributes parser.
         *
         * @param {HTMLElement} el - Element to apply bindings to.
         * @param {Function} valueAccessor - Function that returns value, passed to binding.
         */
        update: function (el, valueAccessor) {
            var html = ko.utils.unwrapObservable(valueAccessor());

            ko.virtualElements.emptyNode(el);

            if (!_.isNull(html) && !_.isUndefined(html)) {
                if (!_.isString(html)) {
                    html = html.toString();
                }

                el.innerHTML = html;
            }

            ko.utils.arrayForEach(el.childNodes, ko.cleanNode);

            $(el).trigger('contentUpdated');
        }
    };

    ko.bindingHandlers.afterRender = {
        /**
         * Binding init callback.
         */
        init: function (element, valueAccessor, allBindings, viewModel) {
            var callback = valueAccessor();

            if (typeof callback === 'function') {
                callback.call(viewModel, element, viewModel);
            }
        }
    };

    ko.bindingHandlers.mageInit = {
        /**
         * Initializes components assigned to HTML elements.
         *
         * @param {HTMLElement} el
         * @param {Function} valueAccessor
         */
        init: function (el, valueAccessor) {
            var data = valueAccessor();

            _.each(data, function (config, component) {
                window.breeze.mount(component, {
                    settings: config,
                    el: el
                });
            });
        }
    };
})();
