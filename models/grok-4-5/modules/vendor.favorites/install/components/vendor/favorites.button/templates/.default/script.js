(function () {
	'use strict';

	var ACTION_ADD = 'vendor:favorites.favorites.add';
	var ACTION_REMOVE = 'vendor:favorites.favorites.remove';
	var ACTION_STATUS = 'vendor:favorites.favorites.status';

	function VendorFavoritesButton(node, config) {
		this.node = node;
		this.config = BX.type.isPlainObject(config) ? config : {};
		this.productId = parseInt(this.config.productId, 10) || 0;
		this.showCounter = !!this.config.showCounter;
		this.inFavorites = !!this.config.inFavorites;
		this.count = this.config.count == null ? null : parseInt(this.config.count, 10);
		this.busy = false;
		this.labelNode = this.node.querySelector('.vf-fav-btn__label');
		this.counterNode = this.node.querySelector('[data-role="counter"]');

		this.bindEvents();
		this.hydrate();
	}

	VendorFavoritesButton.prototype.bindEvents = function () {
		BX.bind(this.node, 'click', BX.delegate(this.onClick, this));
	};

	VendorFavoritesButton.prototype.hydrate = function () {
		if (!this.productId || typeof BX.ajax.runAction !== 'function') {
			return;
		}

		BX.ajax.runAction(ACTION_STATUS, {
			data: { productId: this.productId }
		}).then(BX.delegate(function (response) {
			var data = response && response.data ? response.data : null;
			if (!data) {
				return;
			}
			this.setState(!!data.inFavorites, data.count);
		}, this)).catch(function () {
			/* keep server-rendered state */
		});
	};

	VendorFavoritesButton.prototype.onClick = function (event) {
		event.preventDefault();

		if (this.busy || !this.productId) {
			return;
		}

		this.busy = true;
		BX.addClass(this.node, 'is-loading');

		var action = this.inFavorites ? ACTION_REMOVE : ACTION_ADD;
		var nextActive = !this.inFavorites;

		BX.ajax.runAction(action, {
			data: { productId: this.productId }
		}).then(BX.delegate(function (response) {
			var data = response && response.data ? response.data : {};
			var inFavorites = typeof data.inFavorites === 'boolean' ? data.inFavorites : nextActive;
			var count = Object.prototype.hasOwnProperty.call(data, 'count') ? data.count : this.count;
			this.playAnimation();
			this.setState(inFavorites, count);
		}, this)).catch(BX.delegate(function (response) {
			var message = 'Не удалось обновить избранное';
			if (response && BX.type.isArray(response.errors) && response.errors[0]) {
				message = response.errors[0].message || message;
			}
			if (window.console && console.warn) {
				console.warn('[vendor.favorites]', message, response);
			}
		}, this)).then(BX.delegate(function () {
			this.busy = false;
			BX.removeClass(this.node, 'is-loading');
		}, this));
	};

	VendorFavoritesButton.prototype.setState = function (inFavorites, count) {
		this.inFavorites = !!inFavorites;

		if (inFavorites) {
			BX.addClass(this.node, 'is-active');
			this.node.setAttribute('aria-pressed', 'true');
			if (this.labelNode) {
				this.labelNode.textContent = 'В избранном';
			}
			this.node.setAttribute('aria-label', 'В избранном');
		} else {
			BX.removeClass(this.node, 'is-active');
			this.node.setAttribute('aria-pressed', 'false');
			if (this.labelNode) {
				this.labelNode.textContent = 'В избранное';
			}
			this.node.setAttribute('aria-label', 'В избранное');
		}

		if (this.showCounter && this.counterNode && count != null && !isNaN(parseInt(count, 10))) {
			this.count = parseInt(count, 10);
			this.counterNode.textContent = String(this.count);
		}
	};

	VendorFavoritesButton.prototype.playAnimation = function () {
		var node = this.node;
		BX.removeClass(node, 'is-animating');
		// force reflow for restart
		void node.offsetWidth;
		BX.addClass(node, 'is-animating');
		setTimeout(function () {
			BX.removeClass(node, 'is-animating');
		}, 500);
	};

	window.VendorFavoritesButton = {
		init: function (buttonId, config) {
			var node = BX(buttonId);
			if (!node || node.dataset.vfInitialized === 'Y') {
				return null;
			}
			node.dataset.vfInitialized = 'Y';
			return new VendorFavoritesButton(node, config);
		}
	};
})();
