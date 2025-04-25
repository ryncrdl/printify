<template>
    <section class="is-fullheight py-6 has-background-black-bis full-height">
        <div class="hero-head container has-text-centered">
            <p class="title has-text-light">PRINTIFY</p>
            <p class="subtitle has-text-light">Your payment was successfully received. You may now proceed to print your files.</p>
        </div>
        <div class="hero-body">
            <div class="container card  p-6">
                <p class="title is-size-3 has-text-centered">UPLOADED FILES</p>
 
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

        mounted(){
           this.updateStatus()
        },


        methods: {
            async updateStatus() {
                try {
                    this.isLoading = true

                    console.log("Update")
                }catch(error){
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