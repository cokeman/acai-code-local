<template>
    <div class="acai-tabs-wrapper" :id="'acai-tabs-' + _uid">
        <!-- SISTEMA DE TABS ESTILO CARPETA -->
        <div class="tabs-container" role="tablist" aria-label="Pestañas de configuración">
            <transition-group name="tab-move" tag="div" class="tabs-wrapper">
                <div 
                    v-for="(tab, index) in sortedTabs" 
                    :key="tab.id" 
                    :draggable="draggable"
                    @dragstart="onDragStart($event, getOriginalIndex(tab.id))" 
                    @dragover.prevent="onDragOver($event, getOriginalIndex(tab.id))" 
                    @drop="onDrop($event, getOriginalIndex(tab.id))" 
                    @dragend="onDragEnd" 
                    @click="selectTab(tab.id)" 
                    @keydown.enter.prevent="selectTab(tab.id)"
                    @keydown.space.prevent="selectTab(tab.id)"
                    @keydown.right.prevent="focusNextTab(getOriginalIndex(tab.id))"
                    @keydown.left.prevent="focusPrevTab(getOriginalIndex(tab.id))"
                    class="tab-item" 
                    :class="[ 
                        activeTab === tab.id ? 'active' : '', 
                        isDragging && dragIndex === getOriginalIndex(tab.id) ? 'dragging' : '' 
                    ]" 
                    :style="getTabStyle(tab, index)" 
                    :title="tab.label"
                    role="tab"
                    :id="'tab-' + tab.id"
                    :aria-selected="activeTab === tab.id ? 'true' : 'false'"
                    :aria-controls="'panel-' + tab.id"
                    :tabindex="activeTab === tab.id ? 0 : -1"
                    :ref="'tab-' + tab.id"
                >
                    <span v-if="activeTab === tab.id" class="tab-text">{{ tab.label }}</span>
                    <span v-html="tab.icon" class="tab-icon"></span>
                    <div 
                        v-if="activeTab === tab.id" 
                        class="tab-active-border" 
                        :class="{ 'border-visible': showBorder }" 
                        :style="{ backgroundColor: tab.color }"
                    ></div>
                </div>
            </transition-group>
        </div>

        <!-- CONTENIDO DE TABS -->
        <div 
            v-for="tab in tabs" 
            :key="'panel-' + tab.id"
            v-show="activeTab === tab.id" 
            class="tab-panel" 
            :style="{ borderLeftColor: showBorder ? getTabColor(tab.id) : 'transparent' }"
            role="tabpanel"
            :id="'panel-' + tab.id"
            :aria-labelledby="'tab-' + tab.id"
        >
            <slot :name="tab.id" :tab="tab" :color="tab.color"></slot>
        </div>
    </div>
</template>

