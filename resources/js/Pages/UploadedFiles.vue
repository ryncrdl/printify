<template>
  <section class="is-fullheight py-6 has-background-black-bis full-height">
    <b-loading v-model="isLoading" :can-cancel="false"></b-loading>

    <div class="hero-head container has-text-centered">
      <p class="title has-text-light">PRINTIFY</p>
      <p class="subtitle has-text-light">Transfer a file via QR Code or Bluetooth</p>
    </div>

    <div class="container card p-6 mt-6 is-flex is-flex-direction-column is-justify-content-space-between" style="height: 80%;">
        <div>
          <h1 class="is-size-4 has-text-centered">You can now preview the uploaded files by clicking on each file below.</h1>
          <div class="p-4 is-flex flex-wrap gap-2">
            <b-button type="is-info" v-for="(selected_file, index) in files" :key="index" @click="selectFile(selected_file)">{{ selected_file?.name }}</b-button>
          </div>
        </div>

    

        <div class="is-flex is-align-items-center is-justify-content-space-between">
          <div>
            <b-field horizontal>
              <template #label>
                <span style="white-space: nowrap;">Paper</span>
              </template>

              <b-select v-model="paper_size" size="is-small">
                <option v-for="(option, index) in ['Long', 'Short']" :key="index">{{ option }}</option>
              </b-select>
            </b-field>

            <b-field horizontal>
              <template #label>
                <span style="white-space: nowrap;">Color</span>
              </template>

              <b-select v-model="color" size="is-small">
                <option v-for="(option, index) in ['Color', 'Black & White']" :key="index">{{ option }}</option>
              </b-select>
            </b-field>
           
          </div>

          <p>
            <b>Status:</b> <span class="has-text-danger">Unpaid</span> <br> 
            <b>Total Price: </b> {{ 100.00 }}
          </p>
          <div class="">
            <b-button type="is-danger" @click="isPayment = true">PAY</b-button>
            <b-button type="is-info" class="ml-2">PRINT</b-button>
          </div>
        </div>
    </div>
  

    <b-modal v-model="isPreview" full-screen :can-cancel="false">
      <PreviewFile :file="file" @closeModal="closeModal"/>
    </b-modal>

    <b-modal v-model="isPayment" :can-cancel="false">
      <PaymentOption @closePaymentOption="closePaymentOption"/>
    </b-modal>
  </section>
</template>

<script>
import PreviewFile from './PreviewFile.vue';
import PaymentOption from './PaymentOption.vue';
export default {
  name: 'UploadedFiles',

  components: {
    PreviewFile,
    PaymentOption
  },

  data() {
    return {
      receivedFile: null,
      isLoading: false,
      isPreview: false,
      isPayment: false,
      paper_size: 'Long',
      color: 'Color',
      files: [],
      file: {}
    }
  },

  mounted() {
    this.getFiles()
  },


  methods: {
    async getFiles() {
      try {
        this.isLoading = true

        const response = await axios.get('/get_files');
        const response_files = response.data.files;

        const filePromises = response_files.map(async (fileMeta) => {
          const fileUrl = fileMeta.path;
          const fileName = fileMeta.file_name;

          const blobResponse = await fetch(fileUrl);
          const blob = await blobResponse.blob();

          const file = new File([blob], fileName, {
            type: blob.type
          });

          // Include original fileMeta too if needed
          file.meta = fileMeta;

          return file;
        });

        this.files = await Promise.all(filePromises);

      } catch (error) {
        const errorMessage = error.message;
        this.$buefy.notification.open({
          duration: 5000,
          message: `<span class="is-size-4">${errorMessage}</span>`,
          type: 'is-warning',
        })
      } finally {
        this.isLoading = false;
      }
    },


    selectFile(selected_file){
        this.file = selected_file
        this.isPreview = true
    },

    closeModal(){
      this.isPreview = false
    },

    closePaymentOption(){
      this.isPayment = false
    }
  }

};


</script>

<style scoped>
.full-height {
  height: 100vh;
  overflow-y: hidden;
}

body::-webkit-scrollbar {
  display: none;
}

.mt-2 {
  margin-top: 1rem;
}
</style>
