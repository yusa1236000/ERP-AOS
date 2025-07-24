<template>
  <div class="quality-inspection-form">
    <div class="page-header">
      <h1>{{ isEditMode ? 'Edit Inspeksi Kualitas' : 'Tambah Inspeksi Kualitas Baru' }}</h1>
    </div>

    <div v-if="loading" class="loading">
      <p>Memuat data...</p>
    </div>
    <div v-else-if="error" class="error-message">
      <p>{{ error }}</p>
      <button @click="isEditMode ? fetchInspectionData() : resetForm()">Coba Lagi</button>
    </div>
    <div v-else>
      <form @submit.prevent="saveInspection" class="inspection-form">
        <div class="form-section">
          <h2>Informasi Dasar</h2>
          <div class="form-row">
            <div class="form-group">
              <label for="product_name">Nama Item</label>
              <input
                id="product_name"
                v-model="form.product_name"
                type="text"
                required
                :class="{ 'error-input': errors.product_name }"
              />
              <span v-if="errors.product_name" class="error-text">{{ errors.product_name }}</span>
            </div>
            <div class="form-group">
              <label for="batch_number">Nomor Batch</label>
              <input
                id="batch_number"
                v-model="form.batch_number"
                type="text"
                required
                :class="{ 'error-input': errors.batch_number }"
              />
              <span v-if="errors.batch_number" class="error-text">{{ errors.batch_number }}</span>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="inspection_date">Tanggal Inspeksi</label>
              <input
                id="inspection_date"
                v-model="form.inspection_date"
                type="date"
                required
                :class="{ 'error-input': errors.inspection_date }"
              />
              <span v-if="errors.inspection_date" class="error-text">{{ errors.inspection_date }}</span>
            </div>
            <div class="form-group">
              <label for="inspector">Inspektor</label>
              <input
                id="inspector"
                v-model="form.inspector"
                type="text"
                required
                :class="{ 'error-input': errors.inspector }"
              />
              <span v-if="errors.inspector" class="error-text">{{ errors.inspector }}</span>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="production_line">Lini Produksi</label>
              <input
                id="production_line"
                v-model="form.production_line"
                type="text"
                :class="{ 'error-input': errors.production_line }"
              />
              <span v-if="errors.production_line" class="error-text">{{ errors.production_line }}</span>
            </div>
            <div class="form-group">
              <label for="status">Status</label>
              <select
                id="status"
                v-model="form.status"
                required
                :class="{ 'error-input': errors.status }"
              >
                <option value="pending">Menunggu</option>
                <option value="completed">Selesai</option>
                <option value="failed">Gagal</option>
              </select>
              <span v-if="errors.status" class="error-text">{{ errors.status }}</span>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h2>Parameter Kualitas</h2>
          <p class="section-desc">Tambahkan parameter kualitas yang diperiksa pada inspeksi ini</p>
          
          <div class="parameters-list">
            <div v-for="(param, index) in form.parameters" :key="index" class="parameter-item">
              <div class="param-header">
                <h3>Parameter #{{ index + 1 }}</h3>
                <button type="button" class="btn-delete-sm" @click="removeParameter(index)">
                  <span>&times;</span>
                </button>
              </div>
              
              <div class="parameter-form">
                <div class="form-row">
                  <div class="form-group">
                    <label :for="'param_name_' + index">Nama Parameter</label>
                    <input
                      :id="'param_name_' + index"
                      v-model="param.name"
                      type="text"
                      required
                    />
                  </div>
                  <div class="form-group">
                    <label :for="'param_type_' + index">Tipe Parameter</label>
                    <select
                      :id="'param_type_' + index"
                      v-model="param.type"
                      required
                      @change="handleParameterTypeChange(param)"
                    >
                      <option value="numeric">Numerik</option>
                      <option value="boolean">Boolean (Ya/Tidak)</option>
                      <option value="text">Teks</option>
                    </select>
                  </div>
                </div>
                
                <div class="form-row" v-if="param.type === 'numeric'">
                  <div class="form-group">
                    <label :for="'param_min_' + index">Nilai Minimum</label>
                    <input
                      :id="'param_min_' + index"
                      v-model.number="param.min_value"
                      type="number"
                      step="0.01"
                    />
                  </div>
                  <div class="form-group">
                    <label :for="'param_max_' + index">Nilai Maksimum</label>
                    <input
                      :id="'param_max_' + index"
                      v-model.number="param.max_value"
                      type="number"
                      step="0.01"
                    />
                  </div>
                  <div class="form-group">
                    <label :for="'param_target_' + index">Nilai Target</label>
                    <input
                      :id="'param_target_' + index"
                      v-model.number="param.target_value"
                      type="number"
                      step="0.01"
                    />
                  </div>
                  <div class="form-group">
                    <label :for="'param_unit_' + index">Satuan</label>
                    <input
                      :id="'param_unit_' + index"
                      v-model="param.unit"
                      type="text"
                    />
                  </div>
                </div>
                
                <div class="form-row" v-if="param.type === 'boolean'">
                  <div class="form-group checkbox-group">
                    <label>
                      <input
                        type="checkbox"
                        v-model="param.expected_result"
                      />
                      Hasil yang diharapkan (centang untuk 'Ya')
                    </label>
                  </div>
                </div>
                
                <div class="form-row" v-if="param.type === 'text'">
                  <div class="form-group">
                    <label :for="'param_expected_' + index">Nilai yang Diharapkan</label>
                    <input
                      :id="'param_expected_' + index"
                      v-model="param.expected_text"
                      type="text"
                    />
                  </div>
                </div>
                
                <div class="form-row">
                  <div class="form-group full-width">
                    <label :for="'param_desc_' + index">Deskripsi/Catatan</label>
                    <textarea
                      :id="'param_desc_' + index"
                      v-model="param.description"
                      rows="2"
                    ></textarea>
                  </div>
                </div>
                
                <div class="form-row" v-if="isEditMode">
                  <div class="form-group full-width">
                    <label :for="'param_result_' + index">Hasil Aktual</label>
                    <div v-if="param.type === 'numeric'">
                      <input
                        :id="'param_result_' + index"
                        v-model.number="param.actual_value"
                        type="number"
                        step="0.01"
                      />
                      <span>{{ param.unit }}</span>
                    </div>
                    <div v-else-if="param.type === 'boolean'" class="radio-group">
                      <label>
                        <input
                          type="radio"
                          :name="'result_' + index"
                          :value="true"
                          v-model="param.result"
                        />
                        Ya
                      </label>
                      <label>
                        <input
                          type="radio"
                          :name="'result_' + index"
                          :value="false"
                          v-model="param.result"
                        />
                        Tidak
                      </label>
                    </div>
                    <div v-else-if="param.type === 'text'">
                      <input
                        :id="'param_result_' + index"
                        v-model="param.result_text"
                        type="text"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <button type="button" @click="addParameter" class="btn-add-parameter">
              <span>+</span> Tambah Parameter Baru
            </button>
          </div>
        </div>

        <div class="form-section">
          <h2>Catatan & Dokumentasi</h2>
          
          <div class="form-group full-width">
            <label for="notes">Catatan Inspeksi</label>
            <textarea
              id="notes"
              v-model="form.notes"
              rows="4"
              :class="{ 'error-input': errors.notes }"
            ></textarea>
            <span v-if="errors.notes" class="error-text">{{ errors.notes }}</span>
          </div>
          
          <div class="form-group full-width">
            <label for="recommendations">Rekomendasi</label>
            <textarea
              id="recommendations"
              v-model="form.recommendations"
              rows="4"
              :class="{ 'error-input': errors.recommendations }"
            ></textarea>
            <span v-if="errors.recommendations" class="error-text">{{ errors.recommendations }}</span>
          </div>
          
          <div class="form-group">
            <label>Dokumentasi (Gambar)</label>
            <div class="file-upload">
              <input
                type="file"
                id="images"
                @change="handleFileUpload"
                multiple
                accept="image/*"
              />
              <label for="images" class="file-upload-label">
                <span>Pilih File</span>
              </label>
            </div>
            <div class="upload-preview">
              <div v-for="(image, index) in uploadedImages" :key="index" class="image-preview">
                <img :src="image.preview" alt="Preview" />
                <button type="button" @click="removeImage(index)" class="btn-remove-image">
                  &times;
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" @click="goBack" class="btn-secondary">Batal</button>
          <button type="submit" class="btn-primary">{{ isEditMode ? 'Perbarui Inspeksi' : 'Simpan Inspeksi' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'QualityInspectionForm',
  props: {
    id: {
      type: [Number, String],
      default: null
    }
  },
  data() {
    return {
      isEditMode: false,
      loading: false,
      error: null,
      form: {
        product_name: '',
        batch_number: '',
        inspection_date: new Date().toISOString().split('T')[0],
        inspector: '',
        production_line: '',
        status: 'pending',
        notes: '',
        recommendations: '',
        parameters: []
      },
      uploadedImages: [],
      errors: {}
    };
  },
  created() {
    // Determine if we're in edit mode
    this.isEditMode = !!this.id;
    
    if (this.isEditMode) {
      this.fetchInspectionData();
    } else {
      // Add one parameter by default for new inspections
      this.addParameter();
    }
  },
  methods: {
    async fetchInspectionData() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/manufacturing/quality/inspections/${this.id}`);
        
        // Map API data to form structure
        const inspection = response.data;
        this.form = {
          product_name: inspection.product_name,
          batch_number: inspection.batch_number,
          inspection_date: new Date(inspection.inspection_date).toISOString().split('T')[0],
          inspector: inspection.inspector,
          production_line: inspection.production_line || '',
          status: inspection.status,
          notes: inspection.notes || '',
          recommendations: inspection.recommendations || '',
          parameters: []
        };
        
        // Map parameters
        if (inspection.parameters && inspection.parameters.length) {
          this.form.parameters = inspection.parameters.map(param => {
            const mappedParam = {
              id: param.id,
              name: param.name,
              type: param.type,
              description: param.description || ''
            };
            
            // Add specific fields based on parameter type
            if (param.type === 'numeric') {
              mappedParam.min_value = param.min_value;
              mappedParam.max_value = param.max_value;
              mappedParam.target_value = param.target_value;
              mappedParam.unit = param.unit || '';
              mappedParam.actual_value = param.actual_value;
            } else if (param.type === 'boolean') {
              mappedParam.expected_result = param.expected_result;
              mappedParam.result = param.result;
            } else if (param.type === 'text') {
              mappedParam.expected_text = param.expected_text || '';
              mappedParam.result_text = param.result_text || '';
            }
            
            return mappedParam;
          });
        }
        
        // Load existing images
        if (inspection.images && inspection.images.length) {
          this.uploadedImages = inspection.images.map(img => ({
            id: img.id,
            url: img.url,
            preview: img.url,
            existing: true
          }));
        }
        
        this.loading = false;
      } catch (err) {
        this.error = 'Gagal memuat data inspeksi: ' + (err.response?.data?.message || err.message);
        this.loading = false;
      }
    },
    
    addParameter() {
      const newParam = {
        name: '',
        type: 'numeric',
        description: '',
        min_value: null,
        max_value: null,
        target_value: null,
        unit: '',
        expected_result: true,
        expected_text: ''
      };
      
      this.form.parameters.push(newParam);
    },
    
    removeParameter(index) {
      if (confirm('Apakah Anda yakin ingin menghapus parameter ini?')) {
        this.form.parameters.splice(index, 1);
      }
    },
    
    handleParameterTypeChange(param) {
      // Reset parameter-specific values when type changes
      if (param.type === 'numeric') {
        param.min_value = null;
        param.max_value = null;
        param.target_value = null;
        param.unit = '';
        delete param.expected_result;
        delete param.expected_text;
        delete param.result;
        delete param.result_text;
      } else if (param.type === 'boolean') {
        param.expected_result = true;
        delete param.min_value;
        delete param.max_value;
        delete param.target_value;
        delete param.unit;
        delete param.expected_text;
        delete param.result_text;
      } else if (param.type === 'text') {
        param.expected_text = '';
        delete param.min_value;
        delete param.max_value;
        delete param.target_value;
        delete param.unit;
        delete param.expected_result;
        delete param.result;
      }
    },
    
    handleFileUpload(event) {
      const files = event.target.files;
      
      if (!files.length) return;
      
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.match('image.*')) continue;
        
        const reader = new FileReader();
        reader.onload = (e) => {
          this.uploadedImages.push({
            file: file,
            preview: e.target.result
          });
        };
        reader.readAsDataURL(file);
      }
    },
    
    removeImage(index) {
      this.uploadedImages.splice(index, 1);
    },
    
    validateForm() {
      this.errors = {};
      let isValid = true;
      
      // Basic validation
      if (!this.form.product_name.trim()) {
        this.errors.product_name = 'Nama produk harus diisi';
        isValid = false;
      }
      
      if (!this.form.batch_number.trim()) {
        this.errors.batch_number = 'Nomor batch harus diisi';
        isValid = false;
      }
      
      if (!this.form.inspection_date) {
        this.errors.inspection_date = 'Tanggal inspeksi harus diisi';
        isValid = false;
      }
      
      if (!this.form.inspector.trim()) {
        this.errors.inspector = 'Nama inspektor harus diisi';
        isValid = false;
      }
      
      // Validate parameters have required fields
      const paramErrors = [];
      this.form.parameters.forEach((param, index) => {
        if (!param.name.trim()) {
          paramErrors.push(`Parameter #${index + 1}: Nama parameter harus diisi`);
          isValid = false;
        }
        
        if (param.type === 'numeric') {
          if (param.min_value !== null && param.max_value !== null && param.min_value > param.max_value) {
            paramErrors.push(`Parameter #${index + 1}: Nilai minimum tidak boleh lebih besar dari nilai maksimum`);
            isValid = false;
          }
        }
      });
      
      if (paramErrors.length) {
        this.errors.parameters = paramErrors;
      }
      
      return isValid;
    },
    
    async saveInspection() {
      if (!this.validateForm()) {
        // Scroll to first error
        const firstErrorEl = document.querySelector('.error-input, .error-text');
        if (firstErrorEl) {
          firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
      }
      
      this.loading = true;
      this.error = null;
      
      try {
        // Create FormData for file uploads
        const formData = new FormData();
        
        // Add inspection data
        formData.append('product_name', this.form.product_name);
        formData.append('batch_number', this.form.batch_number);
        formData.append('inspection_date', this.form.inspection_date);
        formData.append('inspector', this.form.inspector);
        formData.append('production_line', this.form.production_line);
        formData.append('status', this.form.status);
        formData.append('notes', this.form.notes);
        formData.append('recommendations', this.form.recommendations);
        
        // Add parameters as JSON
        formData.append('parameters', JSON.stringify(this.form.parameters));
        
        // Add new images
        this.uploadedImages.forEach((image, index) => {
          if (!image.existing && image.file) {
            formData.append(`images[${index}]`, image.file);
          }
        });
        
        // Add existing image IDs that should be kept
        const existingImageIds = this.uploadedImages
          .filter(img => img.existing)
          .map(img => img.id);
        formData.append('existing_images', JSON.stringify(existingImageIds));
        
        let response;
        if (this.isEditMode) {
          response = await axios.post(`/manufacturing/quality/inspections/${this.id}?_method=PUT`, formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });
        } else {
          response = await axios.post('/manufacturing/quality/inspections', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });
        }
        
        // Redirect after successful save
        this.$router.push({
          path: `/quality-inspections/${response.data.id}`,
          query: { success: 'true' }
        });
      } catch (err) {
        if (err.response && err.response.data && err.response.data.errors) {
          this.errors = err.response.data.errors;
          
          // Scroll to first error
          const firstErrorEl = document.querySelector('.error-input, .error-text');
          if (firstErrorEl) {
            firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        } else {
          this.error = 'Gagal menyimpan data: ' + (err.response?.data?.message || err.message);
        }
        this.loading = false;
      }
    },
    
    resetForm() {
      this.form = {
        product_name: '',
        batch_number: '',
        inspection_date: new Date().toISOString().split('T')[0],
        inspector: '',
        production_line: '',
        status: 'pending',
        notes: '',
        recommendations: '',
        parameters: [{
          name: '',
          type: 'numeric',
          description: '',
          min_value: null,
          max_value: null,
          target_value: null,
          unit: ''
        }]
      };
      this.uploadedImages = [];
      this.errors = {};
      this.error = null;
    },
    
    goBack() {
      this.$router.go(-1);
    }
  }
};
</script>

