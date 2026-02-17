<template>
    <div @keydown.esc="onEscape">
        <div class="flex relative" style="height: 52px;">
            <!-- 1. Select de formato (ocultable) -->
            <div v-if="showFormatSelector" class="relative flex-shrink-0 text-sm h-full">
                <select 
                    v-model="selectedDisplayFormat" 
                    @change="onFormatChange"
                    class="h-full px-4 w-full appearance-none bg-gray-100 border-gray-600 border-2 border-r-0 pr-10 rounded-l-lg shadow cursor-pointer text-sm"
                >
                    <option v-for="fmt in formatOptions" :key="fmt.value" :value="fmt.value">{{ fmt.label }}</option>
                </select>
                <span class="absolute top-0 right-0 h-full w-6 pointer-events-none flex items-center justify-center mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round" class="icon w-6 h-6 text-black">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </div>

            <!-- 2. Zona de fecha: borde continuo que contiene fecha + badge reloj + hora -->
            <div class="dp-main" :class="!showFormatSelector ? 'rounded-l-lg' : ''">
                <!-- Zona de fecha clicable -->
                <div class="dp-date-zone">
                    <!-- Modo visualización (clic abre picker) -->
                    <div v-if="!editingDate" class="flex items-center w-full h-full cursor-pointer" @click="openNativePicker">
                        <span class="flex-1 truncate px-4" :class="internalDate ? 'text-gray-800 text-sm' : 'text-[#bec5cd] text-sm'">{{ internalDate ? displayValue : placeholder }}</span>
                        <!-- Icono lápiz: abre edición manual -->
                        <div class="dp-edit-icon" @click.stop="startManualEdit" title="Escribir fecha manualmente">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" class="dp-icon-edit">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                <path d="M13.5 6.5l4 4" />
                            </svg>
                        </div>
                    </div>
                    <!-- Modo edición manual -->
                    <div v-else class="flex items-center w-full h-full px-2">
                        <input 
                            ref="manualDateInput"
                            type="text" 
                            v-model="manualDateText"
                            @keydown.enter="confirmManualDate"
                            @keydown.esc="cancelManualDate"
                            @blur="confirmManualDate"
                            class="w-full h-full text-sm bg-transparent outline-none text-gray-800"
                            :placeholder="manualPlaceholder"
                        />
                    </div>
                    <!-- Input date nativo: cubre toda la zona pero invisible, para que focus/click funcione en cualquier navegador -->
                    <input 
                        type="date" 
                        ref="nativeDateInput"
                        :value="internalDate"
                        :min="minDate"
                        :max="maxDate"
                        @input="onNativeDateInput"
                        @change="onNativeDateInput"
                        class="dp-native-input"
                    />
                </div>

                <!-- Badge reloj: dentro de dp-main, flota como cp-opacity-badge -->
                <div v-if="showTimeToggle" class="dp-clock-badge" :class="{ 'dp-clock-active': showTime }" @click="toggleTime" :title="showTime ? 'Ocultar hora' : 'Mostrar hora'">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" class="dp-icon-28">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                        <path d="M12 7v5l3 3" />
                    </svg>
                </div>

                <!-- Inputs de hora: dentro de dp-main, se expanden con animación -->
                <div class="dp-time-inputs" :style="{ width: showTime ? '58px' : '0px', padding: showTime ? '0 2px' : '0', borderLeftWidth: showTimeBorder ? '2px' : '0px' }">
                    <input 
                        ref="hoursInput"
                        type="text" 
                        :value="hours" 
                        @input="onHoursInput"
                        @blur="onHoursBlur"
                        @keydown="onHoursKeydown"
                        @focus="$event.target.select()"
                        maxlength="2"
                        class="dp-time-field"
                        placeholder="HH"
                    />
                    <span class="dp-time-sep">:</span>
                    <input 
                        ref="minutesInput"
                        type="text" 
                        :value="minutes" 
                        @input="onMinutesInput"
                        @blur="onMinutesBlur"
                        @keydown.esc="onEscape"
                        @focus="$event.target.select()"
                        maxlength="2"
                        class="dp-time-field"
                        placeholder="MM"
                    />
                </div>
            </div>

            <!-- 3. Botón Reset: fuera de dp-main, con border-left propio como cp-reset -->
            <button type="button" @click="resetDate" class="dp-reset-btn" title="Borrar fecha">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" class="dp-icon-28">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                    <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                </svg>
            </button>
        </div>

        <!-- Mensaje de validación -->
        <div v-if="validationMessage" class="text-xs text-red-500 mt-1 ml-1">{{ validationMessage }}</div>

        <div style="display: none;">
            <input type="text" ref="hiddenInput" :value="outputValue" />
        </div>
    </div>
