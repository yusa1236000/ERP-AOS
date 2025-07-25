<template>
  <div class="forecast-form-container">
    <div class="page-header">
      <h1>{{ isEdit ? 'Edit' : 'Create' }} Sales Forecast</h1>
      <div class="page-actions">
        <button
          type="button"
          class="btn btn-secondary"
          @click="$router.go(-1)"
        >
          <i class="fas fa-arrow-left"></i>
          Back
        </button>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>{{ isEdit ? 'Edit' : 'New' }} Forecast Information</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitForm">
          <div class="form-grid">
            <!-- Customer Selection with Search -->
            <div class="form-group">
              <label for="customer_id" class="required">Customer</label>
              <div class="dropdown">
                <input
                  type="text"
                  id="customer_id"
                  v-model="customerSearch"
                  class="form-control"
                  :class="{ 'is-invalid': errors.customer_id }"
                  placeholder="Search for a customer..."
                  @focus="showCustomerDropdown = true"
                  @blur="hideCustomerDropdown"
                  :disabled="isEdit"
                  autocomplete="off"
                />
                <div v-if="showCustomerDropdown" class="dropdown-menu">
                  <div
                    v-for="customer in filteredCustomers"
                    :key="customer.customer_id"
                    @mousedown="selectCustomer(customer)"
                    class="dropdown-item"
                  >
                    <div class="item-info">
                      <strong>{{ customer.name }}</strong>
                      <span class="item-code">({{ customer.customer_code }})</span>
                    </div>
                    <div class="item-details">
                      <span class="category">{{ customer.email || 'No email' }}</span>
                      <span class="stock">{{ customer.phone || 'No phone' }}</span>
                    </div>
                  </div>
                  <div v-if="filteredCustomers.length === 0" class="dropdown-item text-muted">
                    No customers found
                  </div>
                </div>
              </div>
              <div v-if="errors.customer_id" class="invalid-feedback">
                {{ errors.customer_id[0] }}
              </div>
            </div>

            <!-- Item Selection with Search -->
            <div class="form-group">
              <label for="item_id" class="required">Item</label>
              <div class="dropdown">
                <input
                  type="text"
                  id="item_id"
                  v-model="itemSearch"
                  class="form-control"
                  :class="{ 'is-invalid': errors.item_id }"
                  placeholder="Search for an item..."
                  @focus="showItemDropdown = true"
                  @blur="hideItemDropdown"
                  :disabled="isEdit"
                  autocomplete="off"
                />
                <div v-if="showItemDropdown" class="dropdown-menu">
                  <div
                    v-for="item in filteredItems"
                    :key="item.item_id"
                    @mousedown="selectItem(item)"
                    class="dropdown-item"
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
                  <div v-if="filteredItems.length === 0" class="dropdown-item text-muted">
                    No items found
                  </div>
                </div>
              </div>
              <div v-if="errors.item_id" class="invalid-feedback">
                {{ errors.item_id[0] }}
              </div>
            </div>

            <!-- Forecast Period -->
            <div class="form-group">
              <label for="forecast_period" class="required">Forecast Period</label>
              <input
                id="forecast_period"
                type="month"
                v-model="form.forecast_period"
                class="form-control"
                :class="{ 'is-invalid': errors.forecast_period }"
                required
                :disabled="isEdit"
              >
              <div v-if="errors.forecast_period" class="invalid-feedback">
                {{ errors.forecast_period[0] }}
              </div>
            </div>

            <!-- Forecast Quantity -->
            <div class="form-group">
              <label for="forecast_quantity" class="required">Forecast Quantity</label>
              <input
                id="forecast_quantity"
                type="number"
                step="0.01"
                min="0"
                v-model="form.forecast_quantity"
                class="form-control"
                :class="{ 'is-invalid': errors.forecast_quantity }"
                required
              >
              <div v-if="errors.forecast_quantity" class="invalid-feedback">
                {{ errors.forecast_quantity[0] }}
              </div>
            </div>

            <!-- Actual Quantity (for edit mode) -->
            <div class="form-group" v-if="isEdit">
              <label for="actual_quantity">Actual Quantity</label>
              <input
                id="actual_quantity"
                type="number"
                step="0.01"
                min="0"
                v-model="form.actual_quantity"
                class="form-control"
                :class="{ 'is-invalid': errors.actual_quantity }"
              >
              <div v-if="errors.actual_quantity" class="invalid-feedback">
                {{ errors.actual_quantity[0] }}
              </div>
            </div>

            <!-- Forecast Source -->
            <div class="form-group">
              <label for="forecast_source">Forecast Source</label>
              <select
                id="forecast_source"
                v-model="form.forecast_source"
                class="form-control"
                :class="{ 'is-invalid': errors.forecast_source }"
              >
                <option value="Customer">Customer</option>
                <option value="System-Manual">System Manual</option>
                <option value="System-Average">System Average</option>
                <option value="System-Weighted">System Weighted</option>
                <option value="System-Trend">System Trend</option>
              </select>
              <div v-if="errors.forecast_source" class="invalid-feedback">
                {{ errors.forecast_source[0] }}
              </div>
            </div>

            <!-- Confidence Level -->
            <div class="form-group">
              <label for="confidence_level">Confidence Level</label>
              <input
                id="confidence_level"
                type="number"
                step="0.01"
                min="0"
                max="1"
                v-model="form.confidence_level"
                class="form-control"
                :class="{ 'is-invalid': errors.confidence_level }"
                placeholder="0.70"
              >
              <small class="form-text text-muted">Value between 0 and 1 (e.g., 0.75 for 75% confidence)</small>
              <div v-if="errors.confidence_level" class="invalid-feedback">
                {{ errors.confidence_level[0] }}
              </div>
            </div>

            <!-- Forecast Issue Date -->
            <div class="form-group">
              <label for="forecast_issue_date">Forecast Issue Date</label>
              <input
                id="forecast_issue_date"
                type="date"
                v-model="form.forecast_issue_date"
                class="form-control"
                :class="{ 'is-invalid': errors.forecast_issue_date }"
              >
              <div v-if="errors.forecast_issue_date" class="invalid-feedback">
                {{ errors.forecast_issue_date[0] }}
              </div>
            </div>
          </div>

          <!-- Calculated Fields Display -->
          <div v-if="form.forecast_quantity && form.actual_quantity" class="calculated-fields">
            <h4>Calculated Fields</h4>
            <div class="form-grid">
              <div class="form-group">
                <label>Variance</label>
                <input
                  type="number"
                  :value="calculateVariance()"
                  class="form-control"
                  readonly
                >
              </div>
              <div class="form-group">
                <label>Variance %</label>
                <input
                  type="text"
                  :value="calculateVariancePercentage()"
                  class="form-control"
                  readonly
                >
              </div>
            </div>
          </div>

          <!-- Submit Buttons -->
          <div class="form-actions">
            <button
              type="button"
              class="btn btn-secondary"
              @click="$router.go(-1)"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isLoading"
            >
              <i v-if="isLoading" class="fas fa-spinner fa-spin"></i>
              {{ isEdit ? 'Update' : 'Create' }} Forecast
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SalesForecastForm',
  props: {
    id: {
      type: [String, Number],
      default: null
    }
  },
  data() {
    return {
      isLoading: false,
      customers: [],
      items: [],
      errors: {},
      form: {
        customer_id: '',
        item_id: '',
        forecast_period: '',
        forecast_quantity: '',
        actual_quantity: '',
        forecast_source: 'System-Manual',
        confidence_level: 0.7,
        forecast_issue_date: ''
      },
      // Customer search states
      customerSearch: '',
      showCustomerDropdown: false,
      // Item search states
      itemSearch: '',
      showItemDropdown: false
    };
  },
  computed: {
    isEdit() {
      return !!this.id;
    },
    // Filter customers based on search input
    filteredCustomers() {
      if (!this.customerSearch) {
        return this.customers.slice(0, 10);
      }
      return this.customers.filter(customer =>
        customer.name.toLowerCase().includes(this.customerSearch.toLowerCase()) ||
        customer.customer_code.toLowerCase().includes(this.customerSearch.toLowerCase()) ||
        (customer.email && customer.email.toLowerCase().includes(this.customerSearch.toLowerCase()))
      ).slice(0, 10);
    },
    // Filter items based on search input
    filteredItems() {
      if (!this.itemSearch) {
        return this.items.slice(0, 10);
      }
      return this.items.filter(item =>
        item.name.toLowerCase().includes(this.itemSearch.toLowerCase()) ||
        item.item_code.toLowerCase().includes(this.itemSearch.toLowerCase())
      ).slice(0, 10);
    }
  },
  async mounted() {
    await this.loadDropdownData();

    if (this.isEdit) {
      await this.loadForecast();
    } else {
      // Set default forecast issue date to today
      this.form.forecast_issue_date = new Date().toISOString().split('T')[0];
    }
  },
  methods: {
    async loadDropdownData() {
      try {
        // Load customers
        const customersResponse = await axios.get('/sales/customers');
        this.customers = customersResponse.data.data || customersResponse.data;

        // Load items (sellable items only)
        const itemsResponse = await axios.get('/inventory/items', {
          params: { is_sellable: true }
        });
        this.items = itemsResponse.data.data || itemsResponse.data;
      } catch (error) {
        console.error('Error loading dropdown data:', error);
        this.$toast?.error('Failed to load form data');
      }
    },

    async loadForecast() {
      try {
        this.isLoading = true;
        const response = await axios.get(`/sales/forecasts/${this.id}`);
        const forecast = response.data.data;

        this.form = {
          customer_id: forecast.customer_id,
          item_id: forecast.item_id,
          forecast_period: this.formatDateForInput(forecast.forecast_period),
          forecast_quantity: forecast.forecast_quantity,
          actual_quantity: forecast.actual_quantity || '',
          forecast_source: forecast.forecast_source || 'System-Manual',
          confidence_level: forecast.confidence_level || 0.7,
          forecast_issue_date: forecast.forecast_issue_date ?
            this.formatDateForInput(forecast.forecast_issue_date) : ''
        };

        // Set search texts for display
        if (forecast.customer) {
          this.customerSearch = `${forecast.customer.name} (${forecast.customer.customer_code})`;
        }
        if (forecast.item) {
          this.itemSearch = `${forecast.item.name} (${forecast.item.item_code})`;
        }
      } catch (error) {
        console.error('Error loading forecast:', error);
        this.$toast?.error('Failed to load forecast data');
        this.$router.go(-1);
      } finally {
        this.isLoading = false;
      }
    },

    // Customer dropdown methods
    selectCustomer(customer) {
      this.form.customer_id = customer.customer_id;
      this.customerSearch = `${customer.name} (${customer.customer_code})`;
      this.showCustomerDropdown = false;
    },

    hideCustomerDropdown() {
      setTimeout(() => {
        this.showCustomerDropdown = false;
      }, 200);
    },

    // Item dropdown methods
    selectItem(item) {
      this.form.item_id = item.item_id;
      this.itemSearch = `${item.name} (${item.item_code})`;
      this.showItemDropdown = false;
    },

    hideItemDropdown() {
      setTimeout(() => {
        this.showItemDropdown = false;
      }, 200);
    },

    async submitForm() {
      try {
        this.isLoading = true;
        this.errors = {};

        // Prepare form data
        const formData = { ...this.form };

        // Convert forecast_period to proper format (YYYY-MM-01)
        if (formData.forecast_period) {
          formData.forecast_period = formData.forecast_period + '-01';
        }

        if (this.isEdit) {
          await axios.put(`/sales/forecasts/${this.id}`, formData);
        } else {
          await axios.post('/sales/forecasts', formData);
        }

        this.$toast?.success(`Forecast ${this.isEdit ? 'updated' : 'created'} successfully`);
        this.$router.push('/sales/forecasts');
      } catch (error) {
        console.error('Error saving forecast:', error);

        if (error.response?.data?.errors) {
          this.errors = error.response.data.errors;
        } else {
          this.$toast?.error(`Failed to ${this.isEdit ? 'update' : 'create'} forecast`);
        }
      } finally {
        this.isLoading = false;
      }
    },

    calculateVariance() {
      if (this.form.actual_quantity && this.form.forecast_quantity) {
        return (parseFloat(this.form.actual_quantity) - parseFloat(this.form.forecast_quantity)).toFixed(2);
      }
      return 0;
    },

    calculateVariancePercentage() {
      if (this.form.actual_quantity && this.form.forecast_quantity) {
        const variance = parseFloat(this.form.actual_quantity) - parseFloat(this.form.forecast_quantity);
        const percentage = (variance / parseFloat(this.form.forecast_quantity)) * 100;
        return percentage.toFixed(2) + '%';
      }
      return '0%';
    },

    formatDateForInput(date) {
      if (!date) return '';
      const d = new Date(date);
      if (isNaN(d.getTime())) return '';

      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');

      // For month input, we need YYYY-MM format
      if (this.isMonthInput) {
        return `${year}-${month}`;
      }

      // For date input, we need YYYY-MM-DD format
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }
  }
};
</script>