<style scoped>
.quality-inspection-form {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 30px;
}

.inspection-form {
  background: #fff;
  border-radius: 5px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.form-section {
  padding: 25px;
  border-bottom: 1px solid #eee;
}

.form-section h2 {
  margin-top: 0;
  margin-bottom: 20px;
  font-size: 1.2rem;
  color: #333;
}

.section-desc {
  margin-bottom: 15px;
  color: #666;
  font-size: 0.9rem;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -10px 15px -10px;
}

.form-group {
  flex: 1;
  min-width: 250px;
  padding: 0 10px;
  margin-bottom: 15px;
}

.form-group.checkbox-group {
  display: flex;
  align-items: center;
}

.form-group.full-width {
  flex: 0 0 100%;
}

label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #555;
}

input[type="text"],
input[type="date"],
input[type="number"],
select,
textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

input[type="checkbox"],
input[type="radio"] {
  margin-right: 8px;
}

.radio-group {
  display: flex;
  gap: 20px;
}

.radio-group label {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.error-input {
  border-color: #f44336 !important;
}

.error-text {
  display: block;
  color: #f44336;
  font-size: 0.8rem;
  margin-top: 5px;
}

.parameters-list {
  margin-top: 20px;
}

.parameter-item {
  padding: 15px;
  margin-bottom: 15px;
  border: 1px solid #e0e0e0;
  border-radius: 5px;
  background-color: #f9f9f9;
}

.param-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.param-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 500;
}

