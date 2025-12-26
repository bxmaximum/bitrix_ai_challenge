(() => {
  const runAction = (action, data) => {
    if (!window.BX || !BX.ajax || !BX.ajax.runAction) {
      return Promise.reject(new Error('BX.ajax.runAction is not available'));
    }
    return BX.ajax.runAction(action, { data });
  };

  let idsPromise = null;
  const fetchIdsOnce = () => {
    if (idsPromise) return idsPromise;
    idsPromise = runAction('vendor:favorites.favorites.list', {})
      .then((res) => (res && res.data && Array.isArray(res.data.ids) ? res.data.ids : []))
      .catch(() => []);
    return idsPromise;
  };

  const updateCounter = (btn, ids) => {
    const show = btn.dataset.showCounter === 'Y';
    if (!show) return;
    const counterEl = btn.querySelector('.vendor-favorites-btn__counter');
    if (!counterEl) return;
    counterEl.textContent = String(Array.isArray(ids) ? ids.length : 0);
  };

  const setState = (btn, isActive) => {
    btn.classList.toggle('is-active', isActive);
    btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    btn.setAttribute('aria-label', isActive ? 'Удалить из избранного' : 'Добавить в избранное');
  };

  const pulse = (btn) => {
    btn.classList.remove('is-pulse');
    // force reflow
    void btn.offsetWidth;
    btn.classList.add('is-pulse');
    window.setTimeout(() => btn.classList.remove('is-pulse'), 520);
  };

  const initButton = (btn) => {
    const productId = parseInt(btn.dataset.productId || '0', 10);
    if (!productId) return;

    // Ensure correct initial state even if parent component output is cached.
    fetchIdsOnce().then((ids) => {
      setState(btn, Array.isArray(ids) && ids.includes(productId));
      updateCounter(btn, ids);
    });

    btn.addEventListener('click', async () => {
      const isActive = btn.classList.contains('is-active');
      const action = isActive ? btn.dataset.actionRemove : btn.dataset.actionAdd;

      btn.classList.add('is-loading');
      try {
        const res = await runAction(action, { productId });
        const ids = (res && res.data && res.data.ids) ? res.data.ids : [];
        setState(btn, !isActive);
        updateCounter(btn, ids);
        // keep global state in sync for other buttons on page
        idsPromise = Promise.resolve(Array.isArray(ids) ? ids : []);
        pulse(btn);
      } catch (e) {
        // fallback: do not change state
        if (window.console && console.error) {
          console.error(e);
        }
      } finally {
        btn.classList.remove('is-loading');
      }
    });
  };

  if (window.BX && BX.ready) {
    BX.ready(() => {
      document.querySelectorAll('.vendor-favorites-btn').forEach(initButton);
    });
  } else {
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.vendor-favorites-btn').forEach(initButton);
    });
  }
})();


