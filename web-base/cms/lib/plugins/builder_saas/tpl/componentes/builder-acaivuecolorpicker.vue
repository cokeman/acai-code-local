<template>
    <div class="cp">
        <!-- Barra principal -->
        <div class="cp-main">
            <!-- Checkerboard para transparencia -->
            <div class="cp-bg-checker"></div>
            <!-- Color de fondo -->
            <div class="cp-bg-color" :style="{ backgroundColor: rgbaString }"></div>
            <!-- Input color nativo oculto -->
            <input type="color" ref="colorInput" class="cp-color-hidden" :value="hexColor" @input="onColorInput" tabindex="-1" />
            <!-- Zona clickeable que abre el picker -->
            <div class="cp-clickzone" @click="openColorPicker" title="Seleccionar color">
                <span class="cp-label" :style="{ color: textColor }">{{ label }} :</span>
                <span class="cp-value" :style="{ color: textColor }">{{ hexUpper }}</span>
            </div>
            <!-- Badge de opacidad editable -->
            <div class="cp-opacity-badge" :style="{ color: textColor, backgroundColor: badgeBg }" @mouseenter="onBadgeEnter" @mouseleave="onBadgeLeave">
                <input
                    type="text"
                    class="cp-opacity-input"
                    :value="opacityPercent"
                    maxlength="3"
                    title="Opacidad (0-100%)"
                    @focus="onOpacityFocus"
                    @blur="onOpacityBlur"
                    @keydown="onOpacityKeydown"
                />
                <span class="cp-opacity-symbol">%</span>
            </div>
            <!-- Botón reset -->
            <button type="button" class="cp-reset" :style="{ color: textColor, backgroundColor: resetBg }" @click="resetColor" @mouseenter="onResetEnter" @mouseleave="onResetLeave" title="Restaurar por defecto">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 11a8.1 8.1 0 0 0-15.5-2m-.5-4v4h4"/>
                    <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                </svg>
            </button>
        </div>
        <!-- Slider horizontal de opacidad -->
        <div class="cp-slider-wrap">
            <div class="cp-slider-track" ref="sliderTrack" :class="{ dragging: isDragging }" @mousedown="startDrag" @touchstart.prevent="startDrag">
                <div class="cp-slider-checker"></div>
                <div class="cp-slider-gradient" :style="{ background: sliderGradient }"></div>
                <div class="cp-slider-thumb" :style="{ left: thumbLeft }"></div>
            </div>
        </div>
        <!-- Input oculto para el builder -->
        <div style="display: none;">
            <input type="text" ref="hiddenInput" :value="rgbaString" />
        </div>
    </div>
</template>

