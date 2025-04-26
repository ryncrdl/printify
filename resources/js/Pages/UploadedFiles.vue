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
                <option v-for="(option, index) in ['Colored', 'Black & White']" :key="index">{{ option }}</option>
              </b-select>
            </b-field>
           
          </div>

          <p>
            <b>Status:</b> <span :class="status === 'unpaid' ? 'has-text-danger' : 'has-text-success'">{{ status.toUpperCase() }}</span> <br>   
            <b>Total Page: </b> {{ totalPages }} {{ totalPages > 1 ? "Pages" : "Page" }}<br>
            <b>Total Price: </b> &#x20B1; {{ totalPrice.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
          </p>
          <div class="">
            <b-button v-if="status === 'unpaid'" type="is-danger" @click="isPayment = true">PAY</b-button>
            <b-button v-else type="is-info" class="ml-2" @click="autoPrintFiles">PRINT</b-button>
          </div>
        </div>
    </div>
  

    <b-modal v-model="isPreview" full-screen :can-cancel="false">
      <PreviewFile :file="file" @closeModal="closeModal"/>
    </b-modal>

    <b-modal v-model="isPayment" :can-cancel="false">
      <PaymentOption @closePaymentOption="closePaymentOption" @updateStatus="updateStatus" :transaction="transaction"/>
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
      transaction: {},
      transaction_id: '',
      paper_size: 'Long',
      color: 'Colored',
      page_data: [],
      status: 'unpaid',
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

        if(response.data.files <= 0){
          window.location.href = "dashboard"
        }

        const response_files = response.data.files;
        const transaction = response.data.transaction;

        this.paper_size     = transaction.size;
        this.color          = transaction.color;
        this.status         = transaction.status
        this.transaction_id = transaction.transaction_id
        this.transaction    = transaction

        this.page_data = response.data.page_data;

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
        this.updatePrice()
      } catch (error) {
        const errorMessage = error.response.data.message || error.message;
        this.$buefy.notification.open({
          duration: 5000,
          message: `<span class="is-size-4">${errorMessage}</span>`,
          type: 'is-warning',
        })
      } finally {
        this.isLoading = false;
      }
    },

    async updatePrice(){
      try {
        const form = {
          transaction_id: this.transaction_id,
          price: this.totalPrice,
          pages: this.totalPages,
          size:  this.paper_size,
          color: this.color
        }
        
        const response = await axios.post('/update_price', {params: form});
        const transaction = response.data.data
        this.transaction.price = transaction.price
        
      } catch (error) {
        const errorMessage = error.message;
        this.$buefy.notification.open({
          duration: 5000,
          message: `<span class="is-size-4">${errorMessage}</span>`,
          type: 'is-warning',
        })
      }finally {
        this.isLoading = false;
      }
    },  

    async updateStatus(){
      try {
        const form = {
          transaction_id: this.transaction_id,
          status: 'paid',
        }

        const response = await axios.post('/update_status', {params: form});

        this.$buefy.notification.open({
          duration: 5000,
          message: `<span class="is-size-4">${response.data.message}</span>`,
          type: 'is-success',
        })

        this.closePaymentOption()
        this.getFiles()
      } catch (error) {
        const errorMessage = error.message;
        this.$buefy.notification.open({
          duration: 5000,
          message: `<span class="is-size-4">${errorMessage}</span>`,
          type: 'is-warning',
        })
      }finally {
        this.isLoading = false;
      }
    },  

    async autoPrintFiles() {
  try {
    for (const file of this.files) {
      const fileExtension = file.name.split('.').pop().toLowerCase();

      if (fileExtension !== 'pdf') {
        this.$buefy.notification.open({
          message: `<span class="is-size-5">Cannot print "${file.name}". Only PDF files are printable directly.</span>`,
          type: 'is-warning',
        });
        continue; // Skip non-PDF files
      }

      const fileURL = URL.createObjectURL(file); // Create URL for PDF

      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        this.$buefy.notification.open({
          message: 'Pop-up blocked. Please allow pop-ups for this website.',
          type: 'is-danger',
        });
        return;
      }

      printWindow.document.write(`
        <html>
          <head>
            <title>Print File</title>
            <style>
              @page {
                size: ${this.paper_size === 'Long' ? '8.5in 13in' : '8.5in 11in'};
              }
              body {
                margin: 0;
                padding: 0;
              }
              embed {
                width: 100%;
                height: 100vh;
              }
            </style>
          </head>
          <body>
            <embed src="${fileURL}" type="application/pdf" />
          </body>
        </html>
      `);

      printWindow.document.close();
      printWindow.focus();

      printWindow.onload = () => {
        setTimeout(() => { // give it time to load the PDF
          printWindow.print();
          printWindow.onafterprint = () => {
            printWindow.close();
            URL.revokeObjectURL(fileURL); // clean up
          };
        }, 500); // 0.5 second wait to ensure the file is ready
      };
    }
  } catch (error) {
    console.error('Print error:', error);
    this.$buefy.notification.open({
      message: `<span class="is-size-4">${error.message}</span>`,
      type: 'is-warning',
    });
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
  },

  computed: {
    totalPages() {
      return this.page_data.reduce((total, file) => {
        return total + (Number(file.total_page) || 0);
      }, 0);
    },

    totalPrice() {
      const pages = this.totalPages; // No parentheses

      if (this.paper_size === 'Long') {
        return this.color === 'Colored' ? pages * 8 : pages * 5;
      } else {
        return this.color === 'Colored' ? pages * 7 : pages * 4;
      }
    }
  },

  watch: {
    paper_size(newVal){
      this.updatePrice()
    },
    color(newVal){
      this.updatePrice()
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
