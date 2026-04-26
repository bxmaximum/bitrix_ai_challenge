(function (BX) {
    'use strict';

    BX.VendorFavoritesButton = function (options) {
        this.button = BX(options.buttonId);
        this.addTitle = options.addTitle;
        this.removeTitle = options.removeTitle;
        this.errorTitle = options.errorTitle;

        if (!this.button) {
            return;
        }

        BX.bind(this.button, 'click', BX.proxy(this.handleClick, this));
    };

    BX.VendorFavoritesButton.prototype = {
        handleClick: function () {
            if (BX.hasClass(this.button, 'is-loading')) {
                return;
            }

            var productId = Number(this.button.getAttribute('data-product-id'));
            var isActive = this.button.getAttribute('data-active') === 'Y';
            var action = isActive ? 'vendor:favorites.favorites.remove' : 'vendor:favorites.favorites.add';
            var finish = BX.proxy(function () {
                BX.removeClass(this.button, 'is-loading');
            }, this);

            BX.addClass(this.button, 'is-loading');

            BX.ajax.runAction(action, {
                data: {
                    productId: productId
                }
            }).then(
                BX.proxy(function (response) {
                    this.applyState(response.data || {});
                    finish();
                }, this),
                BX.proxy(function () {
                    this.showError();
                    finish();
                }, this)
            );
        },

        applyState: function (state) {
            var isFavorite = state.isFavorite === true;
            var title = isFavorite ? this.removeTitle : this.addTitle;
            var text = this.button.querySelector('.vf-button__text');

            this.button.setAttribute('data-active', isFavorite ? 'Y' : 'N');
            this.button.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
            this.button.setAttribute('aria-label', title);
            if (isFavorite) {
                BX.addClass(this.button, 'is-active');
            } else {
                BX.removeClass(this.button, 'is-active');
            }
            BX.addClass(this.button, 'is-pulse');

            if (text) {
                text.textContent = title;
            }

            this.updateCounter(state);

            setTimeout(BX.proxy(function () {
                BX.removeClass(this.button, 'is-pulse');
            }, this), 320);
        },

        updateCounter: function (state) {
            if (!state || typeof state.counter === 'undefined') {
                return;
            }

            var value = String(Number(state.counter) || 0);
            var productId = this.button.getAttribute('data-product-id');
            var buttons = document.querySelectorAll('[data-product-id="' + productId + '"]');

            this.setCounterValue(this.button, value);

            for (var i = 0; i < buttons.length; i++) {
                this.setCounterValue(buttons[i], value);
            }
        },

        setCounterValue: function (button, value) {
            if (!button) {
                return;
            }

            var counters = button.querySelectorAll('.vf-button__counter, [data-role="counter"], [data-counter]');
            for (var i = 0; i < counters.length; i++) {
                counters[i].textContent = value;
                counters[i].innerHTML = value;
            }
        },

        showError: function () {
            this.button.setAttribute('title', this.errorTitle);
            BX.addClass(this.button, 'is-error');

            setTimeout(BX.proxy(function () {
                this.button.removeAttribute('title');
                BX.removeClass(this.button, 'is-error');
            }, this), 2200);
        }
    };
})(window.BX);