<script>
module.exports = {
    props: ['builder', 'data', 'field', 'label', 'color'],
    data: function() {
        return {
            hexColor: '#000000',
            opacity: 1,
            isDragging: false,
            badgeHover: false,
            resetHover: false
        }
    },
    computed: {
        defaultColor: function() {
            return this.color || '#000000';
        },
        opacityPercent: function() {
            return Math.round(this.opacity * 100);
        },
        hexUpper: function() {
            if (this.opacity <= 0) {
                return 'TRANSPARENT';
            }
            return this.hexColor.toUpperCase();
        },
        sliderGradient: function() {
            return 'linear-gradient(to right, transparent, ' + this.hexColor + ')';
        },
        thumbLeft: function() {
            var pct = this.opacityPercent / 100;
            return 'calc(9px + (100% - 18px) * ' + pct + ')';
        },
        rgbaString: function() {
            var rgb = this.hexToRgb(this.hexColor);
            if (this.opacity <= 0) {
                return 'transparent';
            }
            if (this.opacity >= 1) {
                return this.hexColor;
            }
            return 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + this.opacity.toFixed(2) + ')';
        },
        tone: function() {
            var rgb = this.hexToRgb(this.hexColor);
            var r = Math.round(rgb.r * this.opacity + 255 * (1 - this.opacity));
            var g = Math.round(rgb.g * this.opacity + 255 * (1 - this.opacity));
            var b = Math.round(rgb.b * this.opacity + 255 * (1 - this.opacity));
            var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance;
        },
        isDark: function() {
            return this.tone <= 0.60;
        },
        textColor: function() {
            return this.isDark ? '#ffffff' : '#1f2937';
        },
        textColorSoft: function() {
            return this.isDark ? 'rgba(255,255,255,0.7)' : 'rgba(31,41,55,0.55)';
        },
        badgeBg: function() {
            if (this.isDark) {
                return this.badgeHover ? 'rgba(255,255,255,0.30)' : 'rgba(255,255,255,0.20)';
            }
            return this.badgeHover ? 'rgba(75,85,99,0.25)' : 'rgba(75,85,99,0.15)';
        },
        resetBg: function() {
            if (this.isDark) {
                return this.resetHover ? 'rgba(255,255,255,0.30)' : 'rgba(255,255,255,0.20)';
            }
            return this.resetHover ? 'rgba(75,85,99,0.25)' : 'rgba(75,85,99,0.15)';
        }
    },
    watch: {
        data: {
            handler: function() {
                this.initColor();
            },
            deep: true,
            immediate: true
        },
        color: {
            handler: function() {
                this.initColor();
            },
            immediate: true
        }
    },
    mounted: function() {
        var self = this;
        self.$nextTick(function() {
            self.initColor();
        });
        document.addEventListener('mousemove', self.onDrag);
        document.addEventListener('mouseup', self.stopDrag);
        document.addEventListener('touchmove', self.onDrag);
        document.addEventListener('touchend', self.stopDrag);
    },
    beforeDestroy: function() {
        document.removeEventListener('mousemove', this.onDrag);
        document.removeEventListener('mouseup', this.stopDrag);
        document.removeEventListener('touchmove', this.onDrag);
        document.removeEventListener('touchend', this.stopDrag);
    },
    methods: {
        openColorPicker: function() {
            this.$refs.colorInput.click();
        },
        onColorInput: function(event) {
            this.hexColor = event.target.value;
            // Si la opacidad es 0 (transparente), subirla a 100% para mostrar el nuevo color
            if (this.opacity <= 0) {
                this.opacity = 1;
            }
            this.updateDataField(this.rgbaString);
            this.$emit('save-data');
        },

        onBadgeEnter: function() { this.badgeHover = true; },
        onBadgeLeave: function() { this.badgeHover = false; },
        onResetEnter: function() { this.resetHover = true; },
        onResetLeave: function() { this.resetHover = false; },

        onOpacityFocus: function(event) {
            event.target.select();
        },
        onOpacityBlur: function(event) {
            var value = parseInt(event.target.value, 10);
            if (!isNaN(value)) {
                this.setOpacityFromPercent(value);
            }
        },
        onOpacityKeydown: function(event) {
            if (event.key === 'Enter') {
                event.target.blur();
                return;
            }
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.setOpacityFromPercent(Math.min(100, this.opacityPercent + 1));
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.setOpacityFromPercent(Math.max(0, this.opacityPercent - 1));
            }
        },
        setOpacityFromPercent: function(percent) {
            percent = Math.max(0, Math.min(100, Math.round(percent)));
            this.opacity = percent / 100;
            this.updateDataField(this.rgbaString);
            this.$emit('save-data');
        },

        startDrag: function(event) {
            this.isDragging = true;
            this.updateOpacityFromEvent(event);
        },
        onDrag: function(event) {
            if (this.isDragging) {
                this.updateOpacityFromEvent(event);
            }
        },
        stopDrag: function() {
            if (this.isDragging) {
                this.isDragging = false;
                this.$emit('save-data');
            }
        },
        updateOpacityFromEvent: function(event) {
            var track = this.$refs.sliderTrack;
            if (!track) return;
            var rect = track.getBoundingClientRect();
            var clientX = event.touches ? event.touches[0].clientX : event.clientX;
            var thumbHalf = 9;
            var usableWidth = rect.width - thumbHalf * 2;
            var x = clientX - rect.left - thumbHalf;
            var pct = x / usableWidth;
            pct = Math.max(0, Math.min(1, pct));
            this.opacity = Math.round(pct * 100) / 100;
            this.updateDataField(this.rgbaString);
        },

        resetColor: function() {
            var defaultParsed = this.parseColor(this.defaultColor);
            if (defaultParsed) {
                this.hexColor = defaultParsed.hex;
                this.opacity = defaultParsed.opacity;
            } else {
                this.hexColor = '#000000';
                this.opacity = 1;
            }
            this.updateDataField('');
            this.$emit('save-data');
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
        initColor: function() {
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
            var parsed = self.parseColor(value);
            if (parsed) {
                self.hexColor = parsed.hex;
                self.opacity = parsed.opacity;
            } else {
                var defaultParsed = self.parseColor(self.defaultColor);
                if (defaultParsed) {
                    self.hexColor = defaultParsed.hex;
                    self.opacity = defaultParsed.opacity;
                } else {
                    self.hexColor = '#000000';
                    self.opacity = 1;
                }
            }
        },

        hexToRgb: function(hex) {
            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : { r: 0, g: 0, b: 0 };
        },
        rgbToHex: function(r, g, b) {
            return '#' + [r, g, b].map(function(x) {
                var hex = x.toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            }).join('');
        },
        parseColor: function(colorString) {
            if (!colorString) return null;
            
            // Detectar "transparent" (case-insensitive)
            if (colorString.toLowerCase() === 'transparent') {
                return { hex: '#000000', opacity: 0 };
            }
            
            if (/^#[0-9A-Fa-f]{6}$/.test(colorString)) {
                return { hex: colorString, opacity: 1 };
            }
            var hexAlphaMatch = /^#([0-9A-Fa-f]{6})([0-9A-Fa-f]{2})$/.exec(colorString);
            if (hexAlphaMatch) {
                return {
                    hex: '#' + hexAlphaMatch[1],
                    opacity: parseInt(hexAlphaMatch[2], 16) / 255
                };
            }
            var rgbaMatch = /^rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)$/i.exec(colorString);
            if (rgbaMatch) {
                return {
                    hex: this.rgbToHex(
                        parseInt(rgbaMatch[1]),
                        parseInt(rgbaMatch[2]),
                        parseInt(rgbaMatch[3])
                    ),
                    opacity: rgbaMatch[4] !== undefined ? parseFloat(rgbaMatch[4]) : 1
                };
            }
            return null;
        }
    }
}
</script>

