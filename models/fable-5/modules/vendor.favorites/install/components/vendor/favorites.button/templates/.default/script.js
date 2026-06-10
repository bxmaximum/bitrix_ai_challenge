/**
 * vendor:favorites.button — AJAX-кнопка избранного.
 *
 * Состояние кнопки гидрируется на клиенте через action `status`:
 * страница может отдаваться из композитного/компонентного кэша,
 * поэтому серверной отрисовке состояния доверять нельзя.
 *
 * Работает через BX.ajax.runAction (CSRF-токен подставляется автоматически):
 * - vendor:favorites.Favorites.status
 * - vendor:favorites.Favorites.add
 * - vendor:favorites.Favorites.remove
 */
(function (window) {
    'use strict';

    window.BX = window.BX || {};
    BX.Vendor = BX.Vendor || {};

    if (BX.Vendor.FavoritesButton) {
        return;
    }

    /**
     * @param {string} buttonId DOM id кнопки
     * @constructor
     */
    BX.Vendor.FavoritesButton = function (buttonId) {
        this.button = document.getElementById(buttonId);
        if (!this.button) {
            return;
        }

        this.productId = parseInt(this.button.dataset.productId, 10);
        this.counter = this.button.querySelector('.vendor-favorites-btn__counter');
        this.loading = false;

        this.button.addEventListener('click', this.toggle.bind(this));
        this.hydrate();
    };

    BX.Vendor.FavoritesButton.prototype = {
        isActive: function () {
            return this.button.classList.contains('is-active');
        },

        /**
         * Актуализирует состояние после загрузки страницы
         * (HTML мог прийти из общего кэша).
         */
        hydrate: function () {
            if (!this.productId) {
                return;
            }

            BX.ajax.runAction('vendor:favorites.Favorites.status', {
                data: { productId: this.productId }
            }).then(function (response) {
                var data = response.data || {};
                this.applyState(!!data.inFavorites, data.count);
            }.bind(this), function () {});
        },

        toggle: function () {
            if (this.loading || !this.productId) {
                return;
            }

            var active = this.isActive();
            var action = active
                ? 'vendor:favorites.Favorites.remove'
                : 'vendor:favorites.Favorites.add';

            this.setLoading(true);

            BX.ajax.runAction(action, {
                data: { productId: this.productId }
            }).then(
                this.onToggleSuccess.bind(this, !active),
                this.onError.bind(this)
            );
        },

        onToggleSuccess: function (active, response) {
            this.setLoading(false);

            var data = response.data || {};
            this.applyState(active, data.count);
            this.animate();
        },

        applyState: function (active, count) {
            this.button.classList.toggle('is-active', active);
            this.button.setAttribute('aria-pressed', active ? 'true' : 'false');
            this.button.title = active
                ? this.button.dataset.titleRemove
                : this.button.dataset.titleAdd;

            if (this.counter && typeof count !== 'undefined') {
                this.counter.textContent = count;
            }
        },

        onError: function (response) {
            this.setLoading(false);

            var errors = (response && response.errors) || [];
            if (errors.length && window.console) {
                console.error('vendor.favorites: ' + errors.map(function (e) {
                    return e.message;
                }).join('; '));
            }
        },

        animate: function () {
            var button = this.button;
            button.classList.remove('is-animating');
            // Перезапуск CSS-анимации
            void button.offsetWidth;
            button.classList.add('is-animating');

            setTimeout(function () {
                button.classList.remove('is-animating');
            }, 500);
        },

        setLoading: function (state) {
            this.loading = state;
            this.button.classList.toggle('is-loading', state);
        }
    };
})(window);
