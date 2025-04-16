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
                            :rounded="rounded"
                        ></b-image>
                    </div>
                </div>
                <div class="has-text-centered">
                    <p class="title is-size-3">BLUETOOTH</p>
                    <p class="subtitle is-size-6">Share your file via Bluetooth and <br/> send it to the Bluetooth device listed below.</p>

                    <div>
                        <b-image
                            src="/images/bluetooth.png"
                            alt="The Buefy Logo"
                            ratio="1by1"
                            :rounded="rounded"
                        ></b-image>
                        <p class="subtitle">Bluetooth Name: <b>printify</b></p>
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