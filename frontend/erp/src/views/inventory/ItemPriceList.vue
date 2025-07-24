<!-- src/views/inventory/ItemPriceList.vue -->
<template>
  <div class="container-fluid">
    <div class="page-header">
      <h1 class="h2 mb-4">Harga Item</h1>
    </div>

    <!-- Filter untuk memilih item -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row align-items-end">
          <div class="col-md-8">
            <label class="form-label">Pilih Item</label>
            <select class="form-select" v-model="selectedItemId" @change="loadItemPrices">
              <option value="">-- Pilih Item --</option>
              <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                {{ item.item_code }} - {{ item.name }}
              </option>
            </select>
          </div>
          <div class="col-md-4 text-end">
            <button 
              class="btn btn-primary" 
              @click="openAddPriceModal"
              :disabled="!selectedItemId"
            >
              <i class="fas fa-plus me-2"></i>Tambah Harga
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter harga -->
    <div v-if="selectedItemId" class="card mb-4">
      <div class="card-body">
        <div class="row">
          <div class="col-md-3">
            <label class="form-label">Tipe Harga</label>
            <select class="form-select" v-model="priceTypeFilter" @change="filterPrices">
              <option value="">Semua</option>
              <option value="purchase">Beli</option>
              <option value="sale">Jual</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" v-model="activeFilter" @change="filterPrices">
              <option value="">Semua</option>
              <option value="1">Aktif</option>
              <option value="0">Tidak Aktif</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Currency</label>
            <select class="form-select" v-model="currencyFilter" @change="filterPrices">
              <option value="">Semua</option>
              <option v-for="currency in currencies" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">&nbsp;</label>
            <div class="form-check">
              <input 
                class="form-check-input" 
                type="checkbox" 
                v-model="currentOnlyFilter" 
                @change="filterPrices"
                id="currentOnlyCheck"
              >
              <label class="form-check-label" for="currentOnlyCheck">
                Hanya yang sedang berlaku
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Daftar harga -->
    <div v-if="selectedItemId" class="card">
      <div class="card-body">
        <div v-if="isLoading" class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        <div v-else-if="filteredPrices.length === 0" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-tags"></i>
          </div>
          <h3>Tidak ada data harga</h3>
          <p>Belum ada harga yang ditetapkan untuk item ini</p>
        </div>
        <div v-else>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Tipe</th>
                  <th>Harga</th>
                  <th>Currency</th>
                  <th>Min Qty.</th>
                  <th>Status</th>
                  <th>Mulai</th>
                  <th>Berakhir</th>
                  <th>Customer</th>
                  <th>Vendor</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in filteredPrices" :key="item.price_id">
                  <td>{{ item.price_type === 'purchase' ? 'Beli' : 'Jual' }}</td>
                  <td>{{ formatPrice(item.price) }}</td>
                  <td>{{ item.currency_code }}</td>
                  <td>{{ item.min_quantity }}</td>
                  <td>
                    <span v-if="item.is_active" class="badge bg-success">Aktif</span>
                    <span v-else class="badge bg-secondary">Tidak Aktif</span>
                  </td>
                  <td>{{ formatDate(item.start_date) }}</td>
                  <td>{{ formatDate(item.end_date) }}</td>
                  <td>{{ getCustomerDisplay(item) }}</td>
                  <td>{{ getVendorDisplay(item) }}</td>
                  <td>
                    <button 
                      class="btn btn-sm btn-outline-primary me-2" 
                      @click="editPrice(item)"
                      title="Edit"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      class="btn btn-sm btn-outline-danger" 
                      @click="confirmDelete(item)"
                      title="Hapus"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showPriceModal" class="modal" tabindex="-1" style="display: block;">
      <div class="modal-backdrop fade show"></div>
      <div class="modal-dialog modal-md">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ editingMode ? 'Edit Harga Item' : 'Tambah Harga Item' }}
            </h5>
            <button type="button" class="btn-close" @click="closePriceModal"></button>
          </div>
          <form @submit.prevent="submitForm">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Tipe Harga</label>
                <select class="form-select" v-model="form.price_type" required>
                  <option value="purchase">Beli</option>
                  <option value="sale">Jual</option>
                </select>
              </div>
  
              <div class="mb-3">
                <label class="form-label">Harga</label>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model="form.price" 
                  step="0.01" 
                  required
                >
              </div>
  
              <div class="mb-3">
                <label class="form-label">Currency</label>
                <select class="form-select" v-model="form.currency_code" required>
                  <option value="IDR">IDR</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                  <option value="SGD">SGD</option>
                </select>
              </div>
  
              <div class="mb-3">
                <label class="form-label">Minimum Quantity</label>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model="form.min_quantity"
                  step="1"
                  min="1"
                >
              </div>
  
              <div v-if="form.price_type === 'purchase'" class="mb-3">
                <label class="form-label">Vendor (Opsional)</label>
                <select class="form-select" v-model="form.vendor_id">
                  <option value="">-- Pilih Vendor --</option>
                  <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                    {{ vendor.vendor_code }} - {{ vendor.name }}
                  </option>
                </select>
              </div>
  
              <div v-if="form.price_type === 'sale'" class="mb-3">
                <label class="form-label">Customer (Opsional)</label>
                <select class="form-select" v-model="form.customer_id">
                  <option value="">-- Pilih Customer --</option>
                  <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                    {{ customer.customer_code }} - {{ customer.name }}
                  </option>
                </select>
              </div>
  
              <div class="mb-3">
                <label class="form-label">Tanggal Mulai</label>
                <input 
                  type="date" 
                  class="form-control" 
                  v-model="form.start_date"
                >
              </div>
  
              <div class="mb-3">
                <label class="form-label">Tanggal Berakhir</label>
                <input 
                  type="date" 
                  class="form-control" 
                  v-model="form.end_date"
                >
              </div>
  
              <div class="mb-3">
                <div class="form-check">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    v-model="form.is_active"
                    id="isActiveCheck"
                  >
                  <label class="form-check-label" for="isActiveCheck">
                    Aktif
                  </label>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closePriceModal">
                Batal
              </button>
              <button type="submit" class="btn btn-primary" :disabled="submitLoading">
                <span v-if="submitLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                {{ editingMode ? 'Update' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div v-if="showDeleteModal" class="modal" tabindex="-1" style="display: block;">
      <div class="modal-backdrop fade show"></div>
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Konfirmasi Hapus</h5>
            <button type="button" class="btn-close" @click="showDeleteModal = false"></button>
          </div>
          <div class="modal-body">
            <p>Yakin ingin menghapus harga {{ deleteItem?.price }} {{ deleteItem?.currency_code }}?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">
              Batal
            </button>
            <button type="button" class="btn btn-danger" @click="deletePrice">
              Hapus
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ItemPriceList',
  data() {
    return {
      items: [],
      selectedItemId: '',
      prices: [],
      filteredPrices: [],
      isLoading: false,
      showPriceModal: false,
      editingMode: false,
      selectedPrice: null,
      showDeleteModal: false,
      deleteItem: null,
      priceTypeFilter: '',
      activeFilter: '',
      currencyFilter: '',
      currentOnlyFilter: false,
      currencies: ['IDR', 'USD', 'EUR', 'SGD'],
      vendors: [],
      customers: [],
      submitLoading: false,
      form: {
        price_type: 'purchase',
        price: 0,
        currency_code: 'IDR',
        min_quantity: 1,
        vendor_id: '',
        customer_id: '',
        start_date: '',
        end_date: '',
        is_active: true
      }
    };
  },
  mounted() {
    this.loadItems();
    this.loadVendors();
    this.loadCustomers();
  },
  methods: {
    async loadItems() {
      try {
        this.isLoading = true;
        const response = await axios.get('/inventory/items');
        this.items = response.data.data;
      } catch (error) {
        console.error('Failed to load items:', error);
        this.$toast.error('Gagal memuat daftar item');
      } finally {
        this.isLoading = false;
      }
    },
    async loadVendors() {
      try {
        const response = await axios.get('/procurement/vendors');
        this.vendors = response.data.data || [];
      } catch (error) {
        console.error('Failed to load vendors:', error);
      }
    },
    async loadCustomers() {
      try {
        const response = await axios.get('/sales/customers');
        this.customers = response.data.data || [];
      } catch (error) {
        console.error('Failed to load customers:', error);
      }
    },
    async loadItemPrices() {
      if (!this.selectedItemId) {
        this.prices = [];
        this.filteredPrices = [];
        return;
      }

      try {
        this.isLoading = true;
        const params = {};
        if (this.priceTypeFilter) params.price_type = this.priceTypeFilter;
        if (this.activeFilter !== '') params.is_active = this.activeFilter;
        if (this.currentOnlyFilter) params.current_only = 1;

        const response = await axios.get(`/inventory/items/${this.selectedItemId}/prices`, { params });
        this.prices = response.data.data;
        this.filterPrices();
      } catch (error) {
        console.error('Failed to load item prices:', error);
        this.$toast.error('Gagal memuat daftar harga');
      } finally {
        this.isLoading = false;
      }
    },
    filterPrices() {
      let filtered = [...this.prices];

      if (this.priceTypeFilter) {
        filtered = filtered.filter(price => price.price_type === this.priceTypeFilter);
      }

      if (this.activeFilter !== '') {
        const activeValue = this.activeFilter === '1';
        filtered = filtered.filter(price => price.is_active === activeValue);
      }

      if (this.currencyFilter) {
        filtered = filtered.filter(price => price.currency_code === this.currencyFilter);
      }

      if (this.currentOnlyFilter) {
        const today = new Date();
        filtered = filtered.filter(price => {
          const startDate = price.start_date ? new Date(price.start_date) : null;
          const endDate = price.end_date ? new Date(price.end_date) : null;
          return (!startDate || startDate <= today) && (!endDate || endDate >= today);
        });
      }

      this.filteredPrices = filtered;
    },
    openAddPriceModal() {
      this.editingMode = false;
      this.form = {
        price_type: 'purchase',
        price: 0,
        currency_code: 'IDR',
        min_quantity: 1,
        vendor_id: '',
        customer_id: '',
        start_date: '',
        end_date: '',
        is_active: true
      };
      this.showPriceModal = true;
    },
    editPrice(price) {
      this.editingMode = true;
      this.form = {
        price_type: price.price_type || 'purchase',
        price: price.price || 0,
        currency_code: price.currency_code || 'IDR',
        min_quantity: price.min_quantity || 1,
        vendor_id: price.vendor ? price.vendor.vendor_id : '',
        customer_id: price.customer ? price.customer.customer_id : '',
        start_date: price.start_date || '',
        end_date: price.end_date || '',
        is_active: price.is_active !== undefined ? price.is_active : true
      };
      this.selectedPrice = price;
      this.showPriceModal = true;
    },
    closePriceModal() {
      this.showPriceModal = false;
      this.selectedPrice = null;
    },
    async submitForm() {
      try {
        this.submitLoading = true;
        const url = this.editingMode 
          ? `/items/${this.selectedItemId}/prices/${this.selectedPrice.price_id}`
          : `/items/${this.selectedItemId}/prices`;
        
        const method = this.editingMode ? 'put' : 'post';
        await axios[method](url, this.form);
        
        this.$toast.success(this.editingMode ? 'Harga berhasil diupdate' : 'Harga berhasil ditambahkan');
        this.closePriceModal();
        this.loadItemPrices();
      } catch (error) {
        console.error('Failed to save price:', error);
        this.$toast.error('Gagal menyimpan harga');
      } finally {
        this.submitLoading = false;
      }
    },
    confirmDelete(price) {
      this.deleteItem = price;
      this.showDeleteModal = true;
    },
    async deletePrice() {
      try {
        await axios.delete(`/inventory/items/${this.selectedItemId}/prices/${this.deleteItem.price_id}`);
        this.$toast.success('Harga berhasil dihapus');
        this.showDeleteModal = false;
        this.deleteItem = null;
        this.loadItemPrices();
      } catch (error) {
        console.error('Failed to delete price:', error);
        this.$toast.error('Gagal menghapus harga');
      }
    },
    formatPrice(value) {
      return new Intl.NumberFormat('id-ID', {
        style: 'decimal',
        minimumFractionDigits: 2
      }).format(value);
    },
    formatDate(value) {
      if (!value) return '-';
      return new Date(value).toLocaleDateString('id-ID');
    },
    getVendorDisplay(item) {
      if (item.vendor && item.vendor.vendor_code && item.vendor.name) {
        return item.vendor.vendor_code + ' - ' + item.vendor.name;
      }
      return '-';
    },
    getCustomerDisplay(item) {
      if (item.customer && item.customer.customer_code && item.customer.name) {
        return item.customer.customer_code + ' - ' + item.customer.name;
      }
      return '-';
    }
  }
};
</script>

<style scoped>
.page-header {
  margin-bottom: 2rem;
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.card {
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.badge {
  padding: 0.5rem;
}

.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.3);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  text-align: center;
}

.empty-icon {
  font-size: 2.5rem;
  color: var(--gray-300);
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.125rem;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: var(--gray-500);
  max-width: 24rem;
}

table {
  width: 100%;
}

th {
  white-space: nowrap;
}
</style>