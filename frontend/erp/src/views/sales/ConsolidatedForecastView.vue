<!-- src/views/sales/ConsolidatedForecastView.vue -->
<template>
  <div class="page-container">
    <div class="page-header">
      <h2>Consolidated Forecast View</h2>
      <p class="text-muted">Six-month forecast overview by customer and item</p>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <div class="filter-controls mb-4">
          <div class="row g-3">
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Start Month</label>
                <input
                  type="month"
                  class="form-control"
                  v-model="filters.startMonth"
                  @change="loadConsolidatedForecast"
                />
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Customer</label>
                <div class="dropdown">
                  <input
                    type="text"
                    class="form-control"
                    v-model="customerSearch"
                    placeholder="All Customers (type to search...)"
                    @focus="showCustomerDropdown = true"
                    @blur="hideCustomerDropdown"
                    @input="onCustomerSearchInput"
                  />
                  <div v-if="showCustomerDropdown" class="dropdown-menu">
                    <div
                      class="dropdown-item"
                      @mousedown="selectCustomer(null)"
                      :class="{ 'active': !filters.customerId }"
                    >
                      <div class="item-info">
                        <strong>All Customers</strong>
                      </div>
                    </div>
                    <div
                      v-for="customer in filteredCustomers"
                      :key="customer.customer_id"
                      @mousedown="selectCustomer(customer)"
                      class="dropdown-item"
                      :class="{ 'active': filters.customerId === customer.customer_id }"
                    >
                      <div class="item-info">
                        <strong>{{ customer.name }}</strong>
                        <span class="item-code" v-if="customer.customer_code">({{ customer.customer_code }})</span>
                      </div>
                      <div class="item-details" v-if="customer.email || customer.phone">
                        <span class="category" v-if="customer.email">{{ customer.email }}</span>
                        <span class="stock" v-if="customer.phone">{{ customer.phone }}</span>
                      </div>
                    </div>
                    <div v-if="filteredCustomers.length === 0 && customerSearch.trim()" class="dropdown-item text-muted">
                      No customers found
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Item</label>
                <div class="dropdown">
                  <input
                    type="text"
                    class="form-control"
                    v-model="itemSearch"
                    placeholder="All Items (type to search...)"
                    @focus="showItemDropdown = true"
                    @blur="hideItemDropdown"
                    @input="onItemSearchInput"
                  />
                  <div v-if="showItemDropdown" class="dropdown-menu">
                    <div
                      class="dropdown-item"
                      @mousedown="selectItem(null)"
                      :class="{ 'active': !filters.itemId }"
                    >
                      <div class="item-info">
                        <strong>All Items</strong>
                      </div>
                    </div>
                    <div
                      v-for="item in filteredItems"
                      :key="item.item_id"
                      @mousedown="selectItem(item)"
                      class="dropdown-item"
                      :class="{ 'active': filters.itemId === item.item_id }"
                    >
                      <div class="item-info">
                        <strong>{{ item.name }}</strong>
                        <span class="item-code">({{ item.item_code }})</span>
                      </div>
                      <div class="item-details">
                        <span class="category">{{ item.category ? item.category.name : 'No Category' }}</span>
                        <span class="stock">Stock: {{ item.current_stock || 0 }}</span>
                      </div>
                    </div>
                    <div v-if="filteredItems.length === 0 && itemSearch.trim()" class="dropdown-item text-muted">
                      No items found
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Issue Date</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="filters.issueDate"
                  @change="loadConsolidatedForecast"
                />
              </div>
            </div>
          </div>
        </div>

        <div v-if="loading" class="text-center py-5">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p class="mt-2">Loading forecast data...</p>
        </div>

        <div v-else-if="forecastData.length === 0" class="text-center py-5">
          <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
          <h4>No forecast data available</h4>
          <p class="text-muted">
            Try adjusting your filters or importing forecasts first
          </p>
        </div>

        <div v-else>
          <div v-for="customer in forecastData" :key="customer.customer_id" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h3 class="mb-0">
                {{ customer.customer_name }}
                <span class="badge bg-primary ms-2">{{ customer.items.length }} items</span>
              </h3>
              <button
                class="btn btn-sm btn-outline-secondary"
                @click="toggleCustomerExpand(customer.customer_id)"
              >
                <i
                  :class="
                    expandedCustomers.includes(customer.customer_id)
                      ? 'fas fa-chevron-up'
                      : 'fas fa-chevron-down'
                  "
                ></i>
                {{ expandedCustomers.includes(customer.customer_id) ? 'Collapse' : 'Expand' }}
              </button>
            </div>

            <div v-show="expandedCustomers.includes(customer.customer_id)">
              <div class="table-responsive">
                <table class="table table-bordered table-hover">
                  <thead class="bg-light">
                    <tr>
                      <th style="min-width: 200px">Item</th>
                      <th
                        v-for="month in periodRange.months"
                        :key="month"
                        style="min-width: 120px"
                        class="text-center"
                      >
                        {{ formatMonthHeader(month) }}
                      </th>
                      <th class="text-center">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in customer.items" :key="item.item_id">
                      <td>
                        <div class="d-flex flex-column">
                          <strong>{{ item.item_code }}</strong>
                          <small class="text-muted">{{ item.item_name }}</small>
                        </div>
                      </td>
                      <td
                        v-for="period in item.periods"
                        :key="period.period"
                        class="text-center"
                        :class="{
                          'bg-light-success': period.actual_quantity != null && period.actual_quantity >= period.forecast_quantity,
                          'bg-light-danger': period.actual_quantity != null && period.actual_quantity < period.forecast_quantity
                        }"
                      >
                        <div class="forecast-cell">
                          <span class="forecast-amount">{{ period.forecast_quantity }}</span>
                          <small v-if="period.actual_quantity != null" class="d-block text-muted">
                            Actual: {{ period.actual_quantity }}
                          </small>
                          <!-- <small v-if="period.source" class="badge" :class="getSourceBadgeClass(period.source)">
                            {{ period.source }}
                          </small> -->
                        </div>
                      </td>
                      <td class="text-center fw-bold">
                        {{ item.total_forecast }}
                      </td>
                    </tr>
                  </tbody>
                </table>
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
  name: 'ConsolidatedForecastView',
  data() {
    return {
      loading: false,
      forecastData: [],
      periodRange: {
        start: '',
        end: '',
        months: []
      },
      customers: [],
      items: [],
      expandedCustomers: [],
      filters: {
        startMonth: this.getCurrentMonth(),
        customerId: '',
        itemId: '',
        issueDate: ''
      },
      // New properties for searchable dropdowns
      itemSearch: '',
      showItemDropdown: false,
      selectedItemName: '',
      customerSearch: '',
      showCustomerDropdown: false,
      selectedCustomerName: ''
    };
  },
  computed: {
    // Filter items based on search input
    filteredItems() {
      if (!this.itemSearch.trim()) {
        return this.items.slice(0, 10); // Show first 10 items when no search
      }
      return this.items.filter(item =>
        item.name.toLowerCase().includes(this.itemSearch.toLowerCase()) ||
        item.item_code.toLowerCase().includes(this.itemSearch.toLowerCase())
      ).slice(0, 10); // Limit to 10 results
    },
    // Filter customers based on search input
    filteredCustomers() {
      if (!this.customerSearch.trim()) {
        return this.customers.slice(0, 10); // Show first 10 customers when no search
      }
      return this.customers.filter(customer =>
        customer.name.toLowerCase().includes(this.customerSearch.toLowerCase()) ||
        (customer.customer_code && customer.customer_code.toLowerCase().includes(this.customerSearch.toLowerCase()))
      ).slice(0, 10); // Limit to 10 results
    }
  },
  created() {
    this.fetchCustomers();
    this.fetchItems();
    this.loadConsolidatedForecast();
  },
  methods: {
    getCurrentMonth() {
      const date = new Date();
      const year = date.getFullYear();
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      return `${year}-${month}`;
    },
    async fetchCustomers() {
      try {
        const response = await axios.get('/sales/customers');
        this.customers = response.data.data || [];
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },
    async fetchItems() {
      try {
        const response = await axios.get('/inventory/items', {
            params: { sellable: true }
          });
        this.items = response.data.data || [];
      } catch (error) {
        console.error('Error fetching items:', error);
      }
    },
    // New methods for searchable item dropdown
    selectItem(item) {
      if (item === null) {
        // Select "All Items"
        this.filters.itemId = '';
        this.itemSearch = '';
        this.selectedItemName = '';
      } else {
        // Select specific item
        this.filters.itemId = item.item_id;
        this.itemSearch = `${item.name} (${item.item_code})`;
        this.selectedItemName = this.itemSearch;
      }
      this.showItemDropdown = false;
      this.loadConsolidatedForecast();
    },
    hideItemDropdown() {
      setTimeout(() => {
        this.showItemDropdown = false;
        // Reset search if no item was selected and we have a selected item
        if (this.selectedItemName && this.itemSearch !== this.selectedItemName) {
          this.itemSearch = this.selectedItemName;
        } else if (!this.filters.itemId && this.itemSearch) {
          // If no item is selected but there's search text, clear it
          this.itemSearch = '';
        }
      }, 200);
    },
    onItemSearchInput() {
      // If user clears the search, reset the filter
      if (!this.itemSearch.trim()) {
        this.filters.itemId = '';
        this.selectedItemName = '';
        this.loadConsolidatedForecast();
      }
    },
    // New methods for searchable customer dropdown
    selectCustomer(customer) {
      if (customer === null) {
        // Select "All Customers"
        this.filters.customerId = '';
        this.customerSearch = '';
        this.selectedCustomerName = '';
      } else {
        // Select specific customer
        this.filters.customerId = customer.customer_id;
        this.customerSearch = customer.name;
        this.selectedCustomerName = this.customerSearch;
      }
      this.showCustomerDropdown = false;
      this.loadConsolidatedForecast();
    },
    hideCustomerDropdown() {
      setTimeout(() => {
        this.showCustomerDropdown = false;
        // Reset search if no customer was selected and we have a selected customer
        if (this.selectedCustomerName && this.customerSearch !== this.selectedCustomerName) {
          this.customerSearch = this.selectedCustomerName;
        } else if (!this.filters.customerId && this.customerSearch) {
          // If no customer is selected but there's search text, clear it
          this.customerSearch = '';
        }
      }, 200);
    },
    onCustomerSearchInput() {
      // If user clears the search, reset the filter
      if (!this.customerSearch.trim()) {
        this.filters.customerId = '';
        this.selectedCustomerName = '';
        this.loadConsolidatedForecast();
      }
    },
    async loadConsolidatedForecast() {
      this.loading = true;

      try {
        // Prepare the query parameters
        const params = {
          start_month: `${this.filters.startMonth}-01`,
        };

        if (this.filters.customerId) {
          params.customer_id = this.filters.customerId;
        }

        if (this.filters.itemId) {
          params.item_id = this.filters.itemId;
        }

        if (this.filters.issueDate) {
          params.issue_date = this.filters.issueDate;
        }

        const response = await axios.get('/sales/forecasts/consolidated', { params });

        if (response.data.data) {
          this.forecastData = response.data.data;
          this.periodRange = response.data.period_range || {
            start: '',
            end: '',
            months: []
          };

          // Auto-expand if only one customer is present
          if (this.forecastData.length === 1) {
            this.expandedCustomers = [this.forecastData[0].customer_id];
          } else if (this.forecastData.length > 0 && this.expandedCustomers.length === 0) {
            // Otherwise, expand the first customer
            this.expandedCustomers = [this.forecastData[0].customer_id];
          }
        } else {
          this.forecastData = [];
          this.periodRange = { start: '', end: '', months: [] };
        }
      } catch (error) {
        console.error('Error loading consolidated forecast:', error);
      } finally {
        this.loading = false;
      }
    },
    toggleCustomerExpand(customerId) {
      const index = this.expandedCustomers.indexOf(customerId);
      if (index === -1) {
        this.expandedCustomers.push(customerId);
      } else {
        this.expandedCustomers.splice(index, 1);
      }
    },
    formatMonthHeader(month) {
      const [year, monthNum] = month.split('-');
      const date = new Date(year, parseInt(monthNum) - 1, 1);
      return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    },
    getSourceBadgeClass(source) {
      if (source === 'Customer') {
        return 'bg-primary';
      } else if (source.startsWith('System-Trend')) {
        return 'bg-info';
      } else if (source.startsWith('System-Weighted')) {
        return 'bg-success';
      } else if (source.startsWith('System-Average')) {
        return 'bg-warning';
      } else if (source.startsWith('System-Manual')) {
        return 'bg-secondary';
      } else {
        return 'bg-light text-dark';
      }
    }
  }
};
</script>

