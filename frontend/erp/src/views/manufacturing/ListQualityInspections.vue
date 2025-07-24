<template>
  <div class="quality-inspections-list">
    <div class="page-header">
      <h1>Daftar Inspeksi Kualitas</h1>
      <button class="btn-primary" @click="navigateToCreate">Tambah Inspeksi Baru</button>
    </div>

    <div class="search-filters">
      <input
        type="text"
        v-model="search"
        placeholder="Cari inspeksi..."
        @input="filterInspections"
      />
      <div class="filter-group">
        <select v-model="statusFilter" @change="filterInspections">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="completed">Selesai</option>
          <option value="failed">Gagal</option>
        </select>
        <input
          type="date"
          v-model="dateFilter"
          @change="filterInspections"
          placeholder="Filter tanggal"
        />
        <button class="btn-secondary" @click="resetFilters">Reset Filter</button>
      </div>
    </div>

    <div v-if="loading" class="loading">
      <p>Memuat data...</p>
    </div>
    <div v-else-if="error" class="error-message">
      <p>{{ error }}</p>
      <button @click="fetchInspections">Coba Lagi</button>
    </div>
    <div v-else>
      <div class="inspections-table">
        <div class="table-header">
          <div class="header-cell" @click="sortBy('id')">
            ID
            <span v-if="sortColumn === 'id'" class="sort-arrow">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </div>
          <div class="header-cell" @click="sortBy('product_name')">
            Produk
            <span v-if="sortColumn === 'product_name'" class="sort-arrow">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </div>
          <div class="header-cell" @click="sortBy('batch_number')">
            Nomor Batch
            <span v-if="sortColumn === 'batch_number'" class="sort-arrow">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </div>
          <div class="header-cell" @click="sortBy('inspection_date')">
            Tanggal Inspeksi
            <span v-if="sortColumn === 'inspection_date'" class="sort-arrow">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </div>
          <div class="header-cell" @click="sortBy('status')">
            Status
            <span v-if="sortColumn === 'status'" class="sort-arrow">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </div>
          <div class="header-cell">Aksi</div>
        </div>
        
        <div v-for="inspection in displayedInspections" :key="inspection.id" class="table-row">
          <div class="cell">{{ inspection.id }}</div>
          <div class="cell">{{ inspection.product_name }}</div>
          <div class="cell">{{ inspection.batch_number }}</div>
          <div class="cell">{{ formatDate(inspection.inspection_date) }}</div>
          <div class="cell">
            <span :class="'status-badge ' + inspection.status">
              {{ getStatusText(inspection.status) }}
            </span>
          </div>
          <div class="cell actions">
            <button class="btn-view" @click="viewInspection(inspection.id)">Lihat</button>
            <button class="btn-edit" @click="editInspection(inspection.id)">Edit</button>
            <button class="btn-delete" @click="confirmDelete(inspection.id)">Hapus</button>
          </div>
        </div>
      </div>

      <div v-if="displayedInspections.length === 0" class="no-data">
        <p>Tidak ada data inspeksi yang ditemukan</p>
      </div>

      <div class="pagination">
        <button 
          :disabled="currentPage === 1" 
          @click="changePage(currentPage - 1)"
          class="page-button"
        >
          &laquo; Sebelumnya
        </button>
        
        <div class="page-info">
          Halaman {{ currentPage }} dari {{ totalPages }}
        </div>
        
        <button 
          :disabled="currentPage === totalPages" 
          @click="changePage(currentPage + 1)"
          class="page-button"
        >
          Selanjutnya &raquo;
        </button>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-content">
        <h3>Konfirmasi Hapus</h3>
        <p>Apakah Anda yakin ingin menghapus inspeksi ini?</p>
        <div class="modal-actions">
          <button class="btn-cancel" @click="showDeleteModal = false">Batal</button>
          <button class="btn-danger" @click="deleteInspection">Hapus</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ListQualityInspections',
  data() {
    return {
      inspections: [],
      filteredInspections: [],
      displayedInspections: [],
      loading: true,
      error: null,
      search: '',
      statusFilter: '',
      dateFilter: '',
      currentPage: 1,
      perPage: 10,
      sortColumn: 'inspection_date',
      sortOrder: 'desc',
      showDeleteModal: false,
      inspectionToDelete: null
    };
  },
  computed: {
    totalPages() {
      return Math.ceil(this.filteredInspections.length / this.perPage);
    }
  },
  created() {
    this.fetchInspections();
  },
  methods: {
    async fetchInspections() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/manufacturing/quality/inspections');
        // Ensure inspections is always an array
        if (Array.isArray(response.data)) {
          this.inspections = response.data;
        } else {
          this.inspections = [];
          this.error = 'Data inspeksi tidak valid.';
        }
        this.filterInspections();
        this.loading = false;
      } catch (err) {
        this.error = 'Gagal memuat data inspeksi: ' + (err.response?.data?.message || err.message);
        this.loading = false;
      }
    },
    
    filterInspections() {
      let filtered = [...this.inspections];
      
      if (this.search) {
        const searchLower = this.search.toLowerCase();
        filtered = filtered.filter(item => 
          item.product_name.toLowerCase().includes(searchLower) ||
          item.batch_number.toLowerCase().includes(searchLower) ||
          item.id.toString().includes(searchLower)
        );
      }
      
      if (this.statusFilter) {
        filtered = filtered.filter(item => item.status === this.statusFilter);
      }
      
      if (this.dateFilter) {
        const filterDate = new Date(this.dateFilter).toISOString().split('T')[0];
        filtered = filtered.filter(item => {
          const itemDate = new Date(item.inspection_date).toISOString().split('T')[0];
          return itemDate === filterDate;
        });
      }
      
      this.filteredInspections = this.sortInspections(filtered);
      this.currentPage = 1;
      this.updateDisplayedInspections();
    },
    
    sortInspections(inspections) {
      return [...inspections].sort((a, b) => {
        let valueA = a[this.sortColumn];
        let valueB = b[this.sortColumn];
        
        // Khusus untuk tanggal, konversi ke timestamp
        if (this.sortColumn === 'inspection_date') {
          valueA = new Date(valueA).getTime();
          valueB = new Date(valueB).getTime();
        }
        
        if (valueA < valueB) {
          return this.sortOrder === 'asc' ? -1 : 1;
        }
        if (valueA > valueB) {
          return this.sortOrder === 'asc' ? 1 : -1;
        }
        return 0;
      });
    },
    
    sortBy(column) {
      if (this.sortColumn === column) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumn = column;
        this.sortOrder = 'asc';
      }
      this.filterInspections();
    },
    
    updateDisplayedInspections() {
      const start = (this.currentPage - 1) * this.perPage;
      const end = start + this.perPage;
      this.displayedInspections = this.filteredInspections.slice(start, end);
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.updateDisplayedInspections();
      }
    },
    
    resetFilters() {
      this.search = '';
      this.statusFilter = '';
      this.dateFilter = '';
      this.filterInspections();
    },
    
    formatDate(dateString) {
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    
    getStatusText(status) {
      const statusMap = {
        'pending': 'Menunggu',
        'completed': 'Selesai',
        'failed': 'Gagal'
      };
      return statusMap[status] || status;
    },
    
    navigateToCreate() {
      this.$router.push('/quality-inspections/create');
    },
    
    viewInspection(id) {
      this.$router.push(`/quality-inspections/${id}`);
    },
    
    editInspection(id) {
      this.$router.push(`/quality-inspections/${id}/edit`);
    },
    
    confirmDelete(id) {
      this.inspectionToDelete = id;
      this.showDeleteModal = true;
    },
    
    async deleteInspection() {
      try {
        await axios.delete(`/manufacturing/quality/inspections/${this.inspectionToDelete}`);
        this.showDeleteModal = false;
        this.inspectionToDelete = null;
        this.fetchInspections();
      } catch (err) {
        this.error = 'Gagal menghapus data: ' + (err.response?.data?.message || err.message);
        this.showDeleteModal = false;
      }
    }
  }
};
</script>

