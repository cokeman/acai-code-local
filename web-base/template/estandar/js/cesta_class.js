class Cesta {
    /**
     * Returns the object from local storage
     */
    static getFromLocalStorage() {
        if (!localStorage.getItem('cesta')) localStorage.setItem('cesta', '{}');
        return JSON.parse(localStorage.getItem('cesta'));
    }

    /**
     * Adds 1 unit of the product
     * @param productID
     * @param varID
     */
    static add(productID, varID = 0, showAlert = false) {
        const products = Cesta.getFromLocalStorage();
        if (products[productID]) {
            if (products[productID][varID]) {
                products[productID][varID]++;
            }
            else {
                products[productID][varID] = 1;
            }
        }
        else {
            products[productID] = {};
            products[productID][varID] = 1;
        }
        Cesta.save(products);
        Cesta.updateBadge();
        if (showAlert) {
            swal(TEXTOS_CESTA.productoAnadido, TEXTOS_CESTA.textoProductoAnadido, 'success',{
                buttons: {
                    cancel: TEXTOS_CESTA.cancel,
                    ok: {
                        text: TEXTOS_CESTA.ok,
                        value: 'ok'
                    }
                }
            })
                .then((value) => {
                if (value === 'ok') {
                    window.location.href = ENLACE_CESTA;
                }
            });
        }
    }

    /**
     * Substracts 1 unit of the product
     * @param productID
     */
    static substract(productID) {
        const products = Cesta.getFromLocalStorage();
        if (products[productID]) {
            products[productID].quantity--;
            if (products[productID].quantity === 0) {
                delete products[productID];
            }
        }
        Cesta.save(products);
        Cesta.updateBadge();
    }

    static setQuantity(productID, varID = 0, quantity = 1) {
        let products = Cesta.getFromLocalStorage();
        if (!products[productID] || !products[productID][varID]) {
            Cesta.add(productID, varID, false);
            products = Cesta.getFromLocalStorage();
        }
        products[productID][varID] = Math.max(quantity, 1);
        Cesta.save(products);
        Cesta.updateBadge();
    }

    /**
     * Removes a product from the basket
     * @param productID
     */
    static remove(productID) {
        const products = Cesta.getFromLocalStorage();
        delete products[productID];
        console.log(productID, products);
        Cesta.save(products);
        Cesta.updateBadge();
    }

    /**
     * Clears the basket
     */
    static clear() {
        Cesta.save({});
        Cesta.updateBadge();
    }

    /**
     * Saves the new object to the local storage
     * @param products
     */
    static save(products) {
        localStorage.setItem('cesta', JSON.stringify(products));
    }


    /**
     * Returns a promise with the products from the server
     */
    static getProducts(sessID = '') {
        const timestamp = (new Date()).getTime();
        const products = Cesta.getFromLocalStorage();
        let gastosDeEnvio = document.querySelector('select[name=gastos_de_envio]');
        if (gastosDeEnvio) {
            gastosDeEnvio = gastosDeEnvio.value;
        }
        else {
            gastosDeEnvio = 0;
        }

        let codigoDescuento = document.querySelector('#cesta .descuento input');
        if (codigoDescuento) {
            codigoDescuento = codigoDescuento.value;
        }
        else {
            codigoDescuento = '';
        }

        return fetch(`${RUTA_RAIZ}/cesta.php?getProducts=1&cesta=${JSON.stringify(products)}&gastos=${gastosDeEnvio}&codigo=${codigoDescuento}&timestamp=${timestamp}&sessID=${sessID}`)
            .then((data) => {
            return data.json();
        });
    }

    /**
     * Looks for all the "Añadir a la cesta" buttons and bind them to add a product
     */
    static bindNodes() {
        const nodes = document.querySelectorAll('.add-to-basket');
        if (nodes) {
            nodes.forEach(function(node) {
                node.removeEventListener('click', Cesta.bindNode);
                node.addEventListener('click', Cesta.bindNode);
            });
        }
    }

    static bindNode(e) {
        const node = this;
        const productID = parseInt(node.getAttribute('data-producto'));
        const varID = parseInt(node.getAttribute('data-variacion'));
        Cesta.add(productID, varID, true);
    }

    static updateBadge() {
        let count = 0;
        const products = Cesta.getFromLocalStorage();
        for (const key in products) {
            for (const v in products[key]) {
                count += products[key][v];
            }
        }
        let badges = document.querySelectorAll('.cesta-count');
        if (badges) {
            badges.forEach(function(badge) {
                badge.innerHTML = `(${count})`;
            });
        }
    }
}

class Favorites {
    static getFromLocalStorage() {
        if (!localStorage.getItem('favoritos')) localStorage.setItem('favoritos', '{}');
        return JSON.parse(localStorage.getItem('favoritos'));
    }

    /**
     * Saves the new object to the local storage
     * @param products
     */
    static save(products) {
        localStorage.setItem('favoritos', JSON.stringify(products));
    }

    /**
     * Adds or removes from the local storage
     */
    static toggle(productID, node = null) {
        const products = Favorites.getFromLocalStorage();
        if (products[productID]) {
            delete products[productID];
            Favorites.changeClass(node, false);
        }
        else {
            products[productID] = true;
            Favorites.changeClass(node, true);
        }
        Favorites.save(products);
        Favorites.updateBadge();
    }

    /**
     * Returns if the product is favorite or not
     */
    static isFavorite(productID) {
        const products = Favorites.getFromLocalStorage();
        return products[productID] ? true : false;
    }

    /**
     * Updates the class of all the buttons of the same product
     */
    static changeClass(node, active = true) {
        if (!node) return;
        if (active) {
            node.classList.add('active');
            node.querySelector('i').classList.remove('fa-heart-o');
            node.querySelector('i').classList.add('fa-heart');
        }
        else {
            node.classList.remove('active');
            node.querySelector('i').classList.remove('fa-heart');
            node.querySelector('i').classList.add('fa-heart-o');
        }
    }

    /**
     * Returns a promise with the products from the server
     */
    static getProducts() {
        const products = Favorites.getFromLocalStorage();

        return fetch(`${RUTA_RAIZ}/favoritos.php?getProducts=1&favoritos=${JSON.stringify(products)}`)
            .then((data) => {
            return data.json();
        });
    }


    /**
     * Looks for all the "Añadir a la cesta" buttons and bind them to add a product
     */
    static bindNodes() {
        const nodes = document.querySelectorAll('.add-to-favorites');
        if (nodes) {
            nodes.forEach(function(node) {
                const productID = node.getAttribute('data-producto');
                Favorites.changeClass(node, Favorites.isFavorite(productID));
                node.removeEventListener('click', Favorites.bindNode);
                node.addEventListener('click', Favorites.bindNode);
            });
        }
        Favorites.updateBadge();
    }

    static bindNode(e) {
        let node = this;
        while (node && !node.classList.contains('add-to-favorites')) {
            node = node.parentNode;
        }

        if (!node) return;

        const productID = parseInt(node.getAttribute('data-producto'));
        Favorites.toggle(productID, node);
    }

    static updateBadge() {
        let count = 0;
        const products = Favorites.getFromLocalStorage();
        for (const key in products) {
            count++;
        }
        let badges = document.querySelectorAll('.favoritos-count');
        if (badges) {
            badges.forEach(function(badge) {
                badge.innerHTML = `(${count})`;
            });
        }
    }
}
