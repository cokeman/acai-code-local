<template>
    <div>
        <ul class="border-2 bg-gray-100 border-gray-600 rounded-lg px-4 py-3">
            <li v-for="basecolor in colores" class="" v-if="data.layout && data.layout.value ? basecolor.layouts.indexOf(data.layout.value) > -1 : basecolor.layouts.indexOf(0) > -1">
                <label class="color-picker-wrapper flex justify-between items-center my-1 p-2 border rounded-lg bg-white hover:bg-gray-300 cursor-pointer relative">
                    <input type="color" v-model="basecolor.color" class="opacity-0 absolute top-0 left-0" @change="setData">
                    <span class="color-picker-color rounded-full mr-2 w-8 h-8 border border-gray-600 flex-shrink-0 shadow-lg" :title="basecolor.name" :aria-label="basecolor.name" :style="{backgroundColor: basecolor.color}" ></span>
                    <span class="text-left w-full block tw text-xs">{{ basecolor.name }}</span>

                </label>
            </li>
        </ul>

    </div>
</template>
<script>
    module.exports = {
        props:['data','builder','fieldname','save-data','basecolors'],
        data () {
            return {
                colores:{},
            }
        },
        mounted(){
            this.setBaseColors()
        },
        methods:{
            setData(){
                this.data[this.fieldname].newValues.builder_custom.value = JSON.stringify(this.colores);
                this.$emit("save-data");
            },
            setBaseColors(){
                this.colores = this.data[this.fieldname].newValues.builder_custom.value ? JSON.parse(this.data[this.fieldname].newValues.builder_custom.value) : this.basecolors;
            },
            saveData(){
                this.$emit("save-data");
            }
        }
    };
</script>
