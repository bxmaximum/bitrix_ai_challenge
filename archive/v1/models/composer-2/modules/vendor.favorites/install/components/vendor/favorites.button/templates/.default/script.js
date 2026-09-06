(function () {
    'use strict';

    function applyVisualState(wrap, btn, inFavorites, count) {
        wrap.setAttribute('data-in-favorites', inFavorites ? '1' : '0');
        btn.setAttribute('aria-pressed', inFavorites ? 'true' : 'false');
        btn.setAttribute('aria-label', inFavorites ? 'Удалить из избранного' : 'Добавить в избранное');
        if (inFavorites) {
            btn.classList.add('vendor-fav-btn--active');
        } else {
            btn.classList.remove('vendor-fav-btn--active');
        }
        var counter = btn.querySelector('.vendor-fav-btn__counter');
        if (counter && typeof count === 'number') {
            counter.textContent = String(count);
        }
        if (typeof count === 'number') {
            wrap.setAttribute('data-count', String(count));
        }
    }

    function runPulse(btn) {
        btn.classList.remove('vendor-fav-btn--pulse');
        void btn.offsetWidth;
        btn.classList.add('vendor-fav-btn--pulse');
    }

    function attachClick(wrap, btn, actionBase, productId, showCounter) {
        btn.addEventListener('click', function () {
            var inFavorites = wrap.getAttribute('data-in-favorites') === '1';
            var action = inFavorites ? actionBase + '.remove' : actionBase + '.add';
            if (typeof BX === 'undefined' || !BX.ajax || !BX.ajax.runAction) {
                return;
            }
            BX.ajax.runAction(action, { data: { productId: productId } }).then(function (response) {
                if (!response || !response.data) {
                    return;
                }
                var ids = response.data.ids || [];
                var idNum = parseInt(productId, 10);
                var nowIn = false;
                for (var i = 0; i < ids.length; i++) {
                    if (parseInt(ids[i], 10) === idNum) {
                        nowIn = true;
                        break;
                    }
                }
                applyVisualState(wrap, btn, nowIn, showCounter ? ids.length : undefined);
                runPulse(btn);
            });
        });
    }

    function boot() {
        var wraps = document.querySelectorAll('.vendor-fav-wrap');
        if (!wraps.length) {
            return;
        }

        var needSync = false;
        Array.prototype.forEach.call(wraps, function (w) {
            if (w.getAttribute('data-sync-on-client') === '1') {
                needSync = true;
            }
        });

        var actionBase = wraps[0].getAttribute('data-action-base') || '';

        Array.prototype.forEach.call(wraps, function (wrap) {
            var btn = wrap.querySelector('.vendor-fav-btn');
            if (!btn) {
                return;
            }
            var productId = parseInt(wrap.getAttribute('data-product-id'), 10);
            var showCounter = wrap.getAttribute('data-show-counter') === '1';
            attachClick(wrap, btn, actionBase, productId, showCounter);
        });

        if (!needSync || !actionBase || typeof BX === 'undefined' || !BX.ajax || !BX.ajax.runAction) {
            Array.prototype.forEach.call(wraps, function (w) {
                w.classList.remove('vendor-fav-wrap--pending');
            });
            return;
        }

        BX.ajax.runAction(actionBase + '.list', {}).then(function (response) {
            var ids = (response && response.data && response.data.ids) ? response.data.ids : [];
            var idSet = {};
            for (var j = 0; j < ids.length; j++) {
                idSet[parseInt(ids[j], 10)] = true;
            }
            var total = ids.length;
            Array.prototype.forEach.call(wraps, function (wrap) {
                var btn = wrap.querySelector('.vendor-fav-btn');
                if (!btn) {
                    return;
                }
                var pid = parseInt(wrap.getAttribute('data-product-id'), 10);
                var showCounter = wrap.getAttribute('data-show-counter') === '1';
                applyVisualState(wrap, btn, !!idSet[pid], showCounter ? total : undefined);
                wrap.classList.remove('vendor-fav-wrap--pending');
            });
        }).catch(function () {
            Array.prototype.forEach.call(wraps, function (w) {
                w.classList.remove('vendor-fav-wrap--pending');
            });
        });
    }

    if (typeof BX !== 'undefined' && BX.ready) {
        BX.ready(boot);
    } else {
        if (document.readyState !== 'loading') {
            boot();
        } else {
            document.addEventListener('DOMContentLoaded', boot);
        }
    }
})();
