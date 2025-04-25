<template>
    <section class="is-fullheight py-6 has-background-black-bis full-height">
        <div class="hero-head container has-text-centered">
            <p class="title has-text-light">PRINTIFY</p>
            <p class="subtitle has-text-light">Transfer a file via QR Code</p>
        </div>
        <div class="hero-body">
            <div class="container card  p-6 ">
                <div class="has-text-centered" v-if="page_data.length <= 0">
                    <p class="title is-size-3">Upload Files</p>
                    <p class="subtitle is-size-6">Only pdf and docs/docx are allowed</p>

                    <div class="is-flex is-align-items-center is-flex-direction-column">
                        <b-field class="file" v-if="files.length <= 0">
                            <b-upload v-model="files" accept=".pdf, .doc, .docx" multiple>
                                <a class="button is-info is-fullwidth">
                                    <span>Click to upload</span>
                                </a>
                            </b-upload>
                        </b-field>

                        <b-button @click="files = []" v-if="files.length > 0">Re-upload files</b-button>

                        <div class="mt-2" v-if="files.length">
                            <a v-for="(file, index) in files" :key="index" class="mr-2 is-size-7-mobile" style="display: block;">{{ file.name }}</a>
                        </div>

                        <b-button type="is-info" @click="submitFiles" class="mt-2" :disabled="files.length <= 0">Submit</b-button>
                    </div>
                </div>
                <div v-else class="has-text-centered">
                    <p class="title is-size-3 is-size-7-mobile">Sorry! The system is processing</p>
                    <p class="subtitle is-size-6 is-size-7-mobile">File upload is temporarily disabled while the system is printing in progress.</p>
                </div>
            </div>
        </div>
      
    </section>
</template>

<script>
export default {
    name: 'Uploader',
    data() {
        return {
            isLoading: false,
            isProcess: false,
            files: [],
            page_data: []
        }
    },

    mounted(){
        // setInterval(() => {
            this.getFiles()
        // }, 1000)
    },

    methods: {
        async submitFiles(){
            try {
                if (!this.files.length) {
                    this.$buefy.notification.open({
                        duration: 5000,
                        message: `<span class="is-size-4">Please select at least one file.</span>`,
                        type: 'is-warning',
                    });
                    return;
                }

                this.isLoading = true
                const form = new FormData()

                this.files.forEach((file, index) => {
                    form.append(`file_${index}`, file); 
                });

                const response = await axios.post('/upload_files', form, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    }
                })

                this.$buefy.notification.open({
                    duration: 5000,
                    message: `<span class="is-size-4">${response.data.message}</span>`,
                    type: 'is-success',
                })

                this.getFiles()
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

        async getFiles() {
            try {
                this.isLoading = true

                const response = await axios.get('/get_files');
                const response_files = response.data.files;

                this.page_data = response.data.page_data
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
    min-height: 100vh;
}

body::-webkit-scrollbar {
  display: none; 
}
</style>