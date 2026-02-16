<template>
    <div class="relative">

        <!-- ═══════════════════════════════════════════ -->
        <!-- MODO 1: TOGGLE SEGMENTADO (exactamente 2 opciones) -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-if="displayMode === 'toggle'"
            ref="toggleContainer"
            class="acai-toggle relative inline-flex items-center rounded-full cursor-pointer select-none"
            role="switch"
            :aria-checked="isToggleOn"
            :aria-label="fieldConfig ? fieldConfig.label || field : field"
            tabindex="0"
            @click="toggleSwitch"
            @keydown.enter.prevent="toggleSwitch"
            @keydown.space.prevent="toggleSwitch">

            <!-- Pill deslizante -->
            <span ref="togglePill" class="acai-toggle-pill absolute rounded-full"></span>

            <!-- Textos -->
            <span v-for="(opt, idx) in allRealOptions" :key="'toggle-opt-' + idx"
                :ref="'toggleOpt' + idx"
                class="acai-toggle-option relative flex items-center justify-center"
                :class="getToggleOptionClass(idx)">
                <div v-if="getToggleIcon(idx)" class="toggle-icon flex-shrink-0" style="margin-right: 6px;" v-html="getToggleIcon(idx)"></div>
                <div class="font-light leading-none">{{ opt.label }}</div>
            </span>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- MODO 2: COLOR SELECT -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-else-if="displayMode === 'color'" v-click-outside="closeDropdown">
            <div class="p-3 w-full bg-gray-200 border-gray-600 border-2 rounded-lg shadow cursor-pointer flex items-center justify-between transition-colors duration-200"
                 :style="{
                     backgroundColor: currentColor || '#e5e7eb',
                     color: currentTextColor,
                     fontWeight: currentColor ? '600' : '400'
                 }"
                 @click="open = !open">
                <span>{{ currentLabel || 'Seleccionar color...' }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon w-6 h-6 transition-transform duration-200"
                     :class="{ 'rotate-180': open }"
                     :style="{ color: currentTextColor }"
                     width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
            <div v-show="open" class="absolute z-50 w-full mt-1 border-2 border-gray-600 rounded-lg shadow-lg overflow-hidden bg-white">
                <div v-for="(opt, idx) in realOptions"
                     :key="idx"
                     class="px-3 py-2 cursor-pointer flex items-center transition-all duration-150 border-b border-gray-200 last:border-b-0"
                     :style="getColorOptionStyle(opt.value)"
                     @mouseenter="hovered = opt.value"
                     @mouseleave="hovered = null"
                     @click="selectOption(opt.value)">
                    <span class="w-6 h-6 rounded border border-gray-300 mr-3 flex-shrink-0 transition-transform duration-150"
                          :style="{
                              backgroundColor: getSwatchColor(opt.value),
                              transform: hovered === opt.value ? 'scale(1.2)' : 'scale(1)',
                              borderColor: hovered === opt.value ? '#9ca3af' : '#d1d5db'
                          }"></span>
                    <span>{{ opt.label }}</span>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- MODO 3: SELECT NORMAL -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-else>
            <vue-select
                :multiple="multiple"
                :class="['border-2 border-gray-600 rounded-lg', { 'multiple': multiple }]"
                v-model="selected_values"
                :options="tableRecords"
                :label="labelKey"
                :reduce="(option) => option[valueKey]"
                :clearable="false"
                :filterable="isFilterable"
                :loading="loading"
                @input="onInput"
                @search="onSearch"
                placeholder="Seleccionar..."
            >
                <template #no-options="{ search }">
                    <span v-if="search">No se encontraron resultados para "<b>{{ search }}</b>"</span>
                    <span v-else>No hay opciones disponibles</span>
                </template>
                <template #list-footer v-if="isPaginated && totalRecords > limit">
                    <li class="pagination flex justify-between px-3 py-2 border-t border-gray-300 bg-gray-100">
                        <button class="text-sm px-2 py-1 rounded" :class="hasPrevPage ? 'text-blue-600 hover:bg-blue-50 cursor-pointer' : 'text-gray-400 cursor-not-allowed'" :disabled="!hasPrevPage" @click.prevent="offset -= limit">← Anterior</button>
                        <span class="text-xs text-gray-500 self-center">{{ offset + 1 }}-{{ Math.min(offset + limit, totalRecords) }} de {{ totalRecords }}</span>
                        <button class="text-sm px-2 py-1 rounded" :class="hasNextPage ? 'text-blue-600 hover:bg-blue-50 cursor-pointer' : 'text-gray-400 cursor-not-allowed'" :disabled="!hasNextPage" @click.prevent="offset += limit">Siguiente →</button>
                    </li>
                </template>
            </vue-select>
        </div>

    </div>