.parameter-form {
  background-color: #fff;
  padding: 15px;
  border-radius: 4px;
}

.btn-add-parameter {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 12px;
  border: 2px dashed #ddd;
  border-radius: 5px;
  background: none;
  color: #666;
  font-weight: 500;
  cursor: pointer;
  margin-bottom: 20px;
}

.btn-add-parameter:hover {
  border-color: #4caf50;
  color: #4caf50;
}

.btn-add-parameter span {
  font-size: 1.2rem;
  margin-right: 8px;
}

.btn-delete-sm {
  background: none;
  border: none;
  color: #f44336;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 5px;
  border-radius: 50%;
  height: 30px;
  width: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-delete-sm:hover {
  background-color: #ffebee;
}

.file-upload {
  margin-bottom: 10px;
}

.file-upload input[type="file"] {
  display: none;
}

.file-upload-label {
  display: inline-flex;
  align-items: center;
  padding: 10px 15px;
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  color: #555;
  font-weight: normal;
}

.file-upload-label:hover {
  background-color: #e9e9e9;
}

.upload-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 10px;
}

.image-preview {
  position: relative;
  width: 100px;
  height: 100px;
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
}

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.btn-remove-image {
  position: absolute;
  top: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.5);
  color: white;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 0 0 0 5px;
  cursor: pointer;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.form-actions {
  padding: 25px;
  display: flex;
  justify-content: flex-end;
  gap: 15px;
}

.btn-primary {
  background-color: #4caf50;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  min-width: 150px;
}

.btn-secondary {
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
  padding: 12px 20px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.loading, .error-message {
  text-align: center;
  padding: 50px;
  background: #fff;
  border-radius: 5px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.error-message {
  color: #f44336;
}

.error-message button {
  margin-top: 15px;
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  padding: 8px 15px;
  border-radius: 4px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .form-group {
    flex: 0 0 100%;
  }
}
</style>