</template>

<script>
module.exports = {
    props: {
        builder: { default: null },
        data: { default: null },
        field: { type: String, default: '' },
        label: { type: String, default: 'Fecha' },
        defaultDate: { type: String, default: '' },
        placeholder: { type: String, default: 'Haz clic para seleccionar fecha' },
        displayFormat: { type: String, default: 'eu' },
        minDate: { type: String, default: '' },
        maxDate: { type: String, default: '' },
        showFormatSelector: { type: Boolean, default: true },
        showTimeToggle: { type: Boolean, default: true }
    },
    data: function() {
        return {
            internalDate: '',
            hours: '00',
            minutes: '00',
            showTime: false,
            showTimeBorder: false,
            selectedDisplayFormat: 'eu',
            editingDate: false,
            manualDateText: '',
            validationMessage: '',
            /* Punto 6: referencias a timers para cleanup */
            validationTimer: null,
            timeBorderTimer: null,
            timeFocusTimer: null,
            formatOptions: [
                { value: 'eu',      label: 'DD/MM/AAAA' },
                { value: 'eu-dash', label: 'DD-MM-AAAA' },
                { value: 'long',    label: 'Formato largo' },
                { value: 'short',   label: 'Formato corto' }
            ],
            monthsFull: [
                'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
            ],
            monthsShort: [
                'ene', 'feb', 'mar', 'abr', 'may', 'jun',
                'jul', 'ago', 'sep', 'oct', 'nov', 'dic'
            ]
        }
    },
    computed: {
        outputValue: function() {
            var hasDate = this.internalDate !== '';
            var hasTime = this.showTime && (this.hours !== '00' || this.minutes !== '00');
            
            if (!hasDate && !hasTime) return '';
            
            if (!hasDate && hasTime) {
                return this.padZero(this.hours) + ':' + this.padZero(this.minutes) + 'h';
            }
            
            var formatted = this.formatDate(this.internalDate, this.selectedDisplayFormat);
            
            if (this.showTime) {
                var sep = ' a las ';
                if (this.selectedDisplayFormat === 'eu') sep = ' - ';
                else if (this.selectedDisplayFormat === 'eu-dash') sep = ' | ';
                formatted += sep + this.padZero(this.hours) + ':' + this.padZero(this.minutes) + 'h';
            }
            
            return formatted;
        },
        displayValue: function() {
            if (!this.internalDate) return this.placeholder;
            return this.formatDate(this.internalDate, this.selectedDisplayFormat);
        },
        manualPlaceholder: function() {
            switch (this.selectedDisplayFormat) {
                case 'eu': return 'DD/MM/AAAA  ej: 15/03/2026';
                case 'eu-dash': return 'DD-MM-AAAA  ej: 15-03-2026';
                case 'long': return 'ej: 15 de marzo de 2026';
                case 'short': return 'ej: 15 mar 2026';
                default: return 'DD/MM/AAAA';
            }
        }
    },
    watch: {
        data: {
            handler: function() {
                this.initDate();
            },
            deep: true,
            immediate: true
        },
        displayFormat: {
            handler: function(val) {
                if (val) this.selectedDisplayFormat = val;
            },
            immediate: true
        }
    },
    /* Punto 5: sin mounted — el watcher de data con immediate:true ya cubre la inicialización */
    /* Punto 6: cleanup de todos los timers */
    beforeDestroy: function() {
        if (this.validationTimer) { clearTimeout(this.validationTimer); this.validationTimer = null; }
        if (this.timeBorderTimer) { clearTimeout(this.timeBorderTimer); this.timeBorderTimer = null; }
        if (this.timeFocusTimer) { clearTimeout(this.timeFocusTimer); this.timeFocusTimer = null; }
    },
    methods: {
        padZero: function(val) {
            var num = parseInt(val, 10);
            if (isNaN(num)) return '00';
            if (num < 0) num = 0;
            return num < 10 ? '0' + num : '' + num;
        },
        
        formatDate: function(dateStr, format) {
            var parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            
            var year = parts[0];
            var month = parseInt(parts[1], 10);
            var day = parseInt(parts[2], 10);
            var dayPad = this.padZero(day);
            var monthPad = this.padZero(month);
            var monthIndex = month - 1;
            
            switch (format) {
                case 'eu':
                    return dayPad + '/' + monthPad + '/' + year;
                case 'eu-dash':
                    return dayPad + '-' + monthPad + '-' + year;
                case 'long':
                    return day + ' de ' + this.monthsFull[monthIndex] + ' de ' + year;
                case 'short':
                    return day + ' ' + this.monthsShort[monthIndex] + ' ' + year;
                default:
                    return dayPad + '/' + monthPad + '/' + year;
            }
        },

        /* Punto 4: validar que la fecha exista realmente (ej: 31/02 → inválido) */
        isValidDate: function(year, month, day) {
            var d = new Date(year, month - 1, day);
            return d.getFullYear() === year && (d.getMonth() + 1) === month && d.getDate() === day;
        },
        
        /* Punto 6: validación con timer guardado */
        showValidation: function(msg) {
            var self = this;
            self.validationMessage = msg;
            if (self.validationTimer) clearTimeout(self.validationTimer);
            self.validationTimer = setTimeout(function() {
                self.validationMessage = '';
                self.validationTimer = null;
            }, 4000);
        },

        validateDateRange: function(dateStr) {
            if (!dateStr) return true;

            /* Punto 4: validar fecha real */
            var parts = dateStr.split('-');
            if (parts.length === 3) {
                var y = parseInt(parts[0], 10);
                var m = parseInt(parts[1], 10);
                var d = parseInt(parts[2], 10);
                if (!this.isValidDate(y, m, d)) {
                    this.showValidation('La fecha introducida no es válida');
                    return false;
                }
            }

            if (this.minDate && dateStr < this.minDate) {
                this.showValidation('La fecha no puede ser anterior a ' + this.formatDate(this.minDate, this.selectedDisplayFormat));
                return false;
            }
            if (this.maxDate && dateStr > this.maxDate) {
                this.showValidation('La fecha no puede ser posterior a ' + this.formatDate(this.maxDate, this.selectedDisplayFormat));
                return false;
            }
            this.validationMessage = '';
            return true;
        },
        
        openNativePicker: function() {
            var input = this.$refs.nativeDateInput;
            if (!input) return;

            // Activar pointer-events temporalmente
            input.style.pointerEvents = 'auto';

            // showPicker() es lo ideal (Chrome 99+, Safari 16+)
            if (typeof input.showPicker === 'function') {
                try {
                    input.showPicker();
                    this.hideNativeInputLater(input);
                    return;
                } catch (e) {
                    // Falla en algunos contextos (iframe, no-user-gesture), usar fallback
                }
            }

            // Fallback: el input ya cubre toda la zona (absolute inset 0),
            // así que focus + click funciona en cualquier navegador
            input.focus();
            input.click();
            this.hideNativeInputLater(input);
        },

        hideNativeInputLater: function(input) {
            setTimeout(function() {
                if (input) {
                    input.style.pointerEvents = 'none';
                }
            }, 300);
        },
        
        /* Icono lápiz: activa modo edición manual */
        startManualEdit: function() {
            this.editingDate = true;
            this.manualDateText = this.internalDate ? this.formatDate(this.internalDate, this.selectedDisplayFormat) : '';
            var self = this;
            self.$nextTick(function() {
                if (self.$refs.manualDateInput) {
                    self.$refs.manualDateInput.focus();
                    self.$refs.manualDateInput.select();
                }
            });
        },
        
        confirmManualDate: function() {
            var text = this.manualDateText.trim();
            this.editingDate = false;
            
            if (!text) return;
            
            var parsed = this.parseDatePart(text);
            if (parsed && parsed.date) {
                if (this.validateDateRange(parsed.date)) {
                    this.internalDate = parsed.date;
                    if (parsed.format) {
                        this.selectedDisplayFormat = parsed.format;
                    }
                    this.saveAndEmit();
                }
            } else {
                /* Punto 6: usa showValidation en vez de setTimeout suelto */
                this.showValidation('Formato no reconocido. Usa: DD/MM/AAAA, DD-MM-AAAA o texto');
            }
        },
        
        cancelManualDate: function() {
            this.editingDate = false;
            this.manualDateText = '';
        },
        
        onEscape: function() {
            if (this.editingDate) {
                this.cancelManualDate();
            } else if (this.showTime) {
                this.showTime = false;
                var self = this;
                /* Punto 6: timer guardado */
                if (self.timeBorderTimer) clearTimeout(self.timeBorderTimer);
                self.timeBorderTimer = setTimeout(function() {
                    if (!self.showTime) {
                        self.showTimeBorder = false;
                    }
                    self.timeBorderTimer = null;
                }, 510);
                if (this.internalDate) {
                    this.saveAndEmit();
                }
            }
        },
        
        toggleTime: function() {
            var self = this;
            this.showTime = !this.showTime;
            
            if (this.showTime) {
                this.showTimeBorder = true;
                /* Punto 6: timer guardado */
                if (self.timeFocusTimer) clearTimeout(self.timeFocusTimer);
                self.timeFocusTimer = setTimeout(function() {
                    if (self.$refs.hoursInput) {
                        self.$refs.hoursInput.focus();
                        self.$refs.hoursInput.select();
                    }
                    self.timeFocusTimer = null;
                }, 520);
            } else {
                /* Punto 6: timer guardado */
                if (self.timeBorderTimer) clearTimeout(self.timeBorderTimer);
                self.timeBorderTimer = setTimeout(function() {
                    if (!self.showTime) {
                        self.showTimeBorder = false;
                    }
                    self.timeBorderTimer = null;
                }, 510);
            }
            
            if (this.internalDate || (!this.showTime && (this.hours !== '00' || this.minutes !== '00'))) {
                this.saveAndEmit();
            }
        },
        
        onHoursInput: function(event) {
            var raw = event.target.value.replace(/\D/g, '');
            if (raw.length > 2) raw = raw.substring(0, 2);
            
            var val = parseInt(raw, 10);
            if (!isNaN(val) && val > 23) raw = '23';
            
            event.target.value = raw;
            this.hours = raw;
            
            if (raw.length === 2) {
                this.hours = this.padZero(raw);
                var self = this;
                self.$nextTick(function() {
                    if (self.$refs.minutesInput) {
                        self.$refs.minutesInput.focus();
                        self.$refs.minutesInput.select();
                    }
                });
            }
        },
        
        onHoursKeydown: function(event) {
            if (event.key === ':' || event.key === 'Tab') {
                event.preventDefault();
                this.hours = this.padZero(this.hours);
                var self = this;
                self.$nextTick(function() {
                    if (self.$refs.minutesInput) {
                        self.$refs.minutesInput.focus();
                        self.$refs.minutesInput.select();
                    }
                });
            }
            if (event.key === 'Escape') {
                this.onEscape();
            }
        },
        
        onHoursBlur: function() {
            this.hours = this.padZero(this.hours);
            this.saveAndEmit();
        },
        
        onMinutesInput: function(event) {
            var raw = event.target.value.replace(/\D/g, '');
            if (raw.length > 2) raw = raw.substring(0, 2);
            
            var val = parseInt(raw, 10);
            if (!isNaN(val) && val > 59) raw = '59';
            
            event.target.value = raw;
            this.minutes = raw;
            
            if (raw.length === 2) {
                this.minutes = this.padZero(raw);
                this.saveAndEmit();
            }
        },
        
        onMinutesBlur: function() {
            this.minutes = this.padZero(this.minutes);
            this.saveAndEmit();
        },
        
        parseStoredValue: function(value) {
            if (!value || typeof value !== 'string') return null;
            
            value = value.trim();
            
            var timeOnlyMatch = /^(\d{2}):(\d{2})h?$/.exec(value);
            if (timeOnlyMatch) {
                return { date: '', hours: timeOnlyMatch[1], minutes: timeOnlyMatch[2], hasTime: true, format: null };
            }
            
            var timeHours = '00';
            var timeMinutes = '00';
            var hasTime = false;
            
            var timeMatch = /(?:\s+a\s+las\s+|\s*[—·|\-]\s*|\s+)(\d{2}):(\d{2})(?:\s*h(?:oras)?)?\s*$/.exec(value);
            if (timeMatch) {
                timeHours = timeMatch[1];
                timeMinutes = timeMatch[2];
                hasTime = true;
                value = value.substring(0, timeMatch.index).trim();
            }
            
            var result = this.parseDatePart(value);
            if (result) {
                result.hours = timeHours;
                result.minutes = timeMinutes;
                result.hasTime = hasTime;
                return result;
            }
            
            return null;
        },
        
        /* Punto 3+4: parseo robusto con regex consistentes ($) y validación de fecha real */
        parseDatePart: function(value) {
            var self = this;
            var y, m, d;

            // ISO: YYYY-MM-DD
            var isoMatch = /^(\d{4})-(\d{1,2})-(\d{1,2})$/.exec(value);
            if (isoMatch) {
                y = parseInt(isoMatch[1], 10);
                m = parseInt(isoMatch[2], 10);
                d = parseInt(isoMatch[3], 10);
                if (self.isValidDate(y, m, d)) {
                    return { date: isoMatch[1] + '-' + self.padZero(m) + '-' + self.padZero(d), format: 'eu' };
                }
                return null;
            }
            
            // EU barras: DD/MM/YYYY
            var euMatch = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(value);
            if (euMatch) {
                y = parseInt(euMatch[3], 10);
                m = parseInt(euMatch[2], 10);
                d = parseInt(euMatch[1], 10);
                if (self.isValidDate(y, m, d)) {
                    return { date: euMatch[3] + '-' + self.padZero(m) + '-' + self.padZero(d), format: 'eu' };
                }
                return null;
            }
            
            // EU guiones: DD-MM-YYYY
            var euDashMatch = /^(\d{1,2})-(\d{1,2})-(\d{4})$/.exec(value);
            if (euDashMatch) {
                y = parseInt(euDashMatch[3], 10);
                m = parseInt(euDashMatch[2], 10);
                d = parseInt(euDashMatch[1], 10);
                if (self.isValidDate(y, m, d)) {
                    return { date: euDashMatch[3] + '-' + self.padZero(m) + '-' + self.padZero(d), format: 'eu-dash' };
                }
                return null;
            }

            /*
                Texto con nombre de mes — acepta variaciones naturales:
                "15 de marzo de 2026"  ✓   "15 marzo 2026"   ✓
                "15 de marzo 2026"     ✓   "15, marzo, 2026" ✓
                "marzo 15, 2026"       ✓   "marzo 2026"      ✓ (día = 1)
            */
            var normalized = value.toLowerCase().replace(/,/g, ' ').replace(/\s+/g, ' ').trim();
            // Quitar "de" y "del" sueltos para simplificar
            normalized = normalized.replace(/\bde[l]?\b/g, ' ').replace(/\s+/g, ' ').trim();

            // Buscar nombre de mes (completo primero, abreviado después)
            var monthNum = -1;
            var isLong = false;
            var i;
            for (i = 0; i < self.monthsFull.length; i++) {
                if (normalized.indexOf(self.monthsFull[i]) > -1) {
                    monthNum = i + 1;
                    isLong = true;
                    normalized = normalized.replace(self.monthsFull[i], ' ').replace(/\s+/g, ' ').trim();
                    break;
                }
            }
            if (monthNum === -1) {
                for (i = 0; i < self.monthsShort.length; i++) {
                    if (normalized.indexOf(self.monthsShort[i]) > -1) {
                        monthNum = i + 1;
                        isLong = false;
                        normalized = normalized.replace(self.monthsShort[i], ' ').replace(/\s+/g, ' ').trim();
                        break;
                    }
                }
            }

            if (monthNum > -1) {
                // Extraer todos los números que queden
                var nums = normalized.match(/\d+/g);
                if (nums) {
                    if (nums.length === 1) {
                        var n = parseInt(nums[0], 10);
                        if (n > 31) {
                            // Solo año → día = 1
                            y = n; d = 1;
                        } else {
                            // Solo día → año actual
                            d = n; y = new Date().getFullYear();
                        }
                    } else {
                        // Dos números: el >31 es año, el otro es día
                        var n1 = parseInt(nums[0], 10);
                        var n2 = parseInt(nums[1], 10);
                        if (n1 > 31) { y = n1; d = n2; }
                        else if (n2 > 31) { d = n1; y = n2; }
                        else { d = n1; y = n2; } // ambiguo: asume día primero
                    }
                } else {
                    // Solo mes → día 1, año actual
                    d = 1; y = new Date().getFullYear();
                }

                m = monthNum;
                if (self.isValidDate(y, m, d)) {
                    return {
                        date: '' + y + '-' + self.padZero(m) + '-' + self.padZero(d),
                        format: isLong ? 'long' : 'short'
                    };
                }
                return null;
            }
            
            return null;
        },
        
        initDate: function() {
            var self = this;
            var value = null;
            
            if (self.data && self.data[self.field]) {
                var fieldData = self.data[self.field];
                
                if (typeof fieldData === 'string' && fieldData !== '') {
                    value = fieldData;
                } else if (typeof fieldData === 'object' && fieldData !== null) {
                    if (fieldData.value && fieldData.value !== '') {
                        value = fieldData.value;
                    } else if (fieldData.newValues && fieldData.newValues.builder_custom && fieldData.newValues.builder_custom.value) {
                        value = fieldData.newValues.builder_custom.value;
                    }
                }
            }
            
            var parsed = self.parseStoredValue(value);
            if (parsed) {
                self.internalDate = parsed.date;
                self.hours = parsed.hours;
                self.minutes = parsed.minutes;
                self.showTime = parsed.hasTime;
                self.showTimeBorder = parsed.hasTime;
                if (parsed.format) {
                    self.selectedDisplayFormat = parsed.format;
                }
            } else if (self.defaultDate) {
                var defaultParsed = self.parseStoredValue(self.defaultDate);
                if (defaultParsed) {
                    self.internalDate = defaultParsed.date;
                    self.hours = defaultParsed.hours;
                    self.minutes = defaultParsed.minutes;
                    self.showTime = defaultParsed.hasTime;
                    self.showTimeBorder = defaultParsed.hasTime;
                    if (defaultParsed.format) {
                        self.selectedDisplayFormat = defaultParsed.format;
                    }
                } else {
                    self.internalDate = '';
                    self.hours = '00';
                    self.minutes = '00';
                    self.showTime = false;
                    self.showTimeBorder = false;
                }
            } else {
                self.internalDate = '';
                self.hours = '00';
                self.minutes = '00';
                self.showTime = false;
                self.showTimeBorder = false;
            }
        },
        
        updateDataField: function(value) {
            var self = this;
            
            if (self.data) {
                if (self.data[self.field] && typeof self.data[self.field] === 'object') {
                    self.data[self.field].value = value;
                    if (self.data[self.field].newValues && self.data[self.field].newValues.builder_custom) {
                        self.data[self.field].newValues.builder_custom.value = value;
                    }
                } else {
                    self.$set(self.data, self.field, value);
                }
            }
        },
        
        saveAndEmit: function() {
            this.updateDataField(this.outputValue);
            this.$emit('save-data');
        },
        
        onNativeDateInput: function(event) {
            var newDate = event.target.value;
            if (this.validateDateRange(newDate)) {
                this.internalDate = newDate;
                this.saveAndEmit();
            } else {
                event.target.value = this.internalDate;
            }
        },
        
        onFormatChange: function() {
            if (this.internalDate) {
                this.saveAndEmit();
            }
        },
        
        resetDate: function() {
            /* Punto 6: limpiar timers en reset */
            if (this.validationTimer) { clearTimeout(this.validationTimer); this.validationTimer = null; }
            if (this.timeBorderTimer) { clearTimeout(this.timeBorderTimer); this.timeBorderTimer = null; }
            if (this.timeFocusTimer) { clearTimeout(this.timeFocusTimer); this.timeFocusTimer = null; }

            this.internalDate = '';
            this.hours = '00';
            this.minutes = '00';
            this.showTime = false;
            this.showTimeBorder = false;
            this.editingDate = false;
            this.manualDateText = '';
            this.validationMessage = '';
            this.selectedDisplayFormat = this.displayFormat || 'eu';
            
            this.updateDataField('');
            this.$emit('save-data');
            this.$emit('reset');
        }
    }
}
</script>

