<template>
  <div class="csv-uploader">
    <div class="container text-center p-4">
      <div class="row">
        <div class="card border-light my-4">
          <div class="card-header">
            <h5 class="card-title">CSV name extractor test</h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitForm" enctype="multipart/form-data">
              <label for="csvfile">Select a CSV file to upload:</label>
              <!-- Use a key for each form element -->
              <input type="file" name="csvfile" id="csvfile" :key="csvKey">
              <input type="submit" name="submit" value="Upload CSV">
            </form>
            <div v-if="names.length > 0">
              <ul>
                <li v-for="(name, index) in names" :key="index">
                  <!-- display names -->
                  {{ `${name.title} ${name.first_name} ${name.last_name}` }}
                </li>
              </ul>
            </div>
            <div v-if="errorMessage">
              <!--error message if controller returns error -->
              <p class="error-message">{{ errorMessage }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      names: [], // Initialize empty array for parsed names
      errorMessage: '', // Initialize empty string for error messages
      csvKey: 0 // Initialize key for file input element
    }
  },
  methods: {
    submitForm() {
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const formData = new FormData(document.querySelector('form'));
      axios.post('/submit-form', formData, {
        headers: {
          'X-CSRF-TOKEN': token,
          'Content-Type': 'multipart/form-data'
        }
      })
      .then(response => {
      if(response.data == 'error'){
      console.log('error');
      }else{
      this.names = response.data;
      this.csvKey++; // Increment key to clear file input element
      }

      })
      .catch(error => {
        this.errorMessage = error.response.data; // Set error message
      });
    }
  }
}
</script>
