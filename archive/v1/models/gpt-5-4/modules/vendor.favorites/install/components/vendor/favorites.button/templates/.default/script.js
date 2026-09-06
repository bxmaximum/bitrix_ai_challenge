;(function (window) {
    'use strict';

    if (window.VendorFavoritesButton) {
        return;
    }

    function Button(rootId, options) {
        this.root = document.getElementById(rootId);
        this.options = options || {};
        this.button = this.root ? this.root.querySelector('.vfavorites-button') : null;
        this.label = this.root ? this.root.querySelector('.vfavorites-button__label') : null;
        this.counter = this.root ? this.root.querySelector('[data-role="counter"]') : null;
        this.isBusy = false;
    }

    Button.prototype.bind = function () {
        if (!this.root || !this.button || this.button.disabled) {
            return;
        }

        this.button.addEventListener('click', this.handleClick.bind(this));
        this.syncState();
    };

    Button.prototype.handleClick = function (event) {
        var action;
        var finalize;

        event.preventDefault();

        if (this.isBusy) {
            return;
        }

        action = this.options.isFavorite ? this.options.actionRemove : this.options.actionAdd;
        if (!action) {
            return;
        }

        this.isBusy = true;
        this.button.classList.add('is-loading');
        finalize = function () {
            this.isBusy = false;
            this.button.classList.remove('is-loading');
        }.bind(this);

        BX.ajax.runAction(action, {
            data: {
                productId: this.options.productId,
                sessid: this.options.sessid
            }
        }).then(function (response) {
            this.applyState(response.data || {});
            finalize();
        }.bind(this)).catch(function (response) {
            console.error('Favorites action failed', response);
            finalize();
        });
    };

    Button.prototype.applyState = function (state) {
        this.options.isFavorite = !!state.isFavorite;
        this.button.classList.toggle('is-active', this.options.isFavorite);
        this.button.classList.add('is-burst');
        this.button.setAttribute('aria-pressed', this.options.isFavorite ? 'true' : 'false');

        if (this.label) {
            this.label.textContent = this.options.isFavorite ? 'В избранном' : 'В избранное';
        }

        if (this.counter && typeof state.totalCount !== 'undefined') {
            this.counter.textContent = String(state.totalCount);
        }

        window.setTimeout(function () {
            this.button.classList.remove('is-burst');
        }.bind(this), 260);
    };

    Button.prototype.syncState = function () {
        var action = this.options.actionList;

        if (!action) {
            return;
        }

        BX.ajax.runAction(action, {
            data: {}
        }).then(function (response) {
            var data = response.data || {};
            var productIds = data.productIds || [];

            this.applyState({
                isFavorite: productIds.indexOf(this.options.productId) !== -1,
                totalCount: typeof data.count !== 'undefined' ? data.count : productIds.length
            });
        }.bind(this)).catch(function (response) {
            console.error('Favorites state sync failed', response);
        });
    };

    window.VendorFavoritesButton = {
        init: function (rootId, options) {
            var instance = new Button(rootId, options);
            instance.bind();

            return instance;
        }
    };
})(window);
