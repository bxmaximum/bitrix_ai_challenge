/**
 * JavaScript для компонента "Кнопка Избранное"
 *
 * Обеспечивает:
 * - AJAX-взаимодействие с сервером
 * - Анимации при добавлении/удалении
 * - Обновление состояния кнопки
 * - Чтение cookie для гостей (обход кэширования)
 */

(function (window) {
    'use strict';

    /**
     * Глобальный объект для работы с избранным
     */
    window.VendorFavorites = window.VendorFavorites || {
        instances: {},
        COOKIE_NAME: 'VENDOR_FAVORITES',

        /**
         * Инициализация кнопки
         * @param {string} containerId - ID контейнера кнопки
         */
        init: function (containerId) {
            var container = document.getElementById(containerId);
            if (!container) {
                console.warn('[VendorFavorites] Container not found:', containerId);
                return;
            }

            var button = container.querySelector('.vendor-favorites-btn');
            if (!button) {
                console.warn('[VendorFavorites] Button not found in container:', containerId);
                return;
            }

            var params = window.VendorFavoritesButtons[containerId];
            if (!params) {
                console.warn('[VendorFavorites] Params not found for:', containerId);
                return;
            }

            // Сохраняем инстанс
            this.instances[containerId] = {
                container: container,
                button: button,
                params: params,
                isLoading: false
            };

            // Для гостей проверяем cookie и обновляем состояние
            // Это обходит проблему с кэшированием HTML
            if (!params.isAuthorized) {
                var isInCookie = this._checkCookieFavorites(params.productId);
                if (isInCookie) {
                    this._updateButtonState(button, true);
                }
            }

            // Привязываем обработчик клика
            button.addEventListener('click', this._handleClick.bind(this, containerId));

            // Добавляем поддержку клавиатуры
            button.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    button.click();
                }
            });
        },

        /**
         * Проверяет наличие товара в cookie избранного
         * @param {number} productId
         * @returns {boolean}
         */
        _checkCookieFavorites: function (productId) {
            var favorites = this._getFavoritesFromCookie();
            return favorites.indexOf(productId) !== -1;
        },

        /**
         * Читает список избранного из cookie
         * @returns {number[]}
         */
        _getFavoritesFromCookie: function () {
            var cookieValue = this._getCookie(this.COOKIE_NAME);
            
            if (!cookieValue) {
                return [];
            }

            try {
                // Декодируем base64
                var decoded = atob(cookieValue);
                var data = JSON.parse(decoded);
                
                if (Array.isArray(data)) {
                    return data.map(function(id) { return parseInt(id, 10); });
                }
            } catch (e) {
                // Пробуем без base64 (старый формат)
                try {
                    var data = JSON.parse(cookieValue);
                    if (Array.isArray(data)) {
                        return data.map(function(id) { return parseInt(id, 10); });
                    }
                } catch (e2) {
                    // ignore
                }
            }

            return [];
        },

        /**
         * Получает значение cookie по имени
         * @param {string} name
         * @returns {string|null}
         */
        _getCookie: function (name) {
            // Bitrix добавляет префикс к cookie
            var prefixes = ['BITRIX_SM_', ''];
            
            for (var i = 0; i < prefixes.length; i++) {
                var fullName = prefixes[i] + name;
                var matches = document.cookie.match(new RegExp(
                    '(?:^|; )' + fullName.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'
                ));
                
                if (matches) {
                    return decodeURIComponent(matches[1]);
                }
            }
            
            return null;
        },

        /**
         * Обработчик клика по кнопке
         * @param {string} containerId
         * @param {Event} e
         */
        _handleClick: function (containerId, e) {
            e.preventDefault();

            var instance = this.instances[containerId];
            if (!instance || instance.isLoading) {
                return;
            }

            var isActive = instance.button.dataset.isActive === 'true';
            var productId = parseInt(instance.params.productId, 10);

            if (isActive) {
                this.remove(containerId, productId);
            } else {
                this.add(containerId, productId);
            }
        },

        /**
         * Добавляет товар в избранное
         * @param {string} containerId
         * @param {number} productId
         */
        add: function (containerId, productId) {
            this._sendRequest(containerId, 'vendor:favorites.Favorites.add', {productId: productId});
        },

        /**
         * Удаляет товар из избранного
         * @param {string} containerId
         * @param {number} productId
         */
        remove: function (containerId, productId) {
            this._sendRequest(containerId, 'vendor:favorites.Favorites.remove', {productId: productId});
        },

        /**
         * Отправляет AJAX-запрос
         * @param {string} containerId
         * @param {string} action
         * @param {Object} data
         */
        _sendRequest: function (containerId, action, data) {
            var instance = this.instances[containerId];
            if (!instance) {
                return;
            }

            var self = this;
            instance.isLoading = true;
            this._setLoadingState(instance.button, true);
            this._triggerRipple(instance.button);

            // Функция для сброса состояния загрузки
            var resetLoading = function () {
                instance.isLoading = false;
                self._setLoadingState(instance.button, false);
            };

            // Используем BX.ajax.runAction если доступен, иначе fetch
            if (typeof BX !== 'undefined' && BX.ajax && BX.ajax.runAction) {
                BX.ajax.runAction(action, {
                    data: data
                }).then(function (response) {
                    self._handleSuccess(containerId, response.data);
                    resetLoading();
                }).catch(function (response) {
                    self._handleError(containerId, response.errors);
                    resetLoading();
                });
            } else {
                // Fallback на fetch
                this._fetchRequest(containerId, action, data);
            }
        },

        /**
         * Fallback запрос через fetch
         */
        _fetchRequest: function (containerId, action, data) {
            var instance = this.instances[containerId];
            var self = this;
            var sessid = instance.params.sessid || '';

            var url = '/bitrix/services/main/ajax.php?action=' + encodeURIComponent(action);
            var formData = new FormData();
            formData.append('sessid', sessid);

            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    formData.append(key, data[key]);
                }
            }

            fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (result.status === 'success') {
                        self._handleSuccess(containerId, result.data);
                    } else {
                        self._handleError(containerId, result.errors);
                    }
                })
                .catch(function (error) {
                    self._handleError(containerId, [{message: error.message || 'Network error'}]);
                })
                .finally(function () {
                    instance.isLoading = false;
                    self._setLoadingState(instance.button, false);
                });
        },

        /**
         * Обработка успешного ответа
         */
        _handleSuccess: function (containerId, data) {
            var instance = this.instances[containerId];
            if (!instance) {
                return;
            }

            var isActive = data.isInFavorites;
            this._updateButtonState(instance.button, isActive);

            // Запускаем анимацию сердцебиения при добавлении
            if (isActive) {
                this._triggerHeartbeat(instance.button);
            }

            // Обновляем все кнопки для этого товара
            this._updateAllButtons(instance.params.productId, isActive);

            // Вызываем кастомное событие
            this._dispatchEvent('vendorFavoritesChange', {
                productId: instance.params.productId,
                isInFavorites: isActive,
                totalCount: data.count
            });
        },

        /**
         * Обработка ошибки
         */
        _handleError: function (containerId, errors) {
            var instance = this.instances[containerId];
            if (!instance) {
                return;
            }

            var message = 'Произошла ошибка';
            if (errors && errors.length > 0) {
                message = errors[0].message || message;
            }

            console.error('[VendorFavorites] Error:', message);

            // Вызываем кастомное событие
            this._dispatchEvent('vendorFavoritesError', {
                productId: instance.params.productId,
                message: message
            });
        },

        /**
         * Обновляет состояние кнопки
         */
        _updateButtonState: function (button, isActive) {
            button.dataset.isActive = isActive ? 'true' : 'false';

            if (isActive) {
                button.classList.add('vendor-favorites-btn--active');
                button.setAttribute('aria-label', 'Удалить из избранного');
                button.setAttribute('title', 'Удалить из избранного');
            } else {
                button.classList.remove('vendor-favorites-btn--active');
                button.setAttribute('aria-label', 'Добавить в избранное');
                button.setAttribute('title', 'Добавить в избранное');
            }
        },

        /**
         * Устанавливает состояние загрузки
         */
        _setLoadingState: function (button, isLoading) {
            if (isLoading) {
                button.classList.add('vendor-favorites-btn--loading');
                button.disabled = true;
            } else {
                button.classList.remove('vendor-favorites-btn--loading');
                button.disabled = false;
            }
        },

        /**
         * Запускает ripple-эффект
         */
        _triggerRipple: function (button) {
            button.classList.remove('vendor-favorites-btn--ripple');
            void button.offsetWidth;
            button.classList.add('vendor-favorites-btn--ripple');

            setTimeout(function () {
                button.classList.remove('vendor-favorites-btn--ripple');
            }, 600);
        },

        /**
         * Запускает анимацию сердцебиения
         */
        _triggerHeartbeat: function (button) {
            button.classList.remove('vendor-favorites-btn--heartbeat');
            void button.offsetWidth;
            button.classList.add('vendor-favorites-btn--heartbeat');

            setTimeout(function () {
                button.classList.remove('vendor-favorites-btn--heartbeat');
            }, 600);
        },

        /**
         * Обновляет все кнопки для данного товара
         */
        _updateAllButtons: function (productId, isActive) {
            var self = this;
            Object.keys(this.instances).forEach(function (containerId) {
                var instance = self.instances[containerId];
                if (instance.params.productId === productId) {
                    self._updateButtonState(instance.button, isActive);
                }
            });
        },

        /**
         * Вызывает кастомное событие
         */
        _dispatchEvent: function (eventName, detail) {
            var event;
            if (typeof CustomEvent === 'function') {
                event = new CustomEvent(eventName, {detail: detail, bubbles: true});
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent(eventName, true, true, detail);
            }
            document.dispatchEvent(event);
        },

        /**
         * Проверяет, в избранном ли товар
         * @param {number} productId
         * @returns {boolean|null}
         */
        isInFavorites: function (productId) {
            for (var containerId in this.instances) {
                if (this.instances[containerId].params.productId === productId) {
                    return this.instances[containerId].button.dataset.isActive === 'true';
                }
            }
            return null;
        }
    };

})(window);