<style scoped>
.quality-inspections-list {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.search-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
  padding: 15px;
  background-color: #f9f9f9;
  border-radius: 5px;
}

.search-filters input[type="text"] {
  flex: 1;
  min-width: 200px;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.filter-group {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.inspections-table {
  width: 100%;
  border: 1px solid #e0e0e0;
  border-radius: 5px;
  overflow: hidden;
}

.table-header {
  display: flex;
  background-color: #f4f4f4;
  font-weight: bold;
  border-bottom: 2px solid #ddd;
}

.header-cell {
  flex: 1;
  padding: 12px 15px;
  cursor: pointer;
  user-select: none;
  position: relative;
}

.header-cell:hover {
  background-color: #e9e9e9;
}

.sort-arrow {
  position: absolute;
  right: 5px;
}

.table-row {
  display: flex;
  border-bottom: 1px solid #e0e0e0;
}

.table-row:nth-child(even) {
  background-color: #f9f9f9;
}

.table-row:hover {
  background-color: #f0f0f0;
}

.cell {
  flex: 1;
  padding: 12px 15px;
  display: flex;
  align-items: center;
}

.actions {
  display: flex;
  gap: 5px;
}

.status-badge {
  padding: 5px 10px;
  border-radius: 15px;
  font-size: 0.85em;
  font-weight: 500;
}

.status-badge.pending {
  background-color: #fff8e1;
  color: #ffa000;
}

.status-badge.completed {
  background-color: #e8f5e9;
  color: #388e3c;
}

.status-badge.failed {
  background-color: #ffebee;
  color: #d32f2f;
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
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-view {
  background-color: #2196f3;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-edit {
  background-color: #ff9800;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-delete {
  background-color: #f44336;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-cancel {
  background-color: #9e9e9e;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-danger {
  background-color: #f44336;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding: 10px 0;
}

.page-button {
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
}

.page-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-size: 0.9em;
  color: #666;
}

.loading, .no-data, .error-message {
  text-align: center;
  padding: 30px;
  color: #666;
}

.error-message {
  color: #d32f2f;
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
  padding: 25px;
  border-radius: 5px;
  width: 400px;
  max-width: 90%;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}
</style>