<style scoped>
/* Perubahan tambahan untuk mendukung layout yang konsisten */
.card-body {
  padding: 1.5rem;
}

/* Memastikan layout yang benar ketika g-3 digunakan */
.row.g-3 {
  margin-right: -10px;
  margin-left: -10px;
  row-gap: normal !important;
}/* Tambahkan reset untuk Bootstrap g-3 default */
.row.g-3 {
  --bs-gutter-x: 2rem !important;
  --bs-gutter-y: 1.5rem !important;
}/* Styling yang disempurnakan untuk ConsolidatedForecastView.vue */

.page-container {
  padding: 2rem;
  max-width: 1600px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2.5rem;
  border-bottom: 1px solid #eaeaea;
  padding-bottom: 1.5rem;
}

.page-header h2 {
  font-size: 1.8rem;
  margin-bottom: 0.5rem;
  color: #333;
}

.page-header .text-muted {
  font-size: 1rem;
}

/* Card styling */
.card {
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: none;
  margin-bottom: 2rem;
}

.card-body {
  padding: 1.75rem;
}

/* Form controls section */
/* Filter controls dengan layout yang konsisten */
.filter-controls {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 1.75rem;
  margin-bottom: 2rem;
  border: 1px solid #eef0f2;
}

/* Reset row untuk memastikan layout baris yang baik */
.filter-controls .row {
  display: flex;
  flex-wrap: wrap;
  margin-right: -10px;
  margin-left: -10px;
}

