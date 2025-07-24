<!-- src/views/sales/CreateInvoiceFromDelivery.vue -->
<template>
    <div class="page-container">
      <div class="page-header">
        <h1>Create Invoice from Delivery</h1>
        <div class="header-actions">
          <router-link to="/sales/invoices" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Invoices
          </router-link>
        </div>
      </div>
  
      <!-- Loading Indicator -->
      <div v-if="loading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading...
      </div>
  
      <!-- Form Content -->
      <div v-else class="form-container">
        <form @submit.prevent="createInvoice">
          <!-- Basic Information Card -->
          <div class="card">
            <div class="card-header">
              <h2>Basic Information</h2>
            </div>
            <div class="card-body">
              <div class="form-row">
                <div class="form-group">
                  <label for="invoiceNumber">Invoice Number <span class="required">*</span></label>
                  <input 
                    type="text" 
                    id="invoiceNumber" 
                    v-model="invoice.invoice_number"
                    required
                    class="form-control"
                  >
                  <div v-if="errors.invoice_number" class="error-message">
                    {{ errors.invoice_number[0] }}
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="invoiceDate">Invoice Date <span class="required">*</span></label>
                  <input 
                    type="date" 
                    id="invoiceDate" 
                    v-model="invoice.invoice_date"
                    required
                    class="form-control"
                  >
                  <div v-if="errors.invoice_date" class="error-message">
                    {{ errors.invoice_date[0] }}
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="dueDate">Due Date <span class="required">*</span></label>
                  <input 
                    type="date" 
                    id="dueDate" 
                    v-model="invoice.due_date"
                    required
                    class="form-control"
                  >
                  <div v-if="errors.due_date" class="error-message">
                    {{ errors.due_date[0] }}
                  </div>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="customer">Customer <span class="required">*</span></label>
                  <select 
                    id="customer" 
                    v-model="invoice.customer_id"
                    class="form-control"
                    required
                    @change="handleCustomerChange"
                  >
                    <option value="">Select Customer</option>
                    <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                      {{ customer.name }}
                    </option>
                  </select>
                  <div v-if="errors.customer_id" class="error-message">
                    {{ errors.customer_id[0] }}
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="status">Status <span class="required">*</span></label>
                  <select 
                    id="status" 
                    v-model="invoice.status"
                    class="form-control"
                    required
                  >
                    <option value="Draft">Draft</option>
                    <option value="Sent">Sent</option>
                  </select>
                  <div v-if="errors.status" class="error-message">
                    {{ errors.status[0] }}
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="currency">Currency</label>
                  <select 
                    id="currency" 
                    v-model="invoice.currency_code"
                    class="form-control"
                  >
                    <option value="IDR">IDR - Indonesian Rupiah</option>
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="SGD">SGD - Singapore Dollar</option>
                    <option value="MYR">MYR - Malaysian Ringgit</option>
                  </select>
                  <div v-if="errors.currency_code" class="error-message">
                    {{ errors.currency_code[0] }}
                  </div>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="reference">Reference Number</label>
                  <input 
                    type="text" 
                    id="reference" 
                    v-model="invoice.reference"
                    class="form-control"
                    placeholder="Optional reference number"
                  >
                </div>
                
                <div class="form-group">
                  <label for="paymentTerms">Payment Terms</label>
                  <select 
                    id="paymentTerms" 
                    v-model="invoice.payment_terms"
                    class="form-control"
                  >
                    <option value="">Select Payment Terms</option>
                    <option value="Net 15">Net 15 (Due in 15 days)</option>
                    <option value="Net 30">Net 30 (Due in 30 days)</option>
                    <option value="Net 45">Net 45 (Due in 45 days)</option>
                    <option value="Net 60">Net 60 (Due in 60 days)</option>
                    <option value="Due on Receipt">Due on Receipt</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Delivery Order Selection -->
          <div class="card mt-4">
            <div class="card-header">
              <h2>Select Delivery Orders</h2>
            </div>
            <div class="card-body">
              <div v-if="loadingDeliveries" class="loading-text">
                <i class="fas fa-spinner fa-spin"></i> Loading deliveries...
              </div>
              <div v-else-if="deliveries.length === 0" class="empty-message">
                No deliveries available for this customer or all deliveries are already invoiced.
              </div>
              <div v-else>
                <p class="info-text">Select one or more deliveries to include in this invoice:</p>
                <div class="delivery-selection">
                  <div v-for="delivery in deliveries" :key="delivery.delivery_id" class="delivery-item">
                    <div class="form-check">
                      <input
                        type="checkbox"
                        :id="`delivery-${delivery.delivery_id}`"
                        v-model="selectedDeliveries"
                        :value="delivery.delivery_id"
                        class="form-check-input"
                        @change="handleDeliverySelection"
                      >
                      <label :for="`delivery-${delivery.delivery_id}`" class="form-check-label">
                        <div class="delivery-header">
                          <strong>{{ delivery.delivery_number }}</strong> - {{ formatDate(delivery.delivery_date) }}
                        </div>
                        <div class="delivery-details">
                          <div class="detail-item">
                            <span class="detail-label">Shipping Method:</span>
                            <span class="detail-value">{{ delivery.shipping_method || 'Not specified' }}</span>
                          </div>
                          <div class="detail-item">
                            <span class="detail-label">Tracking Number:</span>
                            <span class="detail-value">{{ delivery.tracking_number || 'Not specified' }}</span>
                          </div>
                          <div class="detail-item">
                            <span class="detail-label">Status:</span>
                            <span class="status-badge status-delivered">{{ delivery.status }}</span>
                          </div>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>
                <div v-if="errors.delivery_ids" class="error-message mt-2">
                  {{ errors.delivery_ids[0] }}
                </div>
              </div>
            </div>
          </div>
          
          <!-- Invoice Items Preview Card -->
          <div class="card mt-4">
            <div class="card-header">
              <h2>Invoice Items Preview</h2>
            </div>
            <div class="card-body">
              <div v-if="isLoadingItems" class="loading-text">
                <i class="fas fa-spinner fa-spin"></i> Loading items...
              </div>
              <div v-else-if="itemsPreview.length === 0" class="empty-message">
                Select one or more deliveries to preview invoice items.
              </div>
              <div v-else class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th>Description</th>
                      <th>Quantity</th>
                      <th>Unit Price</th>
                      <th>Subtotal</th>
                      <th>From Deliveries</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in itemsPreview" :key="index">
                      <td>
                        <div class="item-details">
                          <strong>{{ item.item_code }}</strong>
                          <div>{{ item.item_name }}</div>
                        </div>
                      </td>
                      <td>{{ item.item_name }}</td>
                      <td>{{ item.total_quantity }}</td>
                      <td>{{ formatCurrency(item.unit_price, invoice.currency_code) }}</td>
                      <td>{{ formatCurrency(item.total_quantity * item.unit_price, invoice.currency_code) }}</td>
                      <td>
                        <div class="delivery-tags">
                          <span v-for="line in item.delivery_lines" :key="line.do_line_id" class="delivery-tag">
                            {{ findDeliveryNumber(line.do_id) }}
                          </span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="4" class="text-right"><strong>Grand Total:</strong></td>
                      <td colspan="2">{{ formatCurrency(calculateTotalAmount(), invoice.currency_code) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
          
          <!-- Form Actions -->
          <div class="form-actions mt-4">
            <button type="button" class="btn btn-secondary" @click="goBack">
              Cancel
            </button>
            <button 
              type="submit" 
              class="btn btn-primary" 
              :disabled="saving || selectedDeliveries.length === 0 || itemsPreview.length === 0"
            >
              <i class="fas fa-spinner fa-spin" v-if="saving"></i>
              Create Invoice
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'CreateInvoiceFromDelivery',
    data() {
      return {
        loading: true,
        saving: false,
        customers: [],
        deliveries: [],
        selectedDeliveries: [],
        itemsPreview: [],
        loadingDeliveries: false,
        isLoadingItems: false,
        invoice: {
          invoice_number: '',
          invoice_date: new Date().toISOString().split('T')[0],
          customer_id: '',
          due_date: '',
          status: 'Draft',
          currency_code: 'IDR',
          reference: '',
          payment_terms: ''
        },
        errors: {}
      };
    },
    computed: {
      defaultDueDate() {
        const today = new Date();
        // Default to 30 days from now
        today.setDate(today.getDate() + 30);
        return today.toISOString().split('T')[0];
      }
    },
    mounted() {
      this.initialize();
    },
    methods: {
      async initialize() {
        this.loading = true;
        try {
          await this.loadCustomers();
          
          // Generate invoice number
          this.generateInvoiceNumber();
          
          // Set default due date
          this.invoice.due_date = this.defaultDueDate;
          
          // Check if customer ID is provided in the query string
          const customerId = this.$route.query.customer_id;
          if (customerId) {
            this.invoice.customer_id = customerId;
            await this.handleCustomerChange();
          }
        } catch (error) {
          console.error('Error initializing form:', error);
          this.$toast.error('Failed to initialize form');
        } finally {
          this.loading = false;
        }
      },
      
      async loadCustomers() {
        try {
          const response = await axios.get('/sales/customers');
          this.customers = response.data.data || response.data;
        } catch (error) {
          console.error('Error loading customers:', error);
          this.$toast.error('Failed to load customers');
        }
      },
      
      generateInvoiceNumber() {
        // Generate invoice number based on current date
        const date = new Date();
        const year = date.getFullYear().toString().substr(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        
        this.invoice.invoice_number = `INV-${year}${month}${day}-${random}`;
      },
      
      async handleCustomerChange() {
        if (!this.invoice.customer_id) {
          this.deliveries = [];
          this.selectedDeliveries = [];
          this.itemsPreview = [];
          return;
        }
        
        // Find selected customer
        const selectedCustomer = this.customers.find(c => c.customer_id === this.invoice.customer_id);
        
        if (selectedCustomer) {
          // Set preferred currency if available
          if (selectedCustomer.preferred_currency) {
            this.invoice.currency_code = selectedCustomer.preferred_currency;
          }
          
          // Set payment terms if available
          if (selectedCustomer.payment_term) {
            this.invoice.payment_terms = selectedCustomer.payment_term;
            
            // Adjust due date based on payment terms
            const today = new Date();
            let daysToAdd = 30; // Default
            
            if (this.invoice.payment_terms.includes('15')) {
              daysToAdd = 15;
            } else if (this.invoice.payment_terms.includes('30')) {
              daysToAdd = 30;
            } else if (this.invoice.payment_terms.includes('45')) {
              daysToAdd = 45;
            } else if (this.invoice.payment_terms.includes('60')) {
              daysToAdd = 60;
            } else if (this.invoice.payment_terms.includes('Receipt')) {
              daysToAdd = 0;
            }
            
            today.setDate(today.getDate() + daysToAdd);
            this.invoice.due_date = today.toISOString().split('T')[0];
          }
        }
        
        // Load deliveries for the selected customer
        await this.loadDeliveries();
      },
      
      async loadDeliveries() {
        if (!this.invoice.customer_id) return;
        
        this.loadingDeliveries = true;
        this.deliveries = [];
        this.selectedDeliveries = [];
        this.itemsPreview = [];
        
        try {
          // API endpoint to get deliveries ready for invoicing for a specific customer
          const response = await axios.get('/sales/invoices/getDeliveriesForInvoicing', {
            params: { customer_id: this.invoice.customer_id }
          });
          
          this.deliveries = response.data.data || [];
        } catch (error) {
          console.error('Error loading deliveries:', error);
          this.$toast.error('Failed to load deliveries');
        } finally {
          this.loadingDeliveries = false;
        }
      },
      
      async handleDeliverySelection() {
        if (this.selectedDeliveries.length === 0) {
          this.itemsPreview = [];
          return;
        }
        
        this.isLoadingItems = true;
        
        try {
          // API endpoint to get delivery lines grouped by item
          const response = await axios.get('/sales/invoices/getDeliveryLinesByItem', {
            params: { 
              customer_id: this.invoice.customer_id,
              delivery_ids: this.selectedDeliveries
            }
          });
          
          this.itemsPreview = response.data.data || [];
        } catch (error) {
          console.error('Error loading delivery items:', error);
          this.$toast.error('Failed to load delivery items');
        } finally {
          this.isLoadingItems = false;
        }
      },
      
      findDeliveryNumber(deliveryId) {
        const delivery = this.deliveries.find(d => d.delivery_id === deliveryId);
        return delivery ? delivery.delivery_number : 'Unknown';
      },
      
      calculateTotalAmount() {
        return this.itemsPreview.reduce((total, item) => {
          return total + (item.total_quantity * item.unit_price);
        }, 0);
      },
      
      formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      },
      
      formatCurrency(amount, currencyCode = 'IDR') {
        if (amount === undefined || amount === null) return 'N/A';
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: currencyCode || 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 2
        }).format(amount);
      },
      
      async createInvoice() {
        if (this.selectedDeliveries.length === 0) {
          this.$toast.error('Please select at least one delivery');
          return;
        }
        
        this.saving = true;
        this.errors = {};
        
        try {
          const payload = {
            invoice_number: this.invoice.invoice_number,
            invoice_date: this.invoice.invoice_date,
            due_date: this.invoice.due_date,
            customer_id: this.invoice.customer_id,
            status: this.invoice.status,
            currency_code: this.invoice.currency_code,
            reference: this.invoice.reference,
            payment_terms: this.invoice.payment_terms,
            delivery_ids: this.selectedDeliveries
          };
          
          const response = await axios.post('/sales/invoices/from-deliveries', payload);
          
          this.$toast.success('Invoice created successfully');
          
          // Navigate to the detail page
          const invoiceId = response.data.data.invoice_id;
          this.$router.push(`/sales/invoices/${invoiceId}`);
        } catch (error) {
          console.error('Error creating invoice:', error);
          
          if (error.response && error.response.data.errors) {
            this.errors = error.response.data.errors;
            this.$toast.error('Please correct the errors in the form');
          } else if (error.response && error.response.data.message) {
            this.$toast.error(error.response.data.message);
          } else {
            this.$toast.error('Failed to create invoice. Please try again.');
          }
        } finally {
          this.saving = false;
        }
      },
      
      goBack() {
        this.$router.go(-1);
      }
    }
  };
  </script>
  
  <style scoped>
  .page-container {
    padding: 1.5rem;
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
  
  .header-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem 0;
    color: var(--gray-500);
    font-size: 0.875rem;
  }
  
  .loading-indicator i {
    margin-right: 0.5rem;
  }
  
  .loading-text {
    display: flex;
    align-items: center;
    color: var(--gray-500);
    font-size: 0.875rem;
    padding: 0.5rem 0;
  }
  
  .loading-text i {
    margin-right: 0.5rem;
  }
  
  .empty-message {
    color: var(--gray-500);
    font-style: italic;
    padding: 1rem 0;
  }
  
  .form-container {
    max-width: 100%;
  }
  
  .card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
  }
  
  .card-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
  }
  
  .card-body {
    padding: 1.5rem;
  }
  
  .form-row {
    display: flex;
    flex-wrap: wrap;
    margin: -0.5rem;
    margin-bottom: 1rem;
  }
  
  .form-group {
    flex: 1;
    min-width: 0;
    padding: 0.5rem;
  }
  
  .form-control {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--gray-800);
    background-color: white;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }
  
  .form-control:focus {
    border-color: var(--primary-color);
    outline: 0;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
  }
  
  .required {
    color: #dc2626;
  }
  
  .error-message {
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 0.25rem;
  }
  
  .mt-2 {
    margin-top: 0.5rem;
  }
  
  .mt-4 {
    margin-top: 1.5rem;
  }
  
  .info-text {
    color: var(--gray-700);
    margin-bottom: 1rem;
  }
  
  .delivery-selection {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
  }
  
  .delivery-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    transition: background-color 0.2s;
  }
  
  .delivery-item:hover {
    background-color: var(--gray-50);
  }
  
  .delivery-item:last-child {
    border-bottom: none;
  }
  
  .form-check {
    display: flex;
    align-items: flex-start;
  }
  
  .form-check-input {
    margin-top: 0.25rem;
    margin-right: 0.75rem;
    flex-shrink: 0;
  }
  
  .form-check-label {
    flex: 1;
    margin-bottom: 0;
  }
  
  .delivery-header {
    font-size: 0.9375rem;
    margin-bottom: 0.375rem;
  }
  
  .delivery-details {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.8125rem;
    color: var(--gray-600);
  }
  
  .detail-item {
    display: flex;
    gap: 0.25rem;
  }
  
  .detail-label {
    font-weight: 500;
  }
  
  .status-badge {
    display: inline-block;
    padding: 0.125rem 0.375rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-align: center;
    white-space: nowrap;
  }
  
  .status-delivered {
    background-color: #d1fae5;
    color: #065f46;
  }
  
  .table-responsive {
    overflow-x: auto;
  }
  
  .data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }
  
  .data-table th {
    text-align: left;
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
    font-weight: 500;
    color: var(--gray-600);
  }
  
  .data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
  }
  
  .data-table tfoot tr td {
    padding: 0.75rem;
    background-color: var(--gray-50);
    font-weight: 500;
  }
  
  .text-right {
    text-align: right;
  }
  
  .item-details {
    display: flex;
    flex-direction: column;
  }
  
  .delivery-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
  }
  
  .delivery-tag {
    display: inline-block;
    padding: 0.125rem 0.375rem;
    background-color: #dbeafe;
    color: #1e40af;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
  }
  
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.5;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    cursor: pointer;
    user-select: none;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }
  
  .btn-primary {
    color: white;
    background-color: var(--primary-color);
    border-color: var(--primary-color);
  }
  
  .btn-primary:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
  }
  
  .btn-primary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }
  
  .btn-secondary {
    color: var(--gray-700);
    background-color: var(--gray-100);
    border-color: var(--gray-300);
  }
  
  .btn-secondary:hover {
    background-color: var(--gray-200);
    border-color: var(--gray-400);
  }
  
  .btn i {
    margin-right: 0.5rem;
  }
  
  @media (max-width: 768px) {
    .form-row {
      flex-direction: column;
    }
    
    .form-group {
      flex: none;
      width: 100%;
    }
    
    .delivery-details {
      flex-direction: column;
      gap: 0.375rem;
    }
  }
  </style>