<style scoped>
    .acai-tabs-wrapper {
        width: 100%;
    }

    .tabs-container {
        width: 100%;
        margin-bottom: 0;
    }

    .tabs-wrapper {
        display: flex;
        align-items: flex-end;
    }

    /* ── Tab base ── */
    .tab-item {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 24px 10px 32px;
        margin-right: -8px;
        cursor: pointer;
        user-select: none;
        color: white;
        border-radius: 15px 15px 0 0;
        box-shadow: 5px 0 8px rgba(0, 0, 0, 0.1);
        transition: padding 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
        transform-origin: bottom center;
    }

    /* ── Hover: escala sutil en tabs inactivos ── */
    .tab-item:not(.active):hover {
        transform: scale(1.06);
        box-shadow: 5px -2px 12px rgba(0, 0, 0, 0.18);
    }

    .tab-item:not(.active):active {
        transform: scale(1.02);
    }

    /* ── Tab activo ── */
    .tab-item.active {
        background: white;
        color: #374151;
        box-shadow: 5px 0 8px rgba(0, 0, 0, 0.1);
        border-radius: 15px 15px 0 0;
        padding: 10px 24px 10px 40px;
        transform: none;
    }

    .tab-item.active .tab-icon {
        color: var(--tab-color);
    }

    /* ── Focus visible para accesibilidad ── */
    .tab-item:focus-visible {
        outline: 2px solid var(--tab-color, #3b82f6);
        outline-offset: -2px;
    }

    .tab-active-border {
        position: absolute;
        left: 0;
        top: 0;
        bottom: -4px;
        width: 16px;
        border-radius: 15px 0 0 0;
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .tab-active-border.border-visible {
        opacity: 1;
    }

    /* Texto oculto por defecto, visible a partir de 1850px */
    .tab-text {
        font-size: 18px;
        font-weight: 600;
        white-space: nowrap;
        display: none;
    }

    .tab-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tab-icon svg {
        width: 26px;
        height: 26px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
    }

    .tab-item.dragging {
        opacity: 0.5;
        transform: scale(0.95);
    }

    .tab-move-move {
        transition: transform 0.4s ease;
    }

    .tab-panel {
        position: relative;
        z-index: 60;
        background: white;
        padding: 32px 24px;
        border-top-right-radius: 15px;
        border-bottom-right-radius: 15px;
        border-bottom-left-radius: 15px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-top: 0;
        border-left: 16px solid transparent;
        transition: border-color 0.4s ease;
    }

    /* ── 1850px — mostrar texto del tab activo ── */
    @media (min-width: 1850px) {
        .tab-item.active .tab-text {
            display: inline;
        }
    }
</style>

<script>
module.exports = {
    name: 'AcaiVueTabs',
    
    props: {
        tabs: {
            type: Array,
            required: true
        },
        defaultTab: {
            type: String,
            default: null
        },
        storageKey: {
            type: String,
            default: 'acai-tabs-order'
        },
        draggable: {
            type: Boolean,
            default: true
        },
        applyThemeStyles: {
            type: Boolean,
            default: true
        }
    },
    
    data: function() {
        return {
            activeTab: null,
            showBorder: true,
            tabOrder: [],
            isDragging: false,
            dragIndex: null,
            dragOverIndex: null,
            borderTransitionTimer: null
        };
    },
    
    computed: {
        orderedTabs: function() {
            if (this.tabOrder.length === 0) return this.tabs;
            var self = this;
            return this.tabOrder
                .map(function(id) { return self.tabs.find(function(t) { return t.id === id; }); })
                .filter(Boolean);
        },
        /**
         * Mueve el tab activo al principio del array para que su border
         * izquierdo se una visualmente con el border-left del panel.
         */
        sortedTabs: function() {
            var tabs = this.orderedTabs.slice();
            var self = this;
            var activeIndex = -1;
            for (var i = 0; i < tabs.length; i++) {
                if (tabs[i].id === self.activeTab) { activeIndex = i; break; }
            }
            if (activeIndex > 0) {
                var activeTabObj = tabs.splice(activeIndex, 1)[0];
                tabs.unshift(activeTabObj);
            }
            return tabs;
        }
    },
    
    watch: {
        activeTab: {
            immediate: true,
            handler: function(newTab) {
                if (!newTab) return;
                
                var color = this.getTabColor(newTab);
                this.$emit('tab-change', { id: newTab, color: color });
                
                if (this.applyThemeStyles) {
                    this.applyDynamicStyles(color);
                }
            }
        },
        tabs: {
            immediate: true,
            handler: function() {
                this.loadTabOrder();
                if (!this.activeTab && this.tabs.length > 0) {
                    this.activeTab = this.defaultTab || this.tabs[0].id;
                }
            }
        }
    },
    
    mounted: function() {
        this.loadTabOrder();
        if (!this.activeTab && this.tabs.length > 0) {
            this.activeTab = this.defaultTab || this.tabs[0].id;
        }
    },
    
    beforeDestroy: function() {
        var styleEl = document.getElementById('dynamic-tab-theme-' + this._uid);
        if (styleEl) {
            styleEl.remove();
        }
        if (this.borderTransitionTimer) {
            clearTimeout(this.borderTransitionTimer);
        }
    },
    
    methods: {
        selectTab: function(tabId) {
            if (this.activeTab === tabId) return;
            
            var self = this;
            this.showBorder = false;
            this.activeTab = tabId;
            
            // Limpiar timer anterior si existe
            if (this.borderTransitionTimer) {
                clearTimeout(this.borderTransitionTimer);
            }
            this.borderTransitionTimer = setTimeout(function() {
                self.showBorder = true;
                self.borderTransitionTimer = null;
            }, 400);
        },
        
        getOriginalIndex: function(tabId) {
            var tabs = this.orderedTabs;
            for (var i = 0; i < tabs.length; i++) {
                if (tabs[i].id === tabId) return i;
            }
            return -1;
        },
        
        getTabColor: function(tabId) {
            var tab = this.tabs.find(function(t) { return t.id === tabId; });
            return tab ? tab.color : '#64748b';
        },
        
        /**
         * Genera el background del tab sin usar color-mix().
         * Calcula un color más claro sumando un 15% de blanco al color base.
         */
        getTabStyle: function(tab, index) {
            var style = {
                '--tab-color': tab.color,
                zIndex: this.activeTab === tab.id ? 50 : (this.orderedTabs.length - index)
            };
            
            // Solo aplicar gradiente a tabs inactivos
            if (this.activeTab !== tab.id) {
                var lighter = this.lightenColor(tab.color, 15);
                style.background = 'linear-gradient(to bottom, ' + lighter + ', ' + tab.color + ')';
            }
            
            return style;
        },
        
        /**
         * Aclara un color hex sumándole un porcentaje de blanco.
         * Reemplaza color-mix(in srgb, ...) para compatibilidad con navegadores legacy.
         */
        lightenColor: function(hex, percent) {
            if (!hex || hex.charAt(0) !== '#') return hex;
            
            var clean = hex.replace('#', '');
            if (clean.length === 3) {
                clean = clean[0] + clean[0] + clean[1] + clean[1] + clean[2] + clean[2];
            }
            
            var r = parseInt(clean.substring(0, 2), 16);
            var g = parseInt(clean.substring(2, 4), 16);
            var b = parseInt(clean.substring(4, 6), 16);
            
            var factor = percent / 100;
            r = Math.min(255, Math.round(r + (255 - r) * factor));
            g = Math.min(255, Math.round(g + (255 - g) * factor));
            b = Math.min(255, Math.round(b + (255 - b) * factor));
            
            var toHex = function(c) {
                var h = c.toString(16);
                return h.length === 1 ? '0' + h : h;
            };
            
            return '#' + toHex(r) + toHex(g) + toHex(b);
        },
        
        // ── localStorage robusto ──
        
        loadTabOrder: function() {
            try {
                var saved = localStorage.getItem(this.storageKey);
                if (saved) {
                    var savedOrder = JSON.parse(saved);
                    if (!Array.isArray(savedOrder)) {
                        this.tabOrder = this.tabs.map(function(t) { return t.id; });
                        return;
                    }
                    var self = this;
                    var validOrder = savedOrder.filter(function(id) {
                        return self.tabs.some(function(t) { return t.id === id; });
                    });
                    var newTabs = this.tabs
                        .filter(function(t) { return validOrder.indexOf(t.id) === -1; })
                        .map(function(t) { return t.id; });
                    this.tabOrder = validOrder.concat(newTabs);
                } else {
                    this.tabOrder = this.tabs.map(function(t) { return t.id; });
                }
            } catch (e) {
                this.tabOrder = this.tabs.map(function(t) { return t.id; });
            }
        },
        
        saveTabOrder: function() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.tabOrder));
            } catch (e) {
                // Silenciar — incógnito, storage lleno, iframe sandbox, etc.
            }
        },
        
        // ── Estilos dinámicos ──
        
        applyDynamicStyles: function(color) {
            var styleId = 'dynamic-tab-theme-' + this._uid;
            var styleEl = document.getElementById(styleId);
            
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = styleId;
                document.head.appendChild(styleEl);
            }
            
            // Scoped al wrapper del componente
            var scope = '#acai-tabs-' + this._uid;
            
            styleEl.textContent = 
                scope + ' .bg-theme.bg-theme, ' +
                scope + ' .btn.btn-primary.btn-primary:not(.btn-alt):not(label) {' +
                '    background: none !important;' +
                '    background-color: ' + color + ' !important;' +
                '    border-color: ' + color + ' !important;' +
                '}';
        },
        
        // ── Accesibilidad: navegación por teclado ──
        
        focusNextTab: function(currentIndex) {
            var tabs = this.orderedTabs;
            var nextIndex = (currentIndex + 1) % tabs.length;
            this.focusTabAtIndex(nextIndex);
        },
        
        focusPrevTab: function(currentIndex) {
            var tabs = this.orderedTabs;
            var prevIndex = (currentIndex - 1 + tabs.length) % tabs.length;
            this.focusTabAtIndex(prevIndex);
        },
        
        focusTabAtIndex: function(index) {
            var tab = this.orderedTabs[index];
            if (!tab) return;
            var ref = this.$refs['tab-' + tab.id];
            var el = Array.isArray(ref) ? ref[0] : ref;
            if (el) {
                el.focus();
                this.selectTab(tab.id);
            }
        },
        
        // ── Drag & Drop ──
        
        onDragStart: function(event, index) {
            if (!this.draggable) return;
            this.isDragging = true;
            this.dragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(index));
        },
        
        onDragOver: function(event, index) {
            if (!this.draggable) return;
            event.preventDefault();
            this.dragOverIndex = index;
        },
        
        onDrop: function(event, dropIndex) {
            if (!this.draggable) return;
            event.preventDefault();
            var dragIndex = this.dragIndex;

            if (dragIndex !== null && dragIndex !== dropIndex) {
                var newOrder = this.tabOrder.slice();
                var draggedItem = newOrder.splice(dragIndex, 1)[0];
                newOrder.splice(dropIndex, 0, draggedItem);
                this.tabOrder = newOrder;
                this.saveTabOrder();
                
                this.$emit('order-change', this.tabOrder);
            }

            this.dragOverIndex = null;
        },
        
        onDragEnd: function() {
            this.isDragging = false;
            this.dragIndex = null;
            this.dragOverIndex = null;
        },
        
        // ── Métodos públicos ──
        
        setActiveTab: function(tabId) {
            this.selectTab(tabId);
        },
        
        getActiveTab: function() {
            return this.activeTab;
        },
        
        getActiveColor: function() {
            return this.getTabColor(this.activeTab);
        }
    }
};
</script>