<style scoped>
.cp {
    --cp-radius: 10px;
    --cp-border: #4b5563;
    position: relative;
    width: 100%;
}

/* ── Barra principal ── */
.cp-main {
    position: relative;
    display: flex;
    align-items: stretch;
    border: 2px solid var(--cp-border);
    border-radius: var(--cp-radius);
    overflow: hidden;
    min-height: 44px;
    cursor: pointer;
    transition: border-color 0.2s ease;
}
.cp-main:hover {
    border-color: #6b7280;
}

/* Checkerboard */
.cp-bg-checker {
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    background-image:
        linear-gradient(45deg, #d1d5db 25%, transparent 25%),
        linear-gradient(-45deg, #d1d5db 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #d1d5db 75%),
        linear-gradient(-45deg, transparent 75%, #d1d5db 75%);
    background-size: 10px 10px;
    background-position: 0 0, 0 5px, 5px -5px, -5px 0;
    z-index: 0;
}

/* Color de fondo */
.cp-bg-color {
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    z-index: 1;
    transition: background-color 0.1s ease;
}

/* Input color oculto */
.cp-color-hidden {
    position: absolute;
    width: 0;
    height: 0;
    overflow: hidden;
    opacity: 0;
    pointer-events: none;
}

/* Zona clickeable */
.cp-clickzone {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    flex: 1;
    padding: 0 8px 0 14px;
    min-height: 44px;
    gap: 8px;
    cursor: pointer;
}

/* Label */
.cp-label {
    font-size: 14px;
    font-weight: 700;
    white-space: nowrap;
    transition: color 0.15s ease;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Valor hex */
.cp-value {
    font-size: 16px;
    font-weight: 500;
    font-family: monospace;
    letter-spacing: 0.02em;
    white-space: nowrap;
    opacity: 0.6;
    transition: color 0.15s ease;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* ── Badge de opacidad ── */
.cp-opacity-badge {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    padding: 4px 6px;
    margin: 4px 8px 4px 0;
    gap: 0px;
    transition: background-color 0.15s ease;
    flex-shrink: 0;
}

.cp-opacity-input {
    width: 34px;
    background: transparent;
    border: none;
    outline: none;
    text-align: center;
    font-size: 16px;
    font-weight: 700;
    color: inherit;
    padding: 0;
    -moz-appearance: textfield;
}
.cp-opacity-input::-webkit-outer-spin-button,
.cp-opacity-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
}
.cp-opacity-input:focus {
    background: rgba(255,255,255,0.25);
    border-radius: 3px;
}

.cp-opacity-symbol {
    font-size: 16px;
    font-weight: 700;
    opacity: 0.7;
    padding: 0 4px 0 0;
}

/* ── Botón reset ── */
.cp-reset {
    position: relative;
    z-index: 2;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    border: none;
    border-left: 2px solid var(--cp-border);
    cursor: pointer;
    transition: background-color 0.15s ease;
    color: inherit;
}
.cp-reset svg {
    width: 28px;
    height: 28px;
    opacity: 0.8;
    transition: opacity 0.15s ease;
}

.cp-reset:hover svg {
    opacity: 1;
}

/* ── Slider horizontal de opacidad ── */
.cp-slider-wrap {
    margin-top: 6px;
    position: relative;
}

.cp-slider-track {
    position: relative;
    width: 100%;
    height: 14px;
    border-radius: 7px;
    overflow: visible;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    border: 2px solid var(--cp-border);
    transition: border-color 0.2s ease;
}
.cp-slider-track:hover {
    border-color: #6b7280;
}

.cp-slider-checker {
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    border-radius: 5px;
    overflow: hidden;
    background-image:
        linear-gradient(45deg, #d1d5db 25%, transparent 25%),
        linear-gradient(-45deg, #d1d5db 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #d1d5db 75%),
        linear-gradient(-45deg, transparent 75%, #d1d5db 75%);
    background-size: 8px 8px;
    background-position: 0 0, 0 4px, 4px -4px, -4px 0;
}

.cp-slider-gradient {
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    border-radius: 5px;
    overflow: hidden;
    transition: background 0.1s ease;
}

.cp-slider-thumb {
    position: absolute;
    top: -2px;
    bottom: -2px;
    width: 20px;
    background: #ffffff;
    border-radius: 9999px;
    border: 2px solid #4b5563;
    box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
    -webkit-transform: translateX(-50%);
    transform: translateX(-50%);
    pointer-events: none;
    cursor: grab;
    transition: box-shadow 0.15s ease;
    z-index: 2;
}
.cp-slider-track:hover .cp-slider-thumb,
.cp-slider-track.dragging .cp-slider-thumb {
    box-shadow: 0 0 0 1px rgba(0,0,0,0.25);
}
</style>