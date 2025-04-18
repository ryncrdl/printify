<template>
    <div class="is-fullheight p-2">
        <div class="card-image is-flex is-justify-content-flex-end">
            <b-button class="is-small" style="border-radius: 50%;" @click="closeModal">&#x2715;</b-button>
        </div>
      
            <div class="container">
                <p class="has-text-centered is-size-4 has-text-weight-bold">PREVIEW FILE</p>
                <VueFilesPreview :file="file"/>
            </div>
      
    </div>
</template>

<script>
  import { VueFilesPreview } from 'vue-files-preview';
export default {
    name: 'PaymentOption',
    props: ['file'],
    components: {
        VueFilesPreview
    },
    data(){
        return {
        
        }
    },

    mounted(){

    },      

    methods: {
        closeModal(){
            this.$emit('closeModal')
        },

    
    }

}
</script>