</template>

<script>
    module.exports = {
        components: {
            "vue-select": VueSelect.VueSelect,
        },
        directives: {
            'click-outside': {
                bind: function(el, binding) {
                    el._clickOutside = function(event) {
                        if (!(el === event.target || el.contains(event.target))) {
                            binding.value();
                        }
                    };
                    document.addEventListener('click', el._clickOutside);
                },
                unbind: function(el) {
                    if (el._clickOutside) {
                        document.removeEventListener('click', el._clickOutside);
                        delete el._clickOutside;
                    }
                }
            }
        },
        props: {
            builder: { type: Object, required: true },
            data: { type: Object, required: true },
            field: { type: String, required: true },
            toggleIcons: { type: Object, default: null },
        },
        data: function() {
            return {
                selected_values: '',
                currentLabel: '',
                loading: false,
                tableRecords: [],
                search: '',
                offset: 0,
                limit: 20,
                cmsColors: null,
                loadingColors: false,
                currentColor: '',
                currentTextColor: '#111827',
                open: false,
                hovered: null,
                _visibilityObserver: null,
                // ── Mapa completo de colores con nombre → hex ──
                // Incluye: main colors (resueltos via CMS), nombres ES/EN,
                // y paleta Tailwind v3/v4 con variantes de nombre personalizadas
                colorNameMap: {
                    // ── Main colors (se sobreescriben con valores reales del CMS) ──
                    'main color': '#6366f1',
                    'main color light': '#818cf8',
                    'main color dark': '#4f46e5',
                    // ── Blanco / Negro ──
                    'blanco': '#ffffff',
                    'white': '#ffffff',
                    'negro': '#000000',
                    'black': '#000000',
                    // ── Grises (gray - Tailwind) ──
                    'gris': '#6b7280',
                    'gray': '#6b7280',
                    'gris claro': '#9ca3af',
                    'gris light': '#9ca3af',
                    'light gray': '#9ca3af',
                    'gris oscuro': '#374151',
                    'gris dark': '#374151',
                    'dark gray': '#374151',
                    'gris muy claro': '#d1d5db',
                    'gris muy oscuro': '#1f2937',
                    // ── Grises cálidos (neutral - Tailwind) ──
                    'gris calido': '#737373',
                    'gris calido light': '#a3a3a3',
                    'gris calido dark': '#525252',
                    'gris calido muy claro': '#d4d4d4',
                    'gris calido muy oscuro': '#262626',
                    'warm gray': '#737373',
                    // ── Grises neutros (zinc - Tailwind) ──
                    'gris neutro': '#71717a',
                    'gris neutro light': '#a1a1aa',
                    'gris neutro dark': '#52525b',
                    'zinc': '#71717a',
                    // ── Grises fríos (slate - Tailwind) ──
                    'gris frio': '#64748b',
                    'gris frio light': '#94a3b8',
                    'gris frio dark': '#475569',
                    'slate': '#64748b',
                    // ── Grises piedra (stone - Tailwind) ──
                    'gris piedra': '#78716c',
                    'gris piedra light': '#a8a29e',
                    'gris piedra dark': '#57534e',
                    'stone': '#78716c',
                    // ── Rojos ──
                    'rojo': '#ef4444',
                    'rojo claro': '#f87171',
                    'rojo oscuro': '#dc2626',
                    'red': '#ef4444',
                    // ── Naranjas ──
                    'naranja': '#f97316',
                    'naranja claro': '#fb923c',
                    'naranja oscuro': '#ea580c',
                    'orange': '#f97316',
                    // ── Amarillos ──
                    'amarillo': '#eab308',
                    'amarillo claro': '#facc15',
                    'amarillo oscuro': '#ca8a04',
                    'yellow': '#eab308',
                    // ── Verdes ──
                    'verde': '#22c55e',
                    'verde claro': '#4ade80',
                    'verde oscuro': '#16a34a',
                    'green': '#22c55e',
                    // ── Azules ──
                    'azul': '#3b82f6',
                    'azul claro': '#60a5fa',
                    'azul oscuro': '#2563eb',
                    'blue': '#3b82f6',
                    // ── Índigos ──
                    'indigo': '#6366f1',
                    'indigo claro': '#818cf8',
                    'indigo oscuro': '#4f46e5',
                    // ── Violetas / Morados ──
                    'violeta': '#8b5cf6',
                    'morado': '#8b5cf6',
                    'violet': '#8b5cf6',
                    'purple': '#a855f7',
                    // ── Rosas ──
                    'rosa': '#ec4899',
                    'rosa claro': '#f472b6',
                    'rosa oscuro': '#db2777',
                    'pink': '#ec4899',
                    // ── Fucsias ──
                    'fucsia': '#d946ef',
                    'fuchsia': '#d946ef',
                    // ── Cianes / Turquesas ──
                    'cian': '#06b6d4',
                    'cyan': '#06b6d4',
                    'turquesa': '#14b8a6',
                    'teal': '#14b8a6',
                    // ── Esmeraldas ──
                    'esmeralda': '#10b981',
                    'emerald': '#10b981',
                    // ── Limas ──
                    'lima': '#84cc16',
                    'lime': '#84cc16',
                    // ── Ámbar ──
                    'ambar': '#f59e0b',
                    'amber': '#f59e0b',
                    // ── Cielo ──
                    'cielo': '#0ea5e9',
                    'sky': '#0ea5e9',
                    // ── Transparente ──
                    'transparente': 'transparent',
                    'transparent': 'transparent',
                }
            };
        },
        created: function() {
            this.initFieldValue();
            this.loadOptions();
        },
        mounted: function() {
            var self = this;
            this.$nextTick(function() {
                self.positionPill(false);
                // ── IntersectionObserver ──
                // Reposiciona la pill cuando el toggle pasa de oculto a visible
                // (resuelve el problema de toggles en tabs inactivos con offsetWidth = 0)
                if (self.$refs.toggleContainer) {
                    self._visibilityObserver = new IntersectionObserver(function(entries) {
                        if (entries[0].isIntersecting) {
                            self.positionPill(false);
                        }
                    }, { threshold: 0.1 });
                    self._visibilityObserver.observe(self.$refs.toggleContainer);
                }
            });
        },
        beforeDestroy: function() {
            if (this._visibilityObserver) {
                this._visibilityObserver.disconnect();
                this._visibilityObserver = null;
            }
        },
        computed: {
            fieldConfig: function() { return this.builder.vars[this.field]; },
            fieldOptions: function() { return this.fieldConfig.options.builder_custom; },
            multiple: function() { return !!this.fieldConfig.multi; },
            tableName: function() { return this.fieldOptions.tableName || null; },
            querySQL: function() { return this.fieldOptions.query || null; },
            isRemote: function() { return !!(this.tableName || this.querySQL); },
            isPaginated: function() { return this.isRemote; },
            isFilterable: function() { return this.tableRecords.length > 10; },
            realOptions: function() {
                if (this.isRemote) return [];
                var internalKeys = ['tableName', 'fieldLabel', 'fieldValue', 'query'];
                var opts = [];
                var entries = Object.entries(this.fieldOptions);
                for (var i = 0; i < entries.length; i++) {
                    if (internalKeys.indexOf(entries[i][0]) === -1) {
                        opts.push({ value: entries[i][0], label: entries[i][1] });
                    }
                }
                var emptyIdx = -1;
                for (var j = 0; j < opts.length; j++) {
                    if (opts[j].value === '') { emptyIdx = j; break; }
                }
                if (emptyIdx > 0) {
                    var emptyOpt = opts.splice(emptyIdx, 1)[0];
                    opts.unshift(emptyOpt);
                }
                return opts;
            },
            allRealOptions: function() { return this.realOptions; },
            displayMode: function() {
                if (this.isRemote || this.multiple) return 'select';
                var opts = this.allRealOptions;
                if (opts.length >= 2 && this.isColorOptions(opts)) return 'color';
                if (opts.length === 2) return 'toggle';
                return 'select';
            },
            isToggleOn: function() {
                if (this.allRealOptions.length < 2) return false;
                var currentVal = this.data[this.field].newValues.builder_custom.value;
                return currentVal === this.allRealOptions[1].value;
            },
            labelKey: function() {
                if (this.querySQL) return this.tableRecords.length > 0 ? Object.keys(this.tableRecords[0])[1] || 'label' : 'label';
                if (this.tableName) return this.fieldOptions.fieldLabel || 'label';
                return 'label';
            },
            valueKey: function() {
                if (this.querySQL) return this.tableRecords.length > 0 ? Object.keys(this.tableRecords[0])[0] || 'value' : 'value';
                if (this.tableName) return this.fieldOptions.fieldValue || 'value';
                return 'value';
            },
            totalRecords: function() { return this.tableRecords.length; },
            hasNextPage: function() { return (this.offset + this.limit) < this.totalRecords; },
            hasPrevPage: function() { return this.offset > 0; },
        },
        methods: {

            initFieldValue: function() {
                var fieldData = this.data[this.field];
                if (typeof fieldData.newValues.builder_custom.value === 'undefined') {
                    Vue.set(fieldData.newValues.builder_custom, 'value', '');
                }
            },

            loadOptions: function() {
                if (this.fieldConfig.type !== 'list') return;
                if (this.tableName) { this.loadFromTable(); }
                else if (this.querySQL) { this.loadFromQuery(); }
                else { this.loadManualOptions(); }
                this.syncSelectedValue();
                this.updateCurrentLabel();
                if (this.displayMode === 'color') { this.loadColors(); }
            },

            loadFromTable: function() {
                var self = this;
                this.loading = true;
                Rest.get(this.tableName)
                    .then(function(r) { self.tableRecords = self.prependEmpty(r.data || []); })
                    .catch(function(e) { console.error('[acai-vue-selectv2] Error:', e); self.tableRecords = []; })
                    .finally(function() { self.loading = false; self.syncSelectedValue(); });
            },

            loadFromQuery: function() {
                var self = this;
                this.loading = true;
                Rest.query(this.querySQL)
                    .then(function(r) {
                        if (!r || !r.data || !r.data[0]) { self.tableRecords = []; return; }
                        self.tableRecords = self.prependEmpty(r.data);
                    })
                    .catch(function(e) { console.error('[acai-vue-selectv2] Error:', e); self.tableRecords = []; })
                    .finally(function() { self.loading = false; self.syncSelectedValue(); });
            },

            loadManualOptions: function() {
                var internalKeys = ['tableName', 'fieldLabel', 'fieldValue', 'query'];
                var entries = Object.entries(this.fieldOptions);
                this.tableRecords = [];
                for (var i = 0; i < entries.length; i++) {
                    if (internalKeys.indexOf(entries[i][0]) === -1) {
                        this.tableRecords.push({ label: entries[i][1], value: entries[i][0] });
                    }
                }
                if (!this.multiple) {
                    this.tableRecords.unshift({ label: '(Sin valor asignado)', value: '' });
                }
            },

            prependEmpty: function(records) {
                if (this.multiple || !records.length) return records;
                var empty = {};
                empty[this.labelKey] = '(Sin valor asignado)';
                empty[this.valueKey] = '';
                return [empty].concat(records);
            },

            syncSelectedValue: function() {
                var currentValue = this.data[this.field].newValues.builder_custom.value;
                this.selected_values = this.multiple
                    ? this.parseMultiValue(currentValue)
                    : (currentValue != null ? currentValue : '');
            },

            parseMultiValue: function(value) {
                if (!value || String(value).trim() === '') return [];
                return String(value).trim().split("\t").filter(function(v) { return v !== ''; });
            },

            updateCurrentLabel: function() {
                var val = this.data[this.field].newValues.builder_custom.value;
                this.currentLabel = this.fieldOptions[val] || '';
            },

            saveField: function(value) {
                this.data[this.field].newValues.builder_custom.value = value;
                this.data[this.field].value = value;
                this.updateCurrentLabel();
                if (this.displayMode === 'color') { this.updateCurrentColor(); }
                this.$emit("save-data");
            },

            // ══════════════════════════════════════
            // MODO TOGGLE
            // ══════════════════════════════════════

            positionPill: function(animate) {
                var pill = this.$refs.togglePill;
                if (!pill) return;

                var container = this.$refs.toggleContainer;
                if (!container) return;

                var opt0Ref = this.$refs['toggleOpt0'];
                var opt1Ref = this.$refs['toggleOpt1'];
                var opt0 = Array.isArray(opt0Ref) ? opt0Ref[0] : opt0Ref;
                var opt1 = Array.isArray(opt1Ref) ? opt1Ref[0] : opt1Ref;
                if (!opt0 || !opt1) return;

                var clientW = container.clientWidth;
                var clientH = container.clientHeight;

                // Si el contenedor no es visible aún, salir sin posicionar
                // (el IntersectionObserver lo reintentará cuando sea visible)
                if (clientW === 0 || clientH === 0) return;

                var activeIdx = this.isToggleOn ? 1 : 0;
                var hasIcon = this.getToggleIcon(activeIdx) !== null;
                var extra = hasIcon ? 4 : 0;

                var pillLeft, pillWidth;

                if (this.isToggleOn) {
                    pillWidth = opt1.offsetWidth + extra;
                    pillLeft = clientW - pillWidth;
                    if (pillLeft < 0) pillLeft = 0;
                } else {
                    pillWidth = opt0.offsetWidth + extra;
                    pillLeft = 0;
                    if (pillWidth > clientW) pillWidth = clientW;
                }

                pill.style.width = pillWidth + 'px';
                pill.style.height = clientH + 'px';
                pill.style.top = '0px';

                if (animate) {
                    pill.style.transition = 'left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                } else {
                    pill.style.transition = 'none';
                }

                pill.style.left = pillLeft + 'px';
            },

            toggleSwitch: function() {
                var opts = this.allRealOptions;
                if (opts.length < 2) return;
                var newValue = this.isToggleOn ? opts[0].value : opts[1].value;
                this.saveField(newValue);
                var self = this;
                this.$nextTick(function() {
                    self.positionPill(true);
                });
            },

            getToggleOptionClass: function(idx) {
                var isActive = (idx === 0 && !this.isToggleOn) || (idx === 1 && this.isToggleOn);
                return isActive ? 'acai-toggle-option--active' : 'acai-toggle-option--inactive';
            },

            getToggleIcon: function(index) {
                if (!this.toggleIcons) return null;
                var opt = this.allRealOptions[index];
                if (!opt) return null;
                return this.toggleIcons[opt.value] || this.toggleIcons[index] || null;
            },

            // ══════════════════════════════════════
            // MODO COLOR
            // ══════════════════════════════════════

            /**
             * Determina si un conjunto de opciones representa colores.
             * Detecta: colorNameMap keys, hex (#xxx/#xxxxxx), rgb/rgba, hsl/hsla.
             * Activa modo color si al menos la mitad de las opciones son colores.
             */
            isColorOptions: function(opts) {
                var hexRegex = /^#[0-9a-f]{3,8}$/i;
                var rgbRegex = /^rgba?\s*\(/i;
                var hslRegex = /^hsla?\s*\(/i;
                var map = this.colorNameMap;
                var colorCount = 0;

                for (var i = 0; i < opts.length; i++) {
                    var l = opts[i].label.toLowerCase().trim();
                    // Vacío = opción por defecto, la contamos como color
                    if (l === '') { colorCount++; continue; }
                    // Buscar en el mapa de nombres
                    if (map[l] !== undefined) { colorCount++; continue; }
                    // Hex
                    if (hexRegex.test(l)) { colorCount++; continue; }
                    // RGB / RGBA
                    if (rgbRegex.test(l)) { colorCount++; continue; }
                    // HSL / HSLA
                    if (hslRegex.test(l)) { colorCount++; continue; }
                }
                return colorCount >= Math.ceil(opts.length / 2);
            },

            /**
             * Resuelve el hex real de una label de color.
             * Prioridad: CMS colors (main) > colorNameMap > hex/rgb directo > fallback gris.
             */
            resolveColorHex: function(label) {
                var l = (typeof label === 'string') ? label.toLowerCase().trim() : '';
                if (!l) return '';

                // 1) Hex directo
                if (/^#[0-9a-f]{3,8}$/i.test(l)) return l;

                // 2) RGB directo → convertir a hex
                var rgbMatch = l.match(/^rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
                if (rgbMatch) {
                    var r = parseInt(rgbMatch[1]), g = parseInt(rgbMatch[2]), b = parseInt(rgbMatch[3]);
                    return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
                }

                // 3) Mapa de nombres
                if (this.colorNameMap[l] !== undefined) return this.colorNameMap[l];

                return '';
            },

            /**
             * Carga los colores reales del CMS (main color, light, dark)
             * y construye el mapa cmsColors con hex reales para cada opción.
             */
            loadColors: function() {
                var self = this;
                this.loadingColors = true;
                try {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', '/admin.php?menu=configuracion_tienda&action=edit&num=1', true);
                    xhr.onload = function() {
                        var doc = new DOMParser().parseFromString(xhr.responseText, 'text/html');
                        var principal = doc.querySelector('input[name="color_principal"]');
                        var claro = doc.querySelector('input[name="color_principal_claro"]');
                        var oscuro = doc.querySelector('input[name="color_principal_oscuro"]');

                        // Sobreescribir main colors en el mapa con valores reales del CMS
                        if (principal && principal.value) self.colorNameMap['main color'] = principal.value;
                        if (claro && claro.value) self.colorNameMap['main color light'] = claro.value;
                        if (oscuro && oscuro.value) self.colorNameMap['main color dark'] = oscuro.value;

                        // Construir mapa de valor → hex para cada opción
                        self.cmsColors = {};
                        var entries = Object.entries(self.fieldOptions);
                        var internalKeys = ['tableName', 'fieldLabel', 'fieldValue', 'query'];
                        for (var i = 0; i < entries.length; i++) {
                            var val = entries[i][0], label = entries[i][1];
                            if (internalKeys.indexOf(val) !== -1) continue;
                            var resolved = self.resolveColorHex(label);
                            if (resolved) {
                                self.cmsColors[val] = resolved;
                            }
                        }

                        self.updateCurrentColor();
                        self.loadingColors = false;
                    };
                    xhr.onerror = function() { console.error('[acai-vue-selectv2] Error de red'); self.loadingColors = false; };
                    xhr.send();
                } catch (e) { console.error('[acai-vue-selectv2] Error:', e); this.loadingColors = false; }
            },

            updateCurrentColor: function() {
                if (!this.cmsColors) { this.currentColor = ''; this.currentTextColor = '#111827'; return; }
                var val = this.data[this.field].newValues.builder_custom.value;
                if (this.cmsColors[val] !== undefined) {
                    this.currentColor = this.cmsColors[val];
                    this.currentTextColor = this.isLightColor(this.currentColor) ? '#111827' : '#ffffff';
                } else { this.currentColor = ''; this.currentTextColor = '#111827'; }
            },

            isLightColor: function(hex) {
                if (!hex || hex === 'transparent') return true;
                var clean = hex.replace('#', '');
                var full = clean.length === 3 ? clean[0]+clean[0]+clean[1]+clean[1]+clean[2]+clean[2] : clean;
                var r = parseInt(full.substring(0, 2), 16);
                var g = parseInt(full.substring(2, 4), 16);
                var b = parseInt(full.substring(4, 6), 16);
                return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.5;
            },

            getSwatchColor: function(value) {
                return (this.cmsColors && this.cmsColors[value] !== undefined) ? this.cmsColors[value] : '#e5e7eb';
            },

            selectOption: function(value) { this.open = false; this.saveField(value); },
            closeDropdown: function() { this.open = false; },

            getColorOptionStyle: function(value) {
                var currentVal = this.data[this.field].newValues.builder_custom.value;
                var isSelected = value === currentVal;
                var isHovered = this.hovered === value;
                return {
                    backgroundColor: isSelected ? '#e5e7eb' : (isHovered ? '#f3f4f6' : '#ffffff'),
                    color: '#111827',
                    fontWeight: isSelected ? '600' : '400',
                    transition: 'all 0.2s ease',
                };
            },

            onInput: function() {
                var value = this.multiple
                    ? (this.selected_values.length === 0 ? '' : ['', ...this.selected_values, ''].join("\t"))
                    : (this.selected_values || '');
                this.saveField(value);
            },

            onSearch: function(query) { this.search = query; this.offset = 0; },
        },
        watch: {
            data: {
                handler: function() {
                    this.updateCurrentLabel();
                    if (this.displayMode === 'color') { this.updateCurrentColor(); }
                },
                deep: true,
            }
        },
    };
</script>

<style scoped>
    /* ── Select normal ── */
    .vs__dropdown-toggle { background: #edf2f7; padding: 10px; }
    .multiple .vs__selected {
        background-color: #ffffff;
        border: 1px solid rgb(113 128 150);
        font-size: .8em;
    }

    /* ── Color dropdown ── */
    .rotate-180 { transform: rotate(180deg); }

    /* ══════════════════════════════════════════════ */
    /* TOGGLE                                        */
    /* ══════════════════════════════════════════════ */
    .acai-toggle {
        height: 42px;
        background-color: #d5dbe1;
        border: 2px solid #4b5563;
        padding: 0;
        gap: 0;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.12);
        overflow: hidden;
    }

    .acai-toggle-pill {
        background-color: #4a5568;
        z-index: 5;
        pointer-events: none;
        box-shadow: 0 1px 3px 0 rgba(0,0,0,.2), 0 1px 3px 3px rgba(0,0,0,.1);
    }

    .acai-toggle-option {
        z-index: 10;
        font-size: 0.875rem;
        line-height: 0;
        padding: 0 14px;
        transition: color 0.25s ease;
        -webkit-font-smoothing: antialiased;
        white-space: nowrap;
    }

    .acai-toggle-option--active {
        color: #ffffff;
        font-weight: 600;
    }

    .acai-toggle-option--inactive {
        color: #4a5568;
        font-weight: 400;
    }

    .acai-toggle:hover {
        border-color: #374151;
    }

    /* ── Toggle icons ── */
    .toggle-icon { display: inline-flex; align-items: center; line-height: 0; }
    .toggle-icon svg { width: 20px; height: 20px; stroke: currentColor; fill: none; }
</style>