<template>
    <section class="is-fullheight py-6 has-background-black-bis full-height">
        <div class="hero-head container has-text-centered">
            <p class="title has-text-light">PRINTIFY</p>
            <p class="subtitle has-text-light">Transfer a file via QR Code or Bluetooth</p>
        </div>
        <div class="hero-body">
            <div class="container card  p-6 is-flex is-justify-content-space-evenly">
                <div class="has-text-centered">
                    <p class="title is-size-3">QR CODE</p>
                    <p class="subtitle is-size-6">Use the built-in QR code scanner on your phone.</p>

                    <div>
                        <b-image
                            src="/images/qr_code.png"
                            alt="The Buefy Logo"
                            ratio="1by1"
                        ></b-image>
                    </div>
                </div>
                <div class="has-text-centered">
                    <p class="title is-size-3">BLUETOOTH</p>
                    <p class="subtitle is-size-6">Share your file via Bluetooth</p>

                    <div>
                        <b-image
                            src="/images/bluetooth.png"
                            alt="The Buefy Logo"
                            ratio="1by1"
                        ></b-image>
                        <p class="subtitle">Bluetooth Name: <b>printify</b></p>
                        <b-button type="is-info" @click="sendViaBluetooth">Send files via Bluetooth</b-button>
                    </div>
                </div>
            </div>
        </div>
      
    </section>
</template>

<script>
    export default {
        data() {
            return {
                checkbox: false,
                checkboxCustom: 'Yes'
            }
        },


        methods: {
            async sendViaBluetooth(){
                try {
                    const response = await axios.get('/receive_bluetooth');
                    this.$buefy.notification.open({
                        duration: 5000,
                        message: `<span class="is-size-4">${response.data.message}</span>`,
                        type: 'is-success',
                    })
                    
                } catch (error) {
                    const errorMessage = error.response.data.message || error.message
                    this.$buefy.notification.open({
                        duration: 5000,
                        message: `<span class="is-size-4">${errorMessage}</span>`,
                        type: 'is-warning',
                    })
                }
            }
        }
    }
</script>

<style>
.full-height{
    height: 100vh;
    overflow-y: hidden;
    
}

body::-webkit-scrollbar {
  display: none; 
}
</style>