/* Styling untuk column */
.filter-controls .col-md-3 {
  padding-left: 10px;
  padding-right: 10px;
  margin-bottom: 0;
  width: 25%;
  flex: 0 0 25%;
  max-width: 25%;
}

.filter-controls .form-group {
  margin-bottom: 0;
}

.filter-controls .form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #495057;
  font-size: 0.9rem;
}

.filter-controls .form-control {
  border-radius: 5px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #ced4da;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  width: 100%;
  height: calc(1.5em + 0.75rem + 2px);
  font-size: 0.9rem;
}

.filter-controls .form-control:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Customer section headers */
.customer-header {
  padding: 1rem 0;
  margin-bottom: 1rem;
  border-bottom: 1px solid #eaeaea;
}

/* Loading and empty state */
.loading-state,
.empty-state {
  padding: 3rem 0;
  text-align: center;
}

.loading-state i,
.empty-state i {
  color: #6c757d;
  margin-bottom: 1rem;
}

.empty-state h4 {
  color: #495057;
  margin-bottom: 0.5rem;
}

/* Table styling */
.table-responsive {
  margin: 1rem 0 2rem 0;
  border-radius: 6px;
  overflow: hidden;
}

.table {
  margin-bottom: 0;
}

.table thead th {
  background-color: #f8f9fa;
  color: #495057;
  font-weight: 600;
  padding: 0.85rem;
  border-bottom-width: 1px;
}

