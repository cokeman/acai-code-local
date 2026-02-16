document.addEventListener("DOMContentLoaded", function () {
    createVueApp_multidiomas_v2();
})
function createVueApp_multidiomas_v2() {
    return new Vue({
        el: '#app',
        data() {
            return {
                basePrice: 140,
                pluginPurchased: false,
                toggledPrice: true,
                idiomas: [
                    // {
                    //     "prefix": "es",
                    //     "name": "Español",
                    //     "checked": false
                    // }
                ],
            }
        },
        mounted: async function () {
            await this.updatePluginConfig();
            await this.verifyPluginPurchased();
            await this.getFullLanguages();
        },
        computed: {
            idiomas_selected: function () {
                return this.idiomas.filter(item => item.checked)
            },
            totalPrice: function () {
                let total = this.idiomas_selected.reduce((aux, producto) => {
                    return parseFloat(aux) + parseFloat(producto.precio);
                }, 0);
                if (!this.pluginPurchased) total = total + this.basePrice;
                return total;
            }
        },
        methods: {
            updatePluginConfig: async function () {
                fetch('/admin.php?menu=multiidiomas&action=updatePluginConfig', {
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    // body: 'keywords=' + keywords.join(','),
                    method: 'POST'
                })
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) {
                            console.log(json)
                        }
                        document.getElementById("loading").style.opacity = 0;
                        document.getElementById("loading").style.pointerEvents = "none";
                    });
            },
            getFullLanguages: async function () {
                document.getElementById("loading").style.opacity = 1;
                document.getElementById("loading").style.pointerEvents = "all";
                fetch('/admin.php?menu=multiidiomas&action=listIdiomasWebsite', {
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    // body: 'keywords=' + keywords.join(','),
                    method: 'POST'
                })
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) {
                            console.log(json)
                            this.idiomas = json.data;

                        }
                        document.getElementById("loading").style.opacity = 0;
                        document.getElementById("loading").style.pointerEvents = "none";
                    });
            },
            verifyPluginPurchased: async function () {
                fetch('/admin.php?menu=multiidiomas&action=verifyPluginPurchased', {
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    // body: 'keywords=' + keywords.join(','),
                    method: 'POST'
                })
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) {
                            this.pluginPurchased = json.data;
                        }
                    });
            },

            togglePriceBlock: function () {
                this.$refs.priceBlock.classList.toggle('hidePriceBlock')
            },
            selectIdioma: function (index) {
                // this.idiomas[index].checked = !this.idiomas[index].checked
                this.$set(this.idiomas[index], "checked", !this.idiomas[index].checked);
                // this.idiomas[index].checked = Object.assign({}, this.idiomas[index].checked,true)

                console.log(this.idiomas[index].name, this.idiomas[index].checked)

            },
            
            pay: function () {
                swal.fire({
                    title: "¿Deseas realizar la compra?",
                    html: "<p>La página del proveedor seguro Stripe se abrirá en una nueva ventana para que puedas realizar el pago. Una vez completado nuestro equipo se pondrá manos a la obra para solicitar el alta y se pondrá en contacto contigo para informarte de todos los avances.</p>",
                    showDenyButton: true,
                    confirmButtonText: "Continuar",
                    denyButtonText: `Volver atrás`,
                    confirmButton: "btn btn-success",

                }).then((value) => {
                    
                    if (value.isConfirmed) {
                        console.log(value);
                        const idiomas = this.idiomas_selected.map(i => i.num).join(",");
                        document.getElementById("loading").style.opacity = 1;
                        document.getElementById("loading").style.pointerEvents = "all";
                        fetch(`admin.php?menu=multiidiomas&action=payLink&idiomas=` + idiomas, {
                            method: "get"
                        }).then((json) => {
                            return json.json();
                        }).then((json) => {
                            if (json.data[0]) {
                                document.getElementById("loading").style.opacity = 0;
                                document.getElementById("loading").style.pointerEvents = "none";
                                window.location.href = json.data[0];
                            }
                        });

                        return true;
                    } else {
                        return false;
                    }
                });
            }

        },
        filters: {
            price(val) {
                return new Intl.NumberFormat('es-ES', {
                    style: 'currency',
                    currency: 'EUR'
                }).format(val);
            }
        }
    });
}