<style scoped>
/* ── Barra principal (como cp-main) ── */
.dp-main {
    position: relative;
    display: flex;
    align-items: stretch;
    flex: 1;
    min-width: 0;
    height: 100%;
    background-color: #edf2f7;
    border: 2px solid #4b5563;
    border-right: none;
    overflow: hidden;
    transition: border-color 0.2s ease;
}
.dp-main:hover {
    border-color: #6b7280;
}

/* ── Zona de fecha (como cp-clickzone) ── */
.dp-date-zone {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0;
    height: 100%;
}

/* ── Badge reloj (como cp-opacity-badge) ── */
.dp-clock-badge {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    padding: 4px 6px;
    margin: 4px 8px 4px 0;
    flex-shrink: 0;
    cursor: pointer;
    color: #4b5563;
    background-color: rgba(75, 85, 99, 0.15);
    transition: background-color 0.15s ease, color 0.15s ease;
}
.dp-clock-badge:hover {
    background-color: rgba(75, 85, 99, 0.25);
}
.dp-clock-badge.dp-clock-active {
    background-color: #4b5563;
    color: #ffffff;
}
.dp-clock-badge.dp-clock-active:hover {
    background-color: #374151;
}

/* ── Inputs de hora ── */
.dp-time-inputs {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0;
    height: 100%;
    background: rgba(255, 255, 255, 0.5);
    overflow: hidden;
    border-left-style: solid;
    border-left-color: #9ca3af;
    flex-shrink: 0;
    transition: width 0.5s cubic-bezier(0.25, 0.1, 0.25, 1), padding 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
}
.dp-time-field {
    width: 20px;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    background: transparent;
    border: none;
    outline: none;
    padding: 0;
    margin: 0;
    -moz-appearance: textfield;
}
.dp-time-field::-webkit-outer-spin-button,
.dp-time-field::-webkit-inner-spin-button {
    -webkit-appearance: none;
}
.dp-time-sep {
    font-size: 14px;
    font-weight: 700;
    color: #9ca3af;
    line-height: 1;
    padding: 0;
    margin: 0;
}

