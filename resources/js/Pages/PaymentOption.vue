<template>
  
    <div class="card p-6 ">
        <div class="card-image is-flex is-justify-content-flex-end">
            <b-button class="is-small" style="border-radius: 50%;" @click="closeModal">&#x2715;</b-button>
        </div>
  
        <div class="container p-6 is-flex" v-if="mop === null">
            <b-button class="is-info" style="width: 100%;" @click="selectMOP('gcash')">ONLINE PAYMENT via GCASH</b-button>
            <b-button class="is-success ml-2" style="width: 100%;" @click="selectMOP('coins')">COINS SLOT</b-button>
        </div>

        <div v-if="mop === 'gcash'">
            WAITING TO SEND PAYMENT VIA GCASH...
        </div>

        <div v-if="mop === 'coins'">
            WAITING TO SEND PAYMENT VIA COINS SLOT...
        </div>
    

    </div>

</template>

<script>
export default {
    name: 'PreviewFile',
    data(){
        return {
            mop: null,
        }
    },

    methods: {
        closeModal(){
            this.$emit('closePaymentOption')
        },

        selectMOP(mop){
            this.mop = mop
        }
    }

}
</script>