<style scoped>
.forecast-form-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  margin: 0;
  color: var(--gray-800);
}

.page-actions {
  display: flex;
  gap: 1rem;
}

.card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  background: var(--gray-50);
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--gray-200);
}

.card-header h3 {
  margin: 0;
  color: var(--gray-800);
}

.card-body {
  padding: 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: var(--gray-700);
}

.form-group label.required::after {
  content: ' *';
  color: var(--danger-color);
}

.form-control {
  padding: 0.75rem;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  background-color: white;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control:disabled {
  background-color: var(--gray-100);
  color: var(--gray-500);
  cursor: not-allowed;
}

.form-control.is-invalid {
  border-color: var(--danger-color);
}

.invalid-feedback {
  color: var(--danger-color);
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.form-text {
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.text-muted {
  color: var(--gray-500);
}

.calculated-fields {
  background: var(--gray-50);
  padding: 1.5rem;
  border-radius: 0.375rem;
  margin-bottom: 2rem;
}

.calculated-fields h4 {
  margin: 0 0 1rem 0;
  color: var(--gray-700);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid var(--gray-200);
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: 1px solid transparent;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--primary-dark);
  border-color: var(--primary-dark);
}

.btn-secondary {
  background-color: white;
  color: var(--gray-700);
  border-color: var(--gray-300);
}

.btn-secondary:hover {
  background-color: var(--gray-50);
}

/* Dropdown Styles */
.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid var(--gray-300);
  border-top: none;
  border-radius: 0 0 0.375rem 0.375rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-height: 250px;
  overflow-y: auto;
  z-index: 100;
}

.dropdown-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid var(--gray-100);
  transition: background-color 0.2s;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background-color: var(--gray-50);
}

.dropdown-item.text-muted {
  color: var(--gray-500);
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
  color: var(--gray-800);
}

.item-code {
  color: var(--gray-500);
  font-size: 0.8rem;
}

.item-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
}

.category {
  color: var(--primary-color);
  font-weight: 500;
}

.stock {
  color: var(--gray-500);
}

@media (max-width: 768px) {
  .forecast-form-container {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .page-actions {
    justify-content: flex-end;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .card-body {
    padding: 1.5rem;
  }
}
</style>
