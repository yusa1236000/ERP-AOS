<template>
  <div class="quality-inspection-detail">
    <div class="page-header">
      <div class="header-content">
        <h1>Detail Inspeksi Kualitas</h1>
        <div class="header-id">ID: {{ inspection.id }}</div>
      </div>
      <div class="header-actions">
        <button @click="editInspection" class="btn-primary">Edit Inspeksi</button>
        <button @click="printInspection" class="btn-secondary">Cetak</button>
        <button @click="goBack" class="btn-text">Kembali</button>
      </div>
    </div>

    <div v-if="loading" class="loading">
      <p>Memuat data...</p>
    </div>
    <div v-else-if="error" class="error-message">
      <p>{{ error }}</p>
      <button @click="fetchInspectionData">Coba Lagi</button>
    </div>
    <div v-else class="inspection-content">
      <div class="message success" v-if="showSuccessMessage">
        <div class="message-content">
          <span class="icon">✓</span>
          Inspeksi berhasil disimpan!
        </div>
        <button @click="showSuccessMessage = false" class="btn-close">&times;</button>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Informasi Dasar</h2>
        </div>
        <div class="card-body">
          <div class="status-badge-large" :class="inspection.status">
            {{ getStatusText(inspection.status) }}
          </div>
          
          <div class="info-grid">
            <div class="info-group">
              <div class="info-label">Item</div>
              <div class="info-value">{{ inspection.product_name }}</div>
            </div>
            <div class="info-group">
              <div class="info-label">Nomor Batch</div>
              <div class="info-value">{{ inspection.batch_number }}</div>
            </div>
            <div class="info-group">
              <div class="info-label">Tanggal Inspeksi</div>
              <div class="info-value">{{ formatDate(inspection.inspection_date) }}</div>
            </div>
            <div class="info-group">
              <div class="info-label">Inspektor</div>
              <div class="info-value">{{ inspection.inspector }}</div>
            </div>
            <div class="info-group">
              <div class="info-label">Lini Produksi</div>
              <div class="info-value">{{ inspection.production_line || '-' }}</div>
            </div>
            <div class="info-group">
              <div class="info-label">Dibuat Pada</div>
              <div class="info-value">{{ formatDateTime(inspection.created_at) }}</div>
            </div>
            <div class="info-group">
              <div class="info-label">Diperbarui Pada</div>
              <div class="info-value">{{ formatDateTime(inspection.updated_at) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Parameter Kualitas</h2>
          <div class="card-actions">
            <button v-if="hasResults" @click="generateQualityReport" class="btn-secondary">
              Laporan Kualitas
            </button>
          </div>
        </div>
        <div class="card-body">
          <div v-if="inspection.parameters && inspection.parameters.length > 0">
            <div class="parameters-header">
              <div class="param-header-cell name">Parameter</div>
              <div class="param-header-cell specification">Spesifikasi</div>
              <div class="param-header-cell result">Hasil</div>
              <div class="param-header-cell status">Status</div>
            </div>
            
            <div v-for="param in inspection.parameters" :key="param.id" class="parameter-row">
              <div class="param-cell name">
                <div class="param-name">{{ param.name }}</div>
                <div v-if="param.description" class="param-description">{{ param.description }}</div>
              </div>
              
              <div class="param-cell specification">
                <div v-if="param.type === 'numeric'" class="spec-numeric">
                  <div class="spec-row">
                    <div class="spec-label">Min:</div>
                    <div class="spec-value">{{ param.min_value !== null ? `${param.min_value} ${param.unit || ''}` : '-' }}</div>
                  </div>
                  <div class="spec-row">
                    <div class="spec-label">Target:</div>
                    <div class="spec-value">{{ param.target_value !== null ? `${param.target_value} ${param.unit || ''}` : '-' }}</div>
                  </div>
                  <div class="spec-row">
                    <div class="spec-label">Max:</div>
                    <div class="spec-value">{{ param.max_value !== null ? `${param.max_value} ${param.unit || ''}` : '-' }}</div>
                  </div>
                </div>
                
                <div v-else-if="param.type === 'boolean'" class="spec-boolean">
                  Diharapkan: {{ param.expected_result ? 'Ya' : 'Tidak' }}
                </div>
                
                <div v-else-if="param.type === 'text'" class="spec-text">
                  {{ param.expected_text || '-' }}
                </div>
              </div>
              
              <div class="param-cell result">
                <div v-if="param.type === 'numeric'">
                  {{ param.actual_value !== undefined && param.actual_value !== null ? `${param.actual_value} ${param.unit || ''}` : 'Belum diisi' }}
                </div>
                
                <div v-else-if="param.type === 'boolean'">
                  {{ param.result !== undefined ? (param.result ? 'Ya' : 'Tidak') : 'Belum diisi' }}
                </div>
                
                <div v-else-if="param.type === 'text'">
                  {{ param.result_text || 'Belum diisi' }}
                </div>
              </div>
              
              <div class="param-cell status">
                <div v-if="getParameterStatus(param) !== null" class="param-status" :class="getParameterStatus(param)">
                  {{ getParameterStatusText(getParameterStatus(param)) }}
                </div>
                <div v-else>
                  Belum diperiksa
                </div>
              </div>
            </div>
          </div>
          <div v-else class="no-data">
            Tidak ada parameter kualitas yang terdaftar
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Catatan & Rekomendasi</h2>
        </div>
        <div class="card-body">
          <div class="notes-section">
            <div class="notes-group">
              <h3>Catatan Inspeksi</h3>
              <div class="notes-content">
                {{ inspection.notes || 'Tidak ada catatan' }}
              </div>
            </div>
            
            <div class="notes-group">
              <h3>Rekomendasi</h3>
              <div class="notes-content">
                {{ inspection.recommendations || 'Tidak ada rekomendasi' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="inspection.images && inspection.images.length > 0" class="card">
        <div class="card-header">
          <h2>Dokumentasi</h2>
        </div>
        <div class="card-body">
          <div class="images-gallery">
            <div 
              v-for="(image, index) in inspection.images" 
              :key="image.id" 
              class="image-item"
              @click="openImageViewer(index)"
            >
              <img :src="image.url" :alt="`Dokumentasi ${index + 1}`" />
            </div>
          </div>
        </div>
      </div>

      <!-- Image Viewer Modal -->
      <div v-if="showImageViewer" class="image-viewer-modal" @click="closeImageViewer">
        <div class="image-viewer-container" @click.stop>
          <div class="image-viewer-header">
            <h3>Dokumentasi {{ currentImageIndex + 1 }} dari {{ inspection.images.length }}</h3>
            <button @click="closeImageViewer" class="btn-close">&times;</button>
          </div>
          <div class="image-viewer-content">
            <button 
              v-if="inspection.images.length > 1 && currentImageIndex > 0" 
              @click="prevImage" 
              class="image-nav prev"
            >
              &lsaquo;
            </button>
            <div class="image-display">
              <img :src="inspection.images[currentImageIndex]?.url" :alt="`Dokumentasi ${currentImageIndex + 1}`" />
            </div>
            <button 
              v-if="inspection.images.length > 1 && currentImageIndex < inspection.images.length - 1" 
              @click="nextImage" 
              class="image-nav next"
            >
              &rsaquo;
            </button>
          </div>
        </div>
      </div>

      <!-- Success/Error Report Generation Modal -->
      <div v-if="showReportModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ reportError ? 'Error Generating Report' : 'Quality Report Generated' }}</h3>
            <button @click="closeReportModal" class="btn-close">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="reportError" class="error-message">
              {{ reportError }}
            </div>
            <div v-else class="success-message">
              <p>Laporan kualitas berhasil dibuat!</p>
              <div class="report-actions">
                <button @click="downloadReport" class="btn-primary">Download Laporan</button>
                <button @click="previewReport" class="btn-secondary">Pratinjau</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'QualityInspectionDetail',
  props: {
    id: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      inspection: {},
      loading: true,
      error: null,
      showSuccessMessage: false,
      showImageViewer: false,
      currentImageIndex: 0,
      showReportModal: false,
      reportUrl: null,
      reportError: null
    };
  },
  computed: {
    hasResults() {
      if (!this.inspection.parameters || !this.inspection.parameters.length) {
        return false;
      }
      
      return this.inspection.parameters.some(param => {
        if (param.type === 'numeric') {
          return param.actual_value !== undefined && param.actual_value !== null;
        } else if (param.type === 'boolean') {
          return param.result !== undefined;
        } else if (param.type === 'text') {
          return param.result_text && param.result_text.trim() !== '';
        }
        return false;
      });
    }
  },
  created() {
    this.fetchInspectionData();
    // Check if we need to show the success message
    if (this.$route.query.success === 'true') {
      this.showSuccessMessage = true;
      
      // Automatically hide after 5 seconds
      setTimeout(() => {
        this.showSuccessMessage = false;
      }, 5000);
    }
  },
  methods: {
    async fetchInspectionData() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/manufacturing/quality/inspections/${this.id}`);
        this.inspection = response.data;
        this.loading = false;
      } catch (err) {
        this.error = 'Gagal memuat data inspeksi: ' + (err.response?.data?.message || err.message);
        this.loading = false;
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return '-';
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    
    formatDateTime(dateTimeString) {
      if (!dateTimeString) return '-';
      const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      };
      return new Date(dateTimeString).toLocaleDateString('id-ID', options);
    },
    
    getStatusText(status) {
      const statusMap = {
        'pending': 'Menunggu',
        'completed': 'Selesai',
        'failed': 'Gagal'
      };
      return statusMap[status] || status;
    },
    
    getParameterStatus(param) {
      if (param.type === 'numeric') {
        if (param.actual_value === undefined || param.actual_value === null) {
          return null;
        }
        
        const min = param.min_value !== null ? param.min_value : -Infinity;
        const max = param.max_value !== null ? param.max_value : Infinity;
        
        if (param.actual_value < min || param.actual_value > max) {
          return 'fail';
        }
        
        // If we have a target value, we can mark as "warning" if it's not exactly that
        if (param.target_value !== null && param.actual_value !== param.target_value) {
          // But only if it's still within tolerances
          if (param.actual_value >= min && param.actual_value <= max) {
            return 'warning';
          }
        }
        
        return 'pass';
      } else if (param.type === 'boolean') {
        if (param.result === undefined) {
          return null;
        }
        
        return param.result === param.expected_result ? 'pass' : 'fail';
      } else if (param.type === 'text') {
        if (!param.result_text || param.result_text.trim() === '') {
          return null;
        }
        
        // If expected text is empty, any non-empty result is a pass
        if (!param.expected_text || param.expected_text.trim() === '') {
          return 'pass';
        }
        
        // Exact match is a pass
        if (param.result_text.toLowerCase() === param.expected_text.toLowerCase()) {
          return 'pass';
        }
        
        // Fuzzy match (contains or similar) could be a warning
        if (param.result_text.toLowerCase().includes(param.expected_text.toLowerCase()) ||
            param.expected_text.toLowerCase().includes(param.result_text.toLowerCase())) {
          return 'warning';
        }
        
        // Otherwise fail
        return 'fail';
      }
      
      return null;
    },
    
    getParameterStatusText(status) {
      if (status === 'pass') {
        return 'Lulus';
      } else if (status === 'fail') {
        return 'Gagal';
      } else if (status === 'warning') {
        return 'Peringatan';
      }
      return 'Tidak Diketahui';
    },
    
    editInspection() {
      this.$router.push(`/quality-inspections/${this.id}/edit`);
    },
    
    printInspection() {
      window.print();
    },
    
    goBack() {
      this.$router.push('/quality-inspections');
    },
    
    openImageViewer(index) {
      this.currentImageIndex = index;
      this.showImageViewer = true;
      
      // Prevent body scrolling when modal is open
      document.body.style.overflow = 'hidden';
    },
    
    closeImageViewer() {
      this.showImageViewer = false;
      
      // Restore body scrolling
      document.body.style.overflow = '';
    },
    
    prevImage() {
      if (this.currentImageIndex > 0) {
        this.currentImageIndex--;
      }
    },
    
    nextImage() {
      if (this.currentImageIndex < this.inspection.images.length - 1) {
        this.currentImageIndex++;
      }
    },
    
    async generateQualityReport() {
      try {
        this.showReportModal = true;
        this.reportError = null;
        
        const response = await axios.get(`/manufacturing/quality/inspections/${this.id}/report`, {
          responseType: 'blob'
        });
        
        // Create a URL for the blob data
        const blob = new Blob([response.data], { type: 'application/pdf' });
        this.reportUrl = URL.createObjectURL(blob);
      } catch (err) {
        this.reportError = 'Gagal membuat laporan: ' + (err.response?.data?.message || err.message);
      }
    },
    
    closeReportModal() {
      this.showReportModal = false;
      if (this.reportUrl) {
        URL.revokeObjectURL(this.reportUrl);
        this.reportUrl = null;
      }
      this.reportError = null;
    },
    
    downloadReport() {
      if (!this.reportUrl) return;
      
      const link = document.createElement('a');
      link.href = this.reportUrl;
      link.download = `quality-report-${this.inspection.id}.pdf`;
      link.click();
    },
    
    previewReport() {
      if (!this.reportUrl) return;
      
      window.open(this.reportUrl, '_blank');
    }
  }
};
</script>

<style scoped>
.quality-inspection-detail {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 15px;
}

.header-content {
  display: flex;
  flex-direction: column;
}

.header-id {
  font-size: 0.9rem;
  color: #666;
  margin-top: 5px;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  margin-bottom: 25px;
  overflow: hidden;
}

.card-header {
  background-color: #f9f9f9;
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h2 {
  margin: 0;
  font-size: 1.2rem;
  color: #333;
}

.card-actions {
  display: flex;
  gap: 10px;
}

.card-body {
  padding: 20px;
}

.status-badge-large {
  display: inline-block;
  padding: 8px 15px;
  border-radius: 20px;
  font-weight: 500;
  margin-bottom: 20px;
}

.status-badge-large.pending {
  background-color: #fff8e1;
  color: #ffa000;
}

.status-badge-large.completed {
  background-color: #e8f5e9;
  color: #388e3c;
}

.status-badge-large.failed {
  background-color: #ffebee;
  color: #d32f2f;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

.info-group {
  min-width: 250px;
}

.info-label {
  font-size: 0.9rem;
  color: #666;
  margin-bottom: 5px;
}

.info-value {
  font-size: 1.1rem;
}

.parameters-header {
  display: grid;
  grid-template-columns: 2fr 2fr 1fr 1fr;
  background-color: #f4f4f4;
  padding: 10px 15px;
  font-weight: 500;
  border-radius: 5px 5px 0 0;
  border: 1px solid #e0e0e0;
}

.param-header-cell {
  padding: 8px;
}

.parameter-row {
  display: grid;
  grid-template-columns: 2fr 2fr 1fr 1fr;
  border: 1px solid #e0e0e0;
  border-top: none;
}

.parameter-row:last-child {
  border-radius: 0 0 5px 5px;
}

.parameter-row:nth-child(even) {
  background-color: #f9f9f9;
}

.param-cell {
  padding: 12px 15px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.param-name {
  font-weight: 500;
}

.param-description {
  font-size: 0.85rem;
  color: #666;
  margin-top: 5px;
}

.spec-row {
  display: flex;
  margin-bottom: 5px;
  gap: 8px;
}

.spec-label {
  font-size: 0.9rem;
  color: #666;
  min-width: 60px;
}

.param-status {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 0.85rem;
  font-weight: 500;
}

.param-status.pass {
  background-color: #e8f5e9;
  color: #388e3c;
}

.param-status.fail {
  background-color: #ffebee;
  color: #d32f2f;
}

.param-status.warning {
  background-color: #fff8e1;
  color: #ffa000;
}

.notes-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 30px;
}

.notes-group h3 {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 1.1rem;
  color: #333;
}

.notes-content {
  padding: 15px;
  background-color: #f9f9f9;
  border-radius: 5px;
  min-height: 100px;
  white-space: pre-line;
}

.images-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 15px;
}

.image-item {
  border-radius: 5px;
  overflow: hidden;
  cursor: pointer;
  height: 150px;
  transition: transform 0.2s;
}

.image-item:hover {
  transform: scale(1.05);
}

.image-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-viewer-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.85);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.image-viewer-container {
  max-width: 90%;
  max-height: 90%;
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
}

.image-viewer-header {
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #f4f4f4;
  border-bottom: 1px solid #ddd;
}

.image-viewer-header h3 {
  margin: 0;
  font-size: 1.1rem;
}

.image-viewer-content {
  display: flex;
  align-items: center;
  position: relative;
  background-color: #000;
}

.image-display {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 70vh;
  width: 80vw;
  max-width: 1200px;
}

.image-display img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.image-nav {
  position: absolute;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  width: 50px;
  height: 50px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 2rem;
  border-radius: 50%;
  cursor: pointer;
  transition: background 0.2s;
  border: none;
}

.image-nav:hover {
  background: rgba(255, 255, 255, 0.4);
}

.image-nav.prev {
  left: 20px;
}

.image-nav.next {
  right: 20px;
}

.btn-primary {
  background-color: #4caf50;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-secondary {
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
  padding: 10px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-text {
  background: none;
  border: none;
  color: #666;
  padding: 10px 15px;
  cursor: pointer;
  font-weight: 500;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  padding: 0;
  cursor: pointer;
  color: #666;
}

.message {
  padding: 15px 20px;
  margin-bottom: 20px;
  border-radius: 5px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.message.success {
  background-color: #e8f5e9;
  border-left: 4px solid #4caf50;
}

.message-content {
  display: flex;
  align-items: center;
  gap: 10px;
}

.message .icon {
  font-size: 1.2rem;
  color: #4caf50;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  width: 500px;
  max-width: 90%;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.modal-header {
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #f9f9f9;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.2rem;
}

.modal-body {
  padding: 20px;
}

.report-actions {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}

.loading, .error-message, .no-data {
  text-align: center;
  padding: 50px;
  background: #fff;
  border-radius: 5px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.error-message {
  color: #f44336;
}

.success-message {
  color: #388e3c;
}

.no-data {
  color: #666;
  font-style: italic;
}

@media (max-width: 768px) {
  .parameters-header,
  .parameter-row {
    grid-template-columns: 1fr;
  }
  
  .param-header-cell:not(.name) {
    display: none;
  }
  
  .param-cell {
    border-bottom: 1px solid #eee;
    padding: 10px;
  }
  
  .param-cell:last-child {
    border-bottom: none;
  }
  
  .param-cell:not(.name)::before {
    content: attr(data-label);
    font-weight: 500;
    margin-bottom: 5px;
  }
  
  .notes-section {
    grid-template-columns: 1fr;
  }
  
  .image-display {
    width: 100vw;
  }
}

@media print {
  .page-header,
  .card-actions,
  .btn-primary,
  .btn-secondary,
  .message {
    display: none !important;
  }
  
  .card {
    box-shadow: none;
    margin-bottom: 20px;
    break-inside: avoid;
  }
  
  .card-header {
    background-color: #f9f9f9 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  
  .quality-inspection-detail {
    padding: 0;
  }
  
  .status-badge-large {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  
  .image-item {
    page-break-inside: avoid;
  }
  
  .param-status {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>