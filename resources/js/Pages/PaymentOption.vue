<template>
    <div class="card p-6">
        <!-- <b-loading v-model="isLoading" :can-cancel="false"></b-loading> -->

        <div class="card-image is-flex is-justify-content-flex-end">
            <b-button v-if="coins === 0" class="is-small" style="border-radius: 50%;" @click="closeModal">&#x2715;</b-button>
        </div>

        <h1 v-if="mop === null" class="title is-size-3 has-text-centered">Select your mode of payment</h1>
        <p v-if="mop === null" class="has-text-centered">
            Gcash payment is only available if the required amount is greater than or equal to 100 pesos.
        </p>

        <div class="container p-6 is-flex" v-if="mop === null">
            <b-button
                class="is-info"
                style="width: 100%;"
                @click="selectMOP('gcash')"
                :disabled="required_amount < 100"
            >
                ONLINE PAYMENT via GCASH
            </b-button>
            <b-button
                class="is-success ml-2"
                style="width: 100%;"
                @click="selectMOP('coins')"
            >
                COINS SLOT
            </b-button>
        </div>

        <div v-if="mop === 'gcash'" class="is-flex is-flex-direction-column is-align-items-center">
            <h1 class="title is-size-3 has-text-centered">WAITING TO SEND PAYMENT VIA ONLINE PAYMENT...</h1>
            <p class="subtitle">REQUIRED AMOUNT: <b>{{ required_amount.toFixed(2) }} pesos</b></p>

            <img
                v-if="qrUrl"
                :src="qrUrl"
                alt="Scan to Pay via GCash"
                width="300"
                height="300"
            />
        </div>

        <div v-if="mop === 'coins'">
            <div class="has-text-centered">
                <h1 class="title is-size-4">WAITING TO SEND PAYMENT VIA COINS SLOT...</h1>
                <p class="subtitle">REQUIRED AMOUNT: <b>{{ required_amount.toFixed(2) }} pesos</b></p>
                <p>AMOUNT INSERTED</p>
                <p class="is-size-4">{{ coins.toFixed(2) }}</p>
            </div>
        </div>
    </div>
</template>

<script>
import QRCode from 'qrcode'

export default {
    name: 'PreviewFile',
    props: ['transaction'],
    data() {
        return {
            isLoading: false,
            qrUrl: "",
            payment_id: null,
            required_amount: 0,
            coins: 0,
            status: 'unpaid',
            mop: null,
            coinInterval: null,
        }
    },

    mounted() {
        this.required_amount = parseFloat(this.transaction.price || 0)
        console.log(this.transaction)
    },

    methods: {
        closeModal() {
            this.$emit('closePaymentOption')
            this.mop = null
            this.clearCoinInterval()
        },

        selectMOP(mop) {
            this.mop = mop

            if (this.mop === 'gcash') {
                this.generateQRCode()
                console.log("Payment via Online Payment")
            } else {
                console.log("Payment via Coins Slot")
            }
        },

        async generateQRCode() {
            try {
                this.isLoading = true
                const response = await axios.post('/create_payment', { transaction: this.transaction })
                const data = response.data.data

                const checkout_url = data.attributes.checkout_url

                this.qrUrl = await QRCode.toDataURL(checkout_url, {
                    width: 300,
                    margin: 2,
                })

                this.payment_id = data.id
            } catch (error) {
                const errorMessage = error.response?.data?.message || error.message
                this.$buefy.notification.open({
                    duration: 5000,
                    message: `<span class="is-size-4">${errorMessage}</span>`,
                    type: 'is-warning',
                })
            } finally {
                this.isLoading = false
            }
        },

        async getPaymentStatus() {
            try {
                const response = await axios.post('/get_payment_status', { payment_id: this.payment_id })
                const data = response.data.data

                this.coins = parseFloat(data.amount || 0)
                this.status = data.status

                if (data.status === 'paid') {
                    this.$emit('updateStatus')
                    this.resetPayment()
                }
            } catch (error) {
                const errorMessage = error.response?.data?.message || error.message
                this.$buefy.notification.open({
                    duration: 5000,
                    message: `<span class="is-size-4">${errorMessage}</span>`,
                    type: 'is-warning',
                })
            }
        },

        async GetCoins() {
            try {
                const response = await axios.get('/get_coin')
                this.coins += parseFloat(response.data.amount) || 0

                if (this.coins >= this.required_amount) {
                    this.$emit('updateStatus')
                    this.resetPayment()
                }
            } catch (error) {
                const errorMessage = error.response?.data?.message || error.message
                this.$buefy.notification.open({
                    duration: 5000,
                    message: `<span class="is-size-4">${errorMessage}</span>`,
                    type: 'is-warning',
                })
            }
        },

        clearCoinInterval() {
            if (this.coinInterval) {
                clearInterval(this.coinInterval)
                this.coinInterval = null
            }
        },

        resetPayment() {
            this.clearCoinInterval()
            this.mop = null
            this.coins = 0
            this.payment_id = null
            this.status = 'unpaid'
            this.qrUrl = ""
        },
    },

    watch: {
        mop(newValue) {
            this.clearCoinInterval()

            if (newValue === 'coins') {
                this.coinInterval = setInterval(() => {
                    this.GetCoins()
                }, 1000)
            } else if (newValue === 'gcash') {
                this.coinInterval = setInterval(() => {
                    this.getPaymentStatus()
                }, 1000)
            }
        },
    },
}
</script>
