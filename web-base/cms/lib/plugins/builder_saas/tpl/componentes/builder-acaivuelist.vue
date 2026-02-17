<template>
    <div class="relative">
        <select class="p-4 w-full appearance-none bg-gray-200 border-gray-600 border-2 rounded-lg shadow" v-model="data[field].newValues.builder_custom.value" @change="saveData()">
            <option v-for="(label,value) in builder.vars[field].options.builder_custom" :value="value">{{ label }}</option>
        </select>
        <span class="absolute top-0 right-0 h-full w-8 pointer-events-none flex items-center justify-center mr-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon w-8 h-8 text-black icon-tabler icon-tabler-chevron-down" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <polyline points="6 9 12 15 18 9" />
            </svg>
        </span>
    </div>
</template>
<script>
    module.exports = {
        props: [
            'builder',
            'data',
            'field',
        ],
        data(){
            return {
            }
        },
        mounted() {
            if(typeof this.data[this.field].newValues.builder_custom.value == 'undefined') {
                Vue.set(this.data[this.field].newValues.builder_custom, 'value', '');
            }
        },
        methods: {
            saveData() {
                this.data[this.field].value = this.data[this.field].newValues.builder_custom.value
                this.$emit("save-data");
            }
        }
    };
</script>
