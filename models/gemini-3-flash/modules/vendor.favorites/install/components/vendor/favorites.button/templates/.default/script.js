BX.ready(function() {
    const favoriteButtons = document.querySelectorAll('.fav-btn-container');

    favoriteButtons.forEach(container => {
        const btn = container.querySelector('.fav-btn');
        const productId = container.dataset.productId;

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const isFavorite = container.dataset.isFavorite === 'Y';
            const action = isFavorite ? 'vendor:favorites.Favorites.remove' : 'vendor:favorites.Favorites.add';

            btn.classList.add('is-loading');

            BX.ajax.runAction(action, {
                data: {
                    productId: productId
                }
            }).then(response => {
                btn.classList.remove('is-loading');
                if (response.status === 'success') {
                    const newStatus = !isFavorite;
                    container.dataset.isFavorite = newStatus ? 'Y' : 'N';
                    
                    if (newStatus) {
                        btn.classList.add('is-active');
                        btn.setAttribute('aria-label', 'Удалить из избранного');
                    } else {
                        btn.classList.remove('is-active');
                        btn.setAttribute('aria-label', 'Добавить в избранное');
                    }
                }
            }).catch(error => {
                btn.classList.remove('is-loading');
                console.error('Favorites error:', error);
                // Optionally show some UI error
            });
        });
    });
});

