/* global ko _ */
(function () {
    'use strict';

    var methods = [
        'click',
        'select',
        'submit',
        'blur',
        'focus'
    ];

    $.each(methods, function () {
        var method = this;

        /** Native methods proxy */
        $.fn[method] = function () {
            return this.each(function () {
                var event = document.createEvent('Event');

                event.initEvent(method, true, true);

                $(this).trigger(event);

                if (!event.defaultPrevented && method !== 'click') {
                    this[method]();
                }
            });
        };
    });

    /** [isVisible description] */
    function isVisible(i, el) {
        return el.offsetWidth || el.offsetHeight || el.getClientRects().length;
    }

    /** [isVisible description] */
    function isHidden(i, el) {
        return !isVisible(i, el);
    }

    /** Return visible elements */
    $.fn.visible = function () {
        return this.filter(isVisible);
    };

    /** Checks if element is visible */
    $.fn.isVisible = function () {
        return this.visible().length > 0;
    };

    /** Return hidden elements */
    $.fn.hidden = function () {
        return this.filter(isHidden);
    };

    /** Checks if element is hidden */
    $.fn.isHidden = function () {
        return this.hidden().length > 0;
    };

    /** [inViewport description] */
    function inViewport(i, el) {
        var rect = el.getBoundingClientRect();

        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= $(window).height() &&
            rect.right <= $(window).width()
        );
    }

    /** Return elements that are inside viewport */
    $.fn.inViewport = function () {
        return this.filter(inViewport);
    };

    /** Checks if element is inside viewport */
    $.fn.isInViewport = function () {
        return this.inViewport().length > 0;
    };

    /** Toggle block loader on the element */
    $.fn.spinner = function (flag, settings) {
        return this.each(function () {
            if (flag) {
                $.fn.blockLoader().show($(this), settings);
            } else {
                $.fn.blockLoader().hide($(this));
            }
        });
    };

    $.fn.fadeIn = $.fn.show;
    $.fn.fadeOut = $.fn.hide;

    /** Get/Set scroll top position */
    $.fn.scrollTop = function (val) {
        var el = this.get(0);

        if (val === undefined) {
            return el ? el.scrollTop : null;
        }

        if (el) {
            el.scrollTop = val;
        }

        return this;
    };

    /** Evaluates bindings specified in each DOM element of collection. */
    $.fn.applyBindings = function () {
        return this.each(function () {
            ko.applyBindings(ko.contextFor(this), this);
        });
    };

    $.fn.is = _.wrap($.fn.is, function (original, selector) {
        switch (selector) {
            case ':visible':
                return this.isVisible();

            case ':hidden':
                return this.isHidden();

            case ':selected':
                return this.filter(function () {
                    return this.selected;
                }).length > 0;

            case ':checked':
                return this.filter(function () {
                    return this.checked;
                }).length > 0;
        }

        return original.bind(this)(selector);
    });

    $.fn.data = _.wrap($.fn.data, function (original, key) {
        var collection = this,
            result = original.apply(
                this,
                Array.prototype.slice.call(arguments, 1)
            ),
            cleanKey,
            keys = [];

        if (result === undefined) {
            cleanKey = key.replace(/^[^A-Z]+/, ''); // mageSwatchRenderer => SwatchRenderer
            keys = [
                key,
                cleanKey,
                cleanKey.charAt(0).toLowerCase() + cleanKey.slice(1)
            ];

            $.each(keys, function (i, widgetName) {
                if (typeof collection[widgetName] !== 'function') {
                    return;
                }

                result = collection[widgetName]('instance');

                return false;
            });
        }

        return result;
    });

    /** Hover implementation */
    $.fn.hover = function (mouseenter, mouseleave) {
        this.on('mouseenter', mouseenter).on('mouseleave', mouseleave);
    };

    /** Proxy implementation */
    $.proxy = function (fn, ctx) {
        return fn.bind(ctx);
    };

    /** Serialize object to query string */
    $.params = function (object) {
        return Object.keys(object).map(function (key) {
            return key + '=' + object[key];
        }).join('&');
    };

    /** Parse url query params */
    $.parseQuery = function (query) {
        var result = {};

        query = query || window.location.search;
        query = query.replace(/^\?/, '');

        $.each(query.split('&'), function (i, param) {
            var pair = param.split('='),
                key = decodeURIComponent(pair.shift().replace('+', ' ')).toString(),
                value = decodeURIComponent(pair.length ? pair.join('=').replace('+', ' ') : null);

            result[key] = value;
        });

        return result;
    };
})();
