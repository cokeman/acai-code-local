/******** MODAL BUSQUEDA ********/
var searchButton = document.getElementById('searchButton');
var searchModal = document.getElementById('modalBusqueda');
var firstSearchFocus = searchModal.querySelector('.cerrar');
var lastSearchFocus = searchModal.querySelector('.btn');
var searchList = document.getElementById('lista-busqueda');
var searchListUl = searchList.querySelector('ul');
var searchInput = searchModal.querySelector('.form-control');
searchButton.addEventListener('click', function (e) {
    showSearchModal();
});

firstSearchFocus.addEventListener('click', function () {
    closeSearchModal();
});

function showSearchModal() {
    document.querySelector('body').classList.add('no-scroll');
    searchModal.classList.add('active');
    searchInput.focus();
    searchModal.addEventListener('keydown', trapFocus);
    searchModal.querySelector('input.form-control').addEventListener('input', handleSearchWrite);
    searchModal.querySelector('select').addEventListener('change', handleSearchWrite);
}

function trapFocus(e) {
    switch (e.keyCode) {
        case 9:
            if (e.shiftKey) {
                if (document.activeElement === firstSearchFocus)  {
                    e.preventDefault();
                    lastSearchFocus.focus();
                }
            } else {
                if (document.activeElement === lastSearchFocus) {
                    e.preventDefault();
                    firstSearchFocus.focus();
                }
            }
            break;
        case 27:
            closeSearchModal();
        default:
            break;
    }
}

function handleSearchWrite(e) {
    if (searchInput.value == "") {
        searchModal.classList.remove('writing');
        searchList.classList.add('hidden');
    } else {
        searchModal.classList.add('writing');
        searchList.classList.remove('hidden');
        searchListUl.innerHTML = '';
        var orderBy = searchModal.querySelector('select[name=order]').value;
        fetch(`/productos.php?q=${searchInput.value}&order=${orderBy}`)
            .then((data) => {
                return data.json()
            })
            .then((json) => {
                if (json.query !== searchInput.value) return;
                if (json.mensaje) {
                    searchList.querySelector('.message').innerHTML = json.mensaje;
                } else {
                    searchList.querySelector('.message').innerHTML = '';
                }
                searchListUl.innerHTML = json.html;
            })
            .catch((error) => {
                console.log(error);
            });
    }
}

function closeSearchModal() {
    document.querySelector('body').classList.remove('no-scroll');
    searchModal.classList.remove('active');
    searchButton.focus();
    searchModal.removeEventListener('keydown', trapFocus);
    searchModal.querySelector('.form-control').removeEventListener('input', handleSearchWrite);
}
