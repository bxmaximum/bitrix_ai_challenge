(function () {
    'use strict';

    function parseConfig(root) {
        try {
            return JSON.parse(root.getAttribute('data-config') || '{}');
        } catch (e) {
            return {};
        }
    }

    function applyState(root, btn, isFavorite) {
        root.setAttribute('data-is-favorite', isFavorite ? 'true' : 'false');
        btn.classList.toggle('vf-favorites__btn--active', isFavorite);
        btn.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
    }

    function updateCounter(root, count) {
        var config = parseConfig(root);
        if (!config.showCounter) {
            return;
        }

        var counter = root.querySelector('[data-vf-counter]');
        if (counter) {
            counter.textContent = String(Math.max(0, parseInt(count, 10) || 0));
        }
    }

    function collectProductIds() {
        var ids = [];

        document.querySelectorAll('[data-vf-favorites]').forEach(function (root) {
            var productId = parseInt(root.getAttribute('data-product-id'), 10);
            if (productId > 0) {
                ids.push(productId);
            }
        });

        return ids;
    }

    function setState(root, btn, isFavorite) {
        applyState(root, btn, isFavorite);
        btn.classList.add('vf-favorites__btn--pulse');
        window.setTimeout(function () {
            btn.classList.remove('vf-favorites__btn--pulse');
        }, 450);
    }

    /** Синхронизация с cookie/БД после загрузки (обход кеша карточки товара). */
    function syncFavoritesFromServer() {
        var productIds = collectProductIds();

        BX.ajax.runAction('vendor:favorites.favorites.list', {}).then(function (response) {
            var ids = response.data && Array.isArray(response.data.ids) ? response.data.ids : [];
            var idSet = {};

            ids.forEach(function (id) {
                idSet[parseInt(id, 10)] = true;
            });

            document.querySelectorAll('[data-vf-favorites]').forEach(function (root) {
                var btn = root.querySelector('[data-vf-toggle]');
                if (!btn) {
                    return;
                }

                var productId = parseInt(root.getAttribute('data-product-id'), 10);
                applyState(root, btn, !!idSet[productId]);
            });

            if (productIds.length === 0) {
                return;
            }

            BX.ajax.runAction('vendor:favorites.favorites.getCounts', {
                data: { productIds: productIds },
            }).then(function (countsResponse) {
                var counts = countsResponse.data && countsResponse.data.counts
                    ? countsResponse.data.counts
                    : {};

                document.querySelectorAll('[data-vf-favorites]').forEach(function (root) {
                    var productId = String(parseInt(root.getAttribute('data-product-id'), 10));
                    if (counts[productId] !== undefined) {
                        updateCounter(root, counts[productId]);
                    }
                });
            });
        });
    }

    function toggleFavorite(root) {
        var btn = root.querySelector('[data-vf-toggle]');
        if (!btn || btn.classList.contains('vf-favorites__btn--loading')) {
            return;
        }

        var config = parseConfig(root);
        var productId = parseInt(root.getAttribute('data-product-id'), 10);
        var isFavorite = root.getAttribute('data-is-favorite') === 'true';
        var action = isFavorite ? 'vendor:favorites.favorites.remove' : 'vendor:favorites.favorites.add';

        btn.classList.add('vf-favorites__btn--loading');

        function clearLoading() {
            btn.classList.remove('vf-favorites__btn--loading');
        }

        BX.ajax.runAction(action, {
            data: { productId: productId },
        }).then(function (response) {
            var nextState = response.data && typeof response.data.isFavorite === 'boolean'
                ? response.data.isFavorite
                : !isFavorite;

            setState(root, btn, nextState);

            if (response.data && response.data.favoriteCount != null) {
                updateCounter(root, response.data.favoriteCount);
            } else if (config.showCounter) {
                var counter = root.querySelector('[data-vf-counter]');
                if (counter) {
                    var current = parseInt(counter.textContent, 10) || 0;
                    updateCounter(root, nextState ? current + 1 : Math.max(0, current - 1));
                }
            }

            clearLoading();
        }, function () {
            if (BX.UI && BX.UI.Notification) {
                BX.UI.Notification.Center.notify({ content: 'Не удалось обновить избранное' });
            } else {
                console.error('Favorites toggle failed');
            }

            clearLoading();
        });
    }

    function initRoot(root) {
        var btn = root.querySelector('[data-vf-toggle]');
        if (!btn) {
            return;
        }
        btn.addEventListener('click', function () {
            toggleFavorite(root);
        });
    }

    function init() {
        document.querySelectorAll('[data-vf-favorites]').forEach(initRoot);
        syncFavoritesFromServer();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