.table tbody td {
  padding: 1rem 0.85rem;
  vertical-align: middle;
}

.table tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

/* Forecast cell styling */
.forecast-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  align-items: center;
}

.forecast-amount {
  font-weight: 600;
  font-size: 1.05rem;
}

/* Status background colors */
.bg-light-success {
  background-color: rgba(40, 167, 69, 0.08);
}

.bg-light-danger {
  background-color: rgba(220, 53, 69, 0.08);
}

/* Status badges */
.badge {
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 500;
}

/* Customer expand/collapse button */
.btn-outline-secondary {
  border-color: #e2e6ea;
  color: #6c757d;
  padding: 0.375rem 0.75rem;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.btn-outline-secondary:hover {
  background-color: #f8f9fa;
  border-color: #cbd3da;
}

/* Add space between items in expand/collapse button */
.btn-outline-secondary i {
  margin-right: 0.25rem;
}

/* Dropdown Styles for Item and Customer Search */
.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ced4da;
  border-top: none;
  border-radius: 0 0 5px 5px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-height: 250px;
  overflow-y: auto;
  z-index: 1000;
}

.dropdown-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid #f8f9fa;
  transition: background-color 0.2s;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}

.dropdown-item.active {
  background-color: #e3f2fd;
  color: #1976d2;
}

.dropdown-item.text-muted {
  color: #6c757d !important;
  cursor: default;
}

.dropdown-item.text-muted:hover {
  background-color: transparent;
}

.item-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.item-info strong {
  font-weight: 600;
  color: #495057;
}

.item-code {
  color: #6c757d;
  font-size: 0.8rem;
}

.item-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
}

.category {
  color: #007bff;
  font-weight: 500;
}

.stock {
  color: #6c757d;
}

/* Responsive adjustments */
@media (max-width: 991px) {
  .filter-controls {
    padding: 1.5rem;
  }

  .filter-controls .col-md-3 {
    margin-bottom: 1.5rem;
  }

  .card-body {
    padding: 1.25rem;
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 1rem;
  }

  .filter-controls {
    padding: 1.25rem;
  }

  .filter-controls .form-group {
    margin-bottom: 1.25rem;
  }

  .table thead th,
  .table tbody td {
    padding: 0.6rem;
  }
}
</style>
