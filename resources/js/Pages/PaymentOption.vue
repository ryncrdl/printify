<template>
  
    <div class="card p-6 ">
        <!-- <b-loading v-model="isLoading" :can-cancel="false"></b-loading> -->

        <div class="card-image is-flex is-justify-content-flex-end">
            <b-button class="is-small" style="border-radius: 50%;" @click="closeModal">&#x2715;</b-button>
        </div>
  
        <div class="container p-6 is-flex" v-if="mop === null">
            <b-button class="is-info" style="width: 100%;" @click="selectMOP('gcash')">ONLINE PAYMENT via GCASH</b-button>
            <b-button class="is-success ml-2" style="width: 100%;" @click="selectMOP('coins')">COINS SLOT</b-button>
        </div>

        <div v-if="mop === 'gcash'">
            WAITING TO SEND PAYMENT VIA GCASH...

            <div v-if="qrUrl">
                <b-image
                    :src="qrUrl"
                    alt="Scan to Pay via GCash"
                    ratio="1by1"
                ></b-image>
            </div>
        </div>

        <div v-if="mop === 'coins'">
            WAITING TO SEND PAYMENT VIA COINS SLOT...

            <div>
                COINS INSERTED: 
                {{ coins }}
            </div>
        </div>
    

    </div>

</template>

<script>
export default {
    name: 'PreviewFile',
    props: ['transaction'],
    data(){
        return {
            isLoading: false,
            qrUrl: "",
            coins: 0,
            mop: null,
        }
    },

    mounted(){
       
    },

    methods: {
        closeModal(){
            this.$emit('closePaymentOption')
        },

        selectMOP(mop){
            this.mop = mop;

            if(this.mop === 'gcash'){
                this.generateQRCode();

                console.log("Payment via Online Payment")
            }else{
                console.log("Payment via Coins Slot")
            }
        },

        async generateQRCode(){
            try {
                this.isLoading = true

                const response = await axios.post('/create_payment', {transaction: this.transaction})
                const data = response.data;
                this.qrUrl = data.qr_code;

                window.location.href = data.checkout_url

            } catch (error) {
                const errorMessage = error.response.data.message || error.message;
                this.$buefy.notification.open({
                    duration: 5000,
                    message: `<span class="is-size-4">${errorMessage}</span>`,
                    type: 'is-warning',
                })
            }finally {
               this.isLoading = false
            }
        },

        async GetCoins(){
            try {
                this.isLoading = true;
                const response = await axios.get('/get_coin')

                this.coins += parseFloat(response.data.amount) || 0;
            } catch (error) {
                const errorMessage = error.response.data.message || error.message;
                this.$buefy.notification.open({
                    duration: 5000,
                    message: `<span class="is-size-4">${errorMessage}</span>`,
                    type: 'is-warning',
                })
            }finally {
                this.isLoading = false
            }
        }
    },

    watch: {
        mop(newvalue){
            if(newvalue === 'coins'  && !this.isLoading){
                setInterval(async () => {
                    await this.GetCoins();
                }, 1000);
            }
        }
    }

}
</script>