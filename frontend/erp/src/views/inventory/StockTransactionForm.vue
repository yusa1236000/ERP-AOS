<!-- src/views/inventory/StockTransactionForm.vue -->
<template>
    <div class="stock-transaction-form">
      <div class="page-header">
        <h1>Create Stock Transaction</h1>
        <div class="page-actions">
          <router-link to="/stock-transactions" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Transactions
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Transaction Details</h3>
        </div>
        <div class="card-body">
          <form @submit.prevent="submitTransaction">
            <!-- Transaction Type Selection -->
            <div class="form-section">
              <h4 class="section-title">Transaction Type</h4>
              <div class="transaction-types">
                <div
                  v-for="type in transactionTypes"
                  :key="type.value"
                  class="transaction-type-option"
                  :class="{ active: form.transaction_type === type.value }"
                  @click="selectTransactionType(type.value)"
                >
                  <div class="transaction-type-icon" :class="type.iconClass">
                    <i :class="type.icon"></i>
                  </div>
                  <div class="transaction-type-details">
                    <div class="transaction-type-label">{{ type.label }}</div>
                    <div class="transaction-type-description">{{ type.description }}</div>
                  </div>
                </div>
              </div>
              <div v-if="errors.transaction_type" class="error-message">
                {{ errors.transaction_type }}
              </div>
            </div>

            <!-- Item Selection -->
            <div class="form-section">
              <h4 class="section-title">Item Details</h4>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="item">Item*</label>
                  <div class="select-with-status">
                    <select
                      id="item"
                      v-model="form.item_id"
                      class="form-control"
                      :class="{ 'is-invalid': errors.item_id }"
                      required
                    >
                      <option value="">Select an item</option>
                      <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                        {{ item.item_code }} - {{ item.name }}
                      </option>
                    </select>
                    <div v-if="itemsLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.item_id" class="error-message">
                    {{ errors.item_id }}
                  </div>
                </div>

                <div class="form-group col-md-6" v-if="selectedItem">
                  <label>Current Stock</label>
                  <div class="info-display">
                    <span :class="getStockStatusClass(selectedItem)">
                      {{ selectedItem.current_stock }}
                      <span class="ml-1">
                        {{ selectedItem.unitOfMeasure ? selectedItem.unitOfMeasure.symbol : '' }}
                      </span>
                    </span>
                  </div>
                </div>
              </div>

              <div class="form-row" v-if="selectedItem && form.transaction_type === 'transfer'">
                <div class="col-md-12">
                  <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    For stock transfers, please use the dedicated
                    <router-link to="/stock-transactions/transfer" class="alert-link">Stock Transfer</router-link>
                    form for better control of source and destination details.
                  </div>
                </div>
              </div>

              <!-- Batch Selection (if applicable) -->
              <div class="form-row" v-if="selectedItem && hasBatches">
                <div class="form-group col-md-6">
                  <label for="batch">Batch</label>
                  <div class="select-with-status">
                    <select
                      id="batch"
                      v-model="form.batch_id"
                      class="form-control"
                      :class="{ 'is-invalid': errors.batch_id }"
                    >
                      <option value="">Select a batch</option>
                      <option v-for="batch in batches" :key="batch.batch_id" :value="batch.batch_id">
                        {{ batch.batch_number }}
                        <template v-if="batch.expiry_date">
                          (Expires: {{ formatDate(batch.expiry_date) }})
                        </template>
                      </option>
                    </select>
                    <div v-if="batchesLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.batch_id" class="error-message">
                    {{ errors.batch_id }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Location Details -->
            <div class="form-section">
              <h4 class="section-title">Location Details</h4>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="warehouse">Warehouse*</label>
                  <div class="select-with-status">
                    <select
                      id="warehouse"
                      v-model="form.warehouse_id"
                      class="form-control"
                      :class="{ 'is-invalid': errors.warehouse_id }"
                      required
                    >
                      <option value="">Select a warehouse</option>
                      <option v-for="warehouse in warehouses" :key="warehouse.warehouse_id" :value="warehouse.warehouse_id">
                        {{ warehouse.name }}
                      </option>
                    </select>
                    <div v-if="warehousesLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.warehouse_id" class="error-message">
                    {{ errors.warehouse_id }}
                  </div>
                </div>

                <div class="form-group col-md-6">
                  <label for="location">Location</label>
                  <div class="select-with-status">
                    <select
                      id="location"
                      v-model="form.location_id"
                      class="form-control"
                      :class="{ 'is-invalid': errors.location_id }"
                      :disabled="!form.warehouse_id || locationsLoading"
                    >
                      <option value="">Select a location</option>
                      <option v-for="location in locations" :key="location.location_id" :value="location.location_id">
                        {{ location.code }}
                      </option>
                    </select>
                    <div v-if="locationsLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.location_id" class="error-message">
                    {{ errors.location_id }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Transaction Details -->
            <div class="form-section">
              <h4 class="section-title">Transaction Details</h4>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="quantity">Quantity*</label>
                  <div class="input-group">
                    <input
                      type="number"
                      id="quantity"
                      v-model.number="form.quantity"
                      class="form-control"
                      :class="{ 'is-invalid': errors.quantity }"
                      :step="quantityStep"
                      required
                    />
                    <div class="input-group-append" v-if="selectedItem && selectedItem.unitOfMeasure">
                      <span class="input-group-text">{{ selectedItem.unitOfMeasure.symbol }}</span>
                    </div>
                  </div>
                  <small class="form-text text-muted">
                    {{ getQuantityHelperText() }}
                  </small>
                  <div v-if="errors.quantity" class="error-message">
                    {{ errors.quantity }}
                  </div>
                </div>

                <div class="form-group col-md-6">
                  <label for="transaction-date">Transaction Date*</label>
                  <input
                    type="date"
                    id="transaction-date"
                    v-model="form.transaction_date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.transaction_date }"
                    required
                  />
                  <div v-if="errors.transaction_date" class="error-message">
                    {{ errors.transaction_date }}
                  </div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="reference-document">Reference Document</label>
                  <input
                    type="text"
                    id="reference-document"
                    v-model="form.reference_document"
                    class="form-control"
                    :class="{ 'is-invalid': errors.reference_document }"
                    placeholder="E.g., Purchase Order, Invoice, etc."
                  />
                  <div v-if="errors.reference_document" class="error-message">
                    {{ errors.reference_document }}
                  </div>
                </div>

                <div class="form-group col-md-6">
                  <label for="reference-number">Reference Number</label>
                  <input
                    type="text"
                    id="reference-number"
                    v-model="form.reference_number"
                    class="form-control"
                    :class="{ 'is-invalid': errors.reference_number }"
                    placeholder="E.g., PO-12345, INV-001, etc."
                  />
                  <div v-if="errors.reference_number" class="error-message">
                    {{ errors.reference_number }}
                  </div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-12">
                  <label for="notes">Notes</label>
                  <textarea
                    id="notes"
                    v-model="form.notes"
                    class="form-control"
                    :class="{ 'is-invalid': errors.notes }"
                    rows="3"
                    placeholder="Additional notes or details about this transaction"
                  ></textarea>
                  <div v-if="errors.notes" class="error-message">
                    {{ errors.notes }}
                  </div>
                </div>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="cancel">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                Create Transaction
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, watch, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'StockTransactionForm',
    setup() {
      const router = useRouter();

      // Form data
      const form = reactive({
        transaction_type: '',
        item_id: '',
        warehouse_id: '',
        location_id: '',
        quantity: null,
        transaction_date: new Date().toISOString().split('T')[0], // Default to today
        reference_document: '',
        reference_number: '',
        batch_id: '',
        notes: ''
      });

      // UI state
      const errors = reactive({});
      const isSubmitting = ref(false);

      // Data for selects
      const items = ref([]);
      const warehouses = ref([]);
      const locations = ref([]);
      const batches = ref([]);

      // Loading states
      const itemsLoading = ref(false);
      const warehousesLoading = ref(false);
      const locationsLoading = ref(false);
      const batchesLoading = ref(false);

      // Transaction types
      const transactionTypes = [
        {
          value: 'receive',
          label: 'Receive',
          icon: 'fas fa-arrow-down',
          iconClass: 'bg-success',
          description: 'Record items entering your inventory'
        },
        {
          value: 'issue',
          label: 'Issue',
          icon: 'fas fa-arrow-up',
          iconClass: 'bg-danger',
          description: 'Record items leaving your inventory'
        },
        {
          value: 'transfer',
          label: 'Transfer',
          icon: 'fas fa-exchange-alt',
          iconClass: 'bg-warning',
          description: 'Move items between warehouses or locations'
        },
        {
          value: 'adjustment',
          label: 'Adjustment',
          icon: 'fas fa-balance-scale',
          iconClass: 'bg-info',
          description: 'Adjust inventory quantities (increase or decrease)'
        },
        {
          value: 'return',
          label: 'Return',
          icon: 'fas fa-undo',
          iconClass: 'bg-secondary',
          description: 'Record returned items back to inventory'
        }
      ];

      // Computed properties
      const selectedItem = computed(() => {
        if (!form.item_id) return null;
        return items.value.find(item => item.item_id === form.item_id);
      });

      const hasBatches = computed(() => {
        return selectedItem.value && batches.value.length > 0;
      });

      const quantityStep = computed(() => {
        // Can be customized based on UOM of selected item
        return 0.01;
      });

      // Methods
      const fetchItems = async () => {
        itemsLoading.value = true;
        try {
          const response = await axios.get('/inventory/items');
          items.value = response.data.data;
        } catch (error) {
          console.error('Error fetching items:', error);
        } finally {
          itemsLoading.value = false;
        }
      };

      const fetchWarehouses = async () => {
        warehousesLoading.value = true;
        try {
          const response = await axios.get('/inventory/warehouses');
          warehouses.value = response.data.data;
        } catch (error) {
          console.error('Error fetching warehouses:', error);
        } finally {
          warehousesLoading.value = false;
        }
      };

      const fetchLocations = async (warehouseId) => {
        if (!warehouseId) {
          locations.value = [];
          return;
        }

        locationsLoading.value = true;
        try {
          // This endpoint might need adjustment based on your API structure
          const response = await axios.get(`/inventory/warehouses/${warehouseId}/locations`);
          locations.value = response.data.data;
        } catch (error) {
          console.error('Error fetching locations:', error);
          locations.value = [];
        } finally {
          locationsLoading.value = false;
        }
      };

      const fetchBatches = async (itemId) => {
        if (!itemId) {
          batches.value = [];
          return;
        }

        batchesLoading.value = true;
        try {
          const response = await axios.get(`/inventory/items/${itemId}/batches`);
          batches.value = response.data.data;
        } catch (error) {
          console.error('Error fetching batches:', error);
          batches.value = [];
        } finally {
          batchesLoading.value = false;
        }
      };

      const selectTransactionType = (type) => {
        form.transaction_type = type;

        // Adjust quantity based on transaction type
        if (!form.quantity && (type === 'receive' || type === 'return' || type === 'adjustment')) {
          form.quantity = 1;
        } else if (!form.quantity && (type === 'issue')) {
          form.quantity = -1;
        }
      };

      const getQuantityHelperText = () => {
        switch (form.transaction_type) {
          case 'receive':
          case 'return':
            return 'Enter a positive number to add to inventory.';
          case 'issue':
            return 'Enter a positive number (will be recorded as negative).';
          case 'adjustment':
            return 'Enter a positive number to increase stock or negative to decrease.';
          case 'transfer':
            return 'Enter a positive number to transfer from one location to another.';
          default:
            return '';
        }
      };

      const getStockStatusClass = (item) => {
        if (!item) return '';

        if (item.current_stock <= 0) {
          return 'text-danger';
        } else if (item.current_stock <= item.minimum_stock) {
          return 'text-warning';
        } else {
          return 'text-success';
        }
      };

      const validateForm = () => {
        errors.transaction_type = '';
        errors.item_id = '';
        errors.warehouse_id = '';
        errors.location_id = '';
        errors.quantity = '';
        errors.transaction_date = '';

        let isValid = true;

        if (!form.transaction_type) {
          errors.transaction_type = 'Please select a transaction type';
          isValid = false;
        }

        if (!form.item_id) {
          errors.item_id = 'Please select an item';
          isValid = false;
        }

        if (!form.warehouse_id) {
          errors.warehouse_id = 'Please select a warehouse';
          isValid = false;
        }

        if (form.quantity === null || form.quantity === undefined) {
          errors.quantity = 'Please enter a quantity';
          isValid = false;
        }

        if (form.transaction_type === 'issue' && form.quantity > 0) {
          // Convert to negative for issue transactions
          form.quantity = -Math.abs(form.quantity);
        }

        if (!form.transaction_date) {
          errors.transaction_date = 'Please select a transaction date';
          isValid = false;
        }

        return isValid;
      };

      const submitTransaction = async () => {
        if (!validateForm()) {
          // Scroll to the first error
          const firstError = document.querySelector('.error-message');
          if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          return;
        }

        isSubmitting.value = true;

        try {
          await axios.post('/inventory/stock-transactions', form);

          // Success notification
          alert('Transaction created successfully');

          // Navigate to transaction list or the new transaction details
          router.push('/stock-transactions');
        } catch (error) {
          console.error('Error creating transaction:', error);

          // Handle validation errors from the server
          if (error.response && error.response.data && error.response.data.errors) {
            Object.assign(errors, error.response.data.errors);
          } else {
            alert('Failed to create transaction. Please try again.');
          }
        } finally {
          isSubmitting.value = false;
        }
      };

      const cancel = () => {
        router.push('/stock-transactions');
      };

      const formatDate = (dateString) => {
        if (!dateString) return '';

        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      // Watchers
      watch(() => form.warehouse_id, (newWarehouseId) => {
        form.location_id = '';
        if (newWarehouseId) {
          fetchLocations(newWarehouseId);
        }
      });

      watch(() => form.item_id, (newItemId) => {
        form.batch_id = '';
        if (newItemId) {
          fetchBatches(newItemId);
        }
      });

      // Lifecycle hooks
      onMounted(() => {
        fetchItems();
        fetchWarehouses();
      });

      return {
        form,
        errors,
        isSubmitting,
        items,
        warehouses,
        locations,
        batches,
        itemsLoading,
        warehousesLoading,
        locationsLoading,
        batchesLoading,
        transactionTypes,
        selectedItem,
        hasBatches,
        quantityStep,
        selectTransactionType,
        getQuantityHelperText,
        getStockStatusClass,
        submitTransaction,
        cancel,
        formatDate
      };
    }
  };
  </script>

  <style scoped>
  .stock-transaction-form {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .page-header h1 {
    margin: 0;
  }

  .form-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  }

  .section-title {
    margin-bottom: 1rem;
    color: var(--gray-700);
    font-size: 1.125rem;
  }

  .transaction-types {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .transaction-type-option {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .transaction-type-option:hover {
    background-color: var(--gray-50);
  }

  .transaction-type-option.active {
    border-color: var(--primary-color);
    background-color: rgba(37, 99, 235, 0.05);
    box-shadow: 0 0 0 1px var(--primary-color);
  }

  .transaction-type-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.375rem;
    color: white;
    margin-right: 1rem;
  }

  .transaction-type-icon.bg-success {
    background-color: var(--success-color);
  }

  .transaction-type-icon.bg-danger {
    background-color: var(--danger-color);
  }

  .transaction-type-icon.bg-warning {
    background-color: var(--warning-color);
  }

  .transaction-type-icon.bg-info {
    background-color: var(--primary-color);
  }

  .transaction-type-icon.bg-secondary {
    background-color: var(--gray-600);
  }

  .transaction-type-details {
    flex: 1;
  }

  .transaction-type-label {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .transaction-type-description {
    font-size: 0.75rem;
    color: var(--gray-600);
  }

  .select-with-status {
    position: relative;
  }

  .status-indicator {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
  }

  .info-display {
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    background-color: var(--gray-50);
    min-height: 38px;
    display: flex;
    align-items: center;
    font-weight: 600;
  }

  .error-message {
    color: var(--danger-color);
    font-size: 0.75rem;
    margin-top: 0.25rem;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }

  .form-row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -0.5rem;
    margin-left: -0.5rem;
  }

  .col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding-right: 0.5rem;
    padding-left: 0.5rem;
  }

  .col-md-12 {
    flex: 0 0 100%;
    max-width: 100%;
    padding-right: 0.5rem;
    padding-left: 0.5rem;
  }

  .input-group {
    position: relative;
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    width: 100%;
  }

  .input-group-append {
    margin-left: -1px;
    display: flex;
  }

  .input-group-text {
    display: flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    font-weight: 500;
    color: var(--gray-700);
    text-align: center;
    white-space: nowrap;
    background-color: var(--gray-100);
    border: 1px solid var(--gray-300);
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
  }

  .input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
  }

  @media (max-width: 768px) {
    .col-md-6, .col-md-12 {
      flex: 0 0 100%;
      max-width: 100%;
    }

    .transaction-types {
      grid-template-columns: 1fr;
    }
  }
  </style>