/* ── Reset (como cp-reset) ── */
.dp-reset-btn {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 100%;
    border: 2px solid #4b5563;
    border-radius: 0 8px 8px 0;
    background-color: #f3f4f6;
    color: #4b5563;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.2s ease;
}
.dp-reset-btn:hover {
    background-color: #d1d5db;
    border-color: #6b7280;
}
.dp-reset-btn svg {
    opacity: 0.8;
    transition: opacity 0.15s ease;
}
.dp-reset-btn:hover svg {
    opacity: 1;
}

/* ── Input date nativo: cubre la zona para fallback real ── */
.dp-native-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    pointer-events: none;
    z-index: 1;
    /* Evitar que el input nativo muestre su UI propia */
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    border: none;
    background: transparent;
    margin: 0;
    padding: 0;
}

/* ── Icono lápiz (edición manual) ── */
.dp-edit-icon {
    position: relative;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 4px;
    margin-right: 4px;
    border-radius: 4px;
    color: #9ca3af;
    cursor: pointer;
    transition: color 0.15s ease, background-color 0.15s ease;
}
.dp-edit-icon:hover {
    color: #4b5563;
    background-color: rgba(75, 85, 99, 0.12);
}
.dp-icon-edit {
    width: 16px;
    height: 16px;
}

/* ── Iconos 28px ── */
.dp-icon-28 {
    width: 28px;
    height: 28px;
}
</style>