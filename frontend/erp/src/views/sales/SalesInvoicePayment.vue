<!-- src/views/sales/SalesInvoicePayment.vue -->
<template>
    <div class="page-container">
      <div class="page-header">
        <h1>Invoice Payment</h1>
        <div class="header-actions">
          <router-link :to="`/sales/invoices/${id}`" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Invoice
          </router-link>
        </div>
      </div>
  
      <!-- Loading Indicator -->
      <div v-if="loading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading payment information...
      </div>
  
      <!-- Payment Content -->
      <div v-else class="payment-container">
        <!-- Invoice Summary Card -->
        <div class="card">
          <div class="card-header">
            <h2>Invoice Summary</h2>
          </div>
          <div class="card-body">
            <div class="info-section">
              <div class="info-column">
                <div class="info-row">
                  <div class="info-label">Invoice Number:</div>
                  <div class="info-value">{{ invoice.invoice_number }}</div>
                </div>
                <div class="info-row">
                  <div class="info-label">Customer:</div>
                  <div class="info-value">
                    <router-link v-if="invoice.customer" :to="`/sales/customers/${invoice.customer.customer_id}`">
                      {{ invoice.customer.name }}
                    </router-link>
                    <span v-else>N/A</span>
                  </div>
                </div>
                <div class="info-row">
                  <div class="info-label">Invoice Date:</div>
                  <div class="info-value">{{ formatDate(invoice.invoice_date) }}</div>
                </div>
                <div class="info-row">
                  <div class="info-label">Due Date:</div>
                  <div class="info-value" :class="{ 'text-danger': isOverdue(invoice.due_date, invoice.status) }">
                    {{ formatDate(invoice.due_date) }}
                    <span v-if="isOverdue(invoice.due_date, invoice.status)" class="overdue-badge">Overdue</span>
                  </div>
                </div>
              </div>
              <div class="info-column">
                <div class="info-row">
                  <div class="info-label">Total Amount:</div>
                  <div class="info-value">{{ formatCurrency(invoice.total_amount, invoice.currency_code) }}</div>
                </div>
                <div class="info-row">
                  <div class="info-label">Currency:</div>
                  <div class="info-value">{{ invoice.currency_code }}</div>
                </div>
                <div class="info-row">
                  <div class="info-label">Status:</div>
                  <div class="info-value">
                    <span class="status-badge" :class="getStatusClass(invoice.status)">
                      {{ invoice.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Payment Information Card -->
        <div class="card mt-4">
          <div class="card-header">
            <h2>Payment Details</h2>
            <div v-if="!isPaid && !isCancelled" class="header-actions">
              <button @click="showPaymentModal = true" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Payment
              </button>
            </div>
          </div>
          <div class="card-body">
            <div v-if="!receivable" class="empty-message">
              No payment information available.
            </div>
            <div v-else>
              <div class="payment-summary">
                <div class="payment-info-row">
                  <div class="payment-label">Total Amount:</div>
                  <div class="payment-value">{{ formatCurrency(receivable.amount, invoice.currency_code) }}</div>
                </div>
                <div class="payment-info-row">
                  <div class="payment-label">Amount Paid:</div>
                  <div class="payment-value">{{ formatCurrency(receivable.paid_amount || 0, invoice.currency_code) }}</div>
                </div>
                <div class="payment-info-row">
                  <div class="payment-label">Balance Due:</div>
                  <div class="payment-value" :class="{ 'text-danger': receivable.balance > 0 }">
                    {{ formatCurrency(receivable.balance || 0, invoice.currency_code) }}
                  </div>
                </div>
                <div class="payment-info-row">
                  <div class="payment-label">Payment Status:</div>
                  <div class="payment-value">
                    <span class="status-badge" :class="getPaymentStatusClass(receivable.status)">
                      {{ receivable.status }}
                    </span>
                  </div>
                </div>
              </div>
  
              <div class="mt-4">
                <h3>Payment History</h3>
                <div v-if="receivable.receivablePayments && receivable.receivablePayments.length > 0">
                  <div class="table-responsive">
                    <table class="data-table">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Amount</th>
                          <th>Invoice Amount</th>
                          <th>Payment Method</th>
                          <th>Reference</th>
                          <th>Currency</th>
                          <th>Exchange Rate</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="payment in receivable.receivablePayments" :key="payment.payment_id">
                          <td>{{ formatDate(payment.payment_date) }}</td>
                          <td>{{ formatCurrency(payment.amount, payment.payment_currency) }}</td>
                          <td>{{ formatCurrency(payment.receivable_amount, invoice.currency_code) }}</td>
                          <td>{{ payment.payment_method }}</td>
                          <td>{{ payment.reference_number || 'N/A' }}</td>
                          <td>{{ payment.payment_currency }}</td>
                          <td>{{ payment.exchange_rate || '1.00' }}</td>
                          <td>
                            <button 
                              @click="viewPaymentDetails(payment)" 
                              class="btn btn-sm btn-info"
                              title="View Details"
                            >
                              <i class="fas fa-eye"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div v-else class="empty-message">
                  No payments recorded yet.
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Exchange Rate Information -->
        <div v-if="hasForeignCurrencyPayments" class="card mt-4">
          <div class="card-header">
            <h2>Exchange Rate Information</h2>
          </div>
          <div class="card-body">
            <p class="info-text">
              This invoice has payments in multiple currencies. Exchange rates are used to convert payments to the invoice currency ({{ invoice.currency_code }}).
            </p>
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>From Currency</th>
                    <th>To Currency</th>
                    <th>Exchange Rate</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(payment, index) in foreignCurrencyPayments" :key="index">
                    <td>{{ payment.payment_currency }}</td>
                    <td>{{ invoice.currency_code }}</td>
                    <td>{{ payment.exchange_rate }}</td>
                    <td>{{ formatDate(payment.payment_date) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Payment Modal -->
      <div class="modal" v-if="showPaymentModal">
        <div class="modal-backdrop" @click="showPaymentModal = false"></div>
        <div class="modal-content">
          <div class="modal-header">
            <h2>Record Payment</h2>
            <button class="close-btn" @click="showPaymentModal = false">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="recordPayment">
              <div class="form-group">
                <label for="paymentAmount">Amount <span class="required">*</span></label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">{{ paymentData.payment_currency }}</span>
                  </div>
                  <input 
                    type="number" 
                    id="paymentAmount" 
                    v-model.number="paymentData.amount"
                    class="form-control"
                    min="0.01" 
                    step="0.01"
                    required
                  >
                </div>
                <div v-if="paymentErrors.amount" class="error-message">
                  {{ paymentErrors.amount }}
                </div>
                <small class="form-text text-muted" v-if="receivable && receivable.balance > 0">
                  Balance due: {{ formatCurrency(receivable.balance, invoice.currency_code) }}
                </small>
              </div>
              
              <div class="form-group">
                <label for="paymentDate">Payment Date <span class="required">*</span></label>
                <input 
                  type="date" 
                  id="paymentDate" 
                  v-model="paymentData.payment_date"
                  class="form-control"
                  required
                >
                <div v-if="paymentErrors.payment_date" class="error-message">
                  {{ paymentErrors.payment_date }}
                </div>
              </div>
              
              <div class="form-group">
                <label for="paymentMethod">Payment Method <span class="required">*</span></label>
                <select 
                  id="paymentMethod" 
                  v-model="paymentData.payment_method"
                  class="form-control"
                  required
                >
                  <option value="">Select Payment Method</option>
                  <option value="Cash">Cash</option>
                  <option value="Bank Transfer">Bank Transfer</option>
                  <option value="Credit Card">Credit Card</option>
                  <option value="Debit Card">Debit Card</option>
                  <option value="Check">Check</option>
                  <option value="Online Payment">Online Payment</option>
                  <option value="Other">Other</option>
                </select>
                <div v-if="paymentErrors.payment_method" class="error-message">
                  {{ paymentErrors.payment_method }}
                </div>
              </div>
              
              <div class="form-group">
                <label for="paymentCurrency">Currency</label>
                <select 
                  id="paymentCurrency" 
                  v-model="paymentData.payment_currency"
                  class="form-control"
                >
                  <option :value="invoice.currency_code">{{ invoice.currency_code }} (Invoice Currency)</option>
                  <option value="IDR">IDR - Indonesian Rupiah</option>
                  <option value="USD">USD - US Dollar</option>
                  <option value="EUR">EUR - Euro</option>
                  <option value="SGD">SGD - Singapore Dollar</option>
                  <option value="MYR">MYR - Malaysian Ringgit</option>
                </select>
                <div v-if="paymentData.payment_currency !== invoice.currency_code" class="currency-notice">
                  <i class="fas fa-info-circle"></i> 
                  Payment will be converted to {{ invoice.currency_code }} using the current exchange rate.
                </div>
              </div>
              
              <div class="form-group">
                <label for="reference">Reference Number</label>
                <input 
                  type="text" 
                  id="reference" 
                  v-model="paymentData.reference"
                  class="form-control"
                  placeholder="e.g., Transaction ID, Check Number"
                >
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="showPaymentModal = false">
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="recording"
                >
                  <i class="fas fa-spinner fa-spin" v-if="recording"></i>
                  Record Payment
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
  
      <!-- Payment Details Modal -->
      <div class="modal" v-if="showPaymentDetailsModal">
        <div class="modal-backdrop" @click="showPaymentDetailsModal = false"></div>
        <div class="modal-content">
          <div class="modal-header">
            <h2>Payment Details</h2>
            <button class="close-btn" @click="showPaymentDetailsModal = false">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div v-if="selectedPayment" class="payment-details">
              <div class="detail-row">
                <div class="detail-label">Payment Date:</div>
                <div class="detail-value">{{ formatDate(selectedPayment.payment_date) }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Amount:</div>
                <div class="detail-value">{{ formatCurrency(selectedPayment.amount, selectedPayment.payment_currency) }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Invoice Amount:</div>
                <div class="detail-value">{{ formatCurrency(selectedPayment.receivable_amount, invoice.currency_code) }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Payment Method:</div>
                <div class="detail-value">{{ selectedPayment.payment_method }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Reference Number:</div>
                <div class="detail-value">{{ selectedPayment.reference_number || 'N/A' }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Payment Currency:</div>
                <div class="detail-value">{{ selectedPayment.payment_currency }}</div>
              </div>
              <div v-if="selectedPayment.payment_currency !== invoice.currency_code" class="detail-row">
                <div class="detail-label">Exchange Rate:</div>
                <div class="detail-value">1 {{ selectedPayment.payment_currency }} = {{ selectedPayment.exchange_rate }} {{ invoice.currency_code }}</div>
              </div>
              <div v-if="selectedPayment.exchange_difference && selectedPayment.exchange_difference !== 0" class="detail-row">
                <div class="detail-label">Exchange Difference:</div>
                <div class="detail-value">{{ formatCurrency(selectedPayment.exchange_difference, selectedPayment.payment_currency) }}</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showPaymentDetailsModal = false">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'SalesInvoicePayment',
    props: {
      id: {
        type: [Number, String],
        required: true
      }
    },
    data() {
      return {
        invoice: {},
        receivable: null,
        loading: true,
        showPaymentModal: false,
        showPaymentDetailsModal: false,
        selectedPayment: null,
        recording: false,
        paymentData: {
          amount: 0,
          payment_date: new Date().toISOString().split('T')[0],
          payment_method: '',
          payment_currency: '',
          reference: ''
        },
        paymentErrors: {}
      };
    },
    computed: {
      isPaid() {
        return this.invoice.status === 'Paid';
      },
      isCancelled() {
        return this.invoice.status === 'Cancelled';
      },
      hasForeignCurrencyPayments() {
        if (!this.receivable || !this.receivable.receivablePayments) return false;
        
        return this.receivable.receivablePayments.some(payment => 
          payment.payment_currency !== this.invoice.currency_code
        );
      },
      foreignCurrencyPayments() {
        if (!this.receivable || !this.receivable.receivablePayments) return [];
        
        return this.receivable.receivablePayments.filter(payment => 
          payment.payment_currency !== this.invoice.currency_code
        );
      }
    },
    mounted() {
      this.fetchPaymentInfo();
    },
    methods: {
      async fetchPaymentInfo() {
        this.loading = true;
        
        try {
          // First get the invoice details
          const invoiceResponse = await axios.get(`/sales/invoices/${this.id}`);
          this.invoice = invoiceResponse.data.data;
          
          // Then get payment information
          const paymentResponse = await axios.get(`/sales/invoices/${this.id}/payment-info`);
          this.receivable = paymentResponse.data.data;
          
          // Set payment currency to invoice currency by default
          this.paymentData.payment_currency = this.invoice.currency_code;
          
          // Set default payment amount to balance due
          if (this.receivable && this.receivable.balance) {
            this.paymentData.amount = this.receivable.balance;
          }
        } catch (error) {
          console.error('Error fetching payment information:', error);
          this.$toast.error('Failed to load payment information');
        } finally {
          this.loading = false;
        }
      },
      getStatusClass(status) {
        switch (status) {
          case 'Draft':
            return 'status-draft';
          case 'Sent':
            return 'status-sent';
          case 'Partially Paid':
            return 'status-partial';
          case 'Paid':
            return 'status-paid';
          case 'Overdue':
            return 'status-overdue';
          case 'Cancelled':
            return 'status-cancelled';
          default:
            return '';
        }
      },
      getPaymentStatusClass(status) {
        switch (status) {
          case 'Open':
            return 'status-draft';
          case 'Partial':
            return 'status-partial';
          case 'Closed':
            return 'status-paid';
          default:
            return '';
        }
      },
      isOverdue(dueDate, status) {
        if (!dueDate || status === 'Paid' || status === 'Cancelled') return false;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const due = new Date(dueDate);
        return due < today;
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
      viewPaymentDetails(payment) {
        this.selectedPayment = payment;
        this.showPaymentDetailsModal = true;
      },
      async recordPayment() {
        this.paymentErrors = {};
        
        // Basic validation
        if (!this.paymentData.amount || this.paymentData.amount <= 0) {
          this.paymentErrors.amount = 'Amount must be greater than zero';
          return;
        }
        
        if (!this.paymentData.payment_date) {
          this.paymentErrors.payment_date = 'Payment date is required';
          return;
        }
        
        if (!this.paymentData.payment_method) {
          this.paymentErrors.payment_method = 'Payment method is required';
          return;
        }
        
        this.recording = true;
        
        try {
          await axios.post(`/sales/invoices/${this.id}/recordPayment`, {
            amount: this.paymentData.amount,
            payment_date: this.paymentData.payment_date,
            payment_method: this.paymentData.payment_method,
            reference: this.paymentData.reference,
            payment_currency: this.paymentData.payment_currency
          });
          
          this.$toast.success('Payment recorded successfully');
          this.showPaymentModal = false;
          
          // Refresh payment information
          this.fetchPaymentInfo();
        } catch (error) {
          console.error('Error recording payment:', error);
          if (error.response && error.response.data.errors) {
            const errors = error.response.data.errors;
            
            // Map API validation errors to form fields
            for (const field in errors) {
              this.paymentErrors[field.replace('amount', 'amount')] = errors[field][0];
            }
            
            this.$toast.error('Please correct the errors in the form');
          } else if (error.response && error.response.data.message) {
            this.$toast.error(error.response.data.message);
          } else {
            this.$toast.error('Failed to record payment. Please try again.');
          }
        } finally {
          this.recording = false;
        }
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
  
  .payment-container {
    max-width: 100%;
  }
  
  .card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
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
  
  .info-section {
    display: flex;
    flex-wrap: wrap;
    margin: -0.75rem;
  }
  
  .info-column {
    flex: 1;
    min-width: 250px;
    padding: 0.75rem;
  }
  
  .info-row {
    display: flex;
    margin-bottom: 1rem;
  }
  
  .info-label {
    width: 40%;
    font-weight: 500;
    color: var(--gray-600);
  }
  
  .info-value {
    width: 60%;
    color: var(--gray-900);
  }
  
  .status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-align: center;
    white-space: nowrap;
  }
  
  .status-draft {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }
  
  .status-sent {
    background-color: #dbeafe;
    color: #1e40af;
  }
  
  .status-partial {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .status-paid {
    background-color: #d1fae5;
    color: #065f46;
  }
  
  .status-overdue {
    background-color: #fee2e2;
    color: #b91c1c;
  }
  
  .status-cancelled {
    background-color: var(--gray-200);
    color: var(--gray-700);
  }
  
  .overdue-badge {
    display: inline-block;
    margin-left: 0.5rem;
    padding: 0.125rem 0.375rem;
    background-color: #fee2e2;
    color: #b91c1c;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  .payment-summary {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    padding: 1.5rem;
    background-color: var(--gray-50);
  }
  
  .payment-info-row {
    display: flex;
    margin-bottom: 1rem;
  }
  
  .payment-info-row:last-child {
    margin-bottom: 0;
  }
  
  .payment-label {
    width: 40%;
    font-weight: 500;
    color: var(--gray-600);
  }
  
  .payment-value {
    width: 60%;
    color: var(--gray-900);
    font-weight: 600;
  }
  
  .mt-4 {
    margin-top: 1.5rem;
  }
  
  h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--gray-700);
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
  
  .empty-message {
    color: var(--gray-500);
    font-style: italic;
    padding: 1rem 0;
  }
  
  .info-text {
    color: var(--gray-700);
    margin-bottom: 1rem;
  }
  
  .text-danger {
    color: #ef4444;
  }
  
  .text-muted {
    color: var(--gray-500);
    font-size: 0.75rem;
    margin-top: 0.25rem;
  }
  
  .currency-notice {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background-color: #f0f9ff;
    border-radius: 0.375rem;
    color: #0369a1;
    font-size: 0.75rem;
  }
  
  .currency-notice i {
    margin-right: 0.25rem;
  }
  
  .modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 50;
  }
  
  .modal-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 500px;
    z-index: 60;
    overflow: hidden;
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
  }
  
  .close-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    border-radius: 0.25rem;
  }
  
  .close-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
  }
  
  .payment-details {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    padding: 1rem;
  }
  
  .detail-row {
    display: flex;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-100);
  }
  
  .detail-row:last-child {
    border-bottom: none;
  }
  
  .detail-label {
    width: 40%;
    font-weight: 500;
    color: var(--gray-600);
  }
  
  .detail-value {
    width: 60%;
    color: var(--gray-900);
  }
  
  .form-group {
    margin-bottom: 1.5rem;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
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
  
  .input-group {
    display: flex;
    width: 100%;
  }
  
  .input-group-prepend {
    display: flex;
  }
  
  .input-group-text {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    color: var(--gray-700);
    text-align: center;
    white-space: nowrap;
    background-color: var(--gray-100);
    border: 1px solid var(--gray-300);
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
    border-right: none;
  }
  
  .input-group .form-control {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    flex: 1;
  }
  
  .required {
    color: #ef4444;
  }
  
  .error-message {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.25rem;
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
  
  .btn-secondary {
    color: var(--gray-700);
    background-color: var(--gray-100);
    border-color: var(--gray-300);
  }
  
  .btn-secondary:hover {
    background-color: var(--gray-200);
    border-color: var(--gray-400);
  }
  
  .btn-info {
    background-color: #dbeafe;
    color: #1e40af;
    border-color: #bfdbfe;
  }
  
  .btn-info:hover {
    background-color: #bfdbfe;
    color: #1e3a8a;
  }
  
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
  
  .btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }
  
  .btn i {
    margin-right: 0.5rem;
  }
  
  @media (max-width: 768px) {
    .info-section, 
    .info-row, 
    .payment-info-row,
    .detail-row {
      flex-direction: column;
    }
    
    .info-label, 
    .info-value, 
    .payment-label, 
    .payment-value,
    .detail-label,
    .detail-value {
      width: 100%;
    }
    
    .info-label, 
    .payment-label,
    .detail-label {
      margin-bottom: 0.25rem;
    }
    
    .info-value, 
    .payment-value,
    .detail-value {
      margin-bottom: 0.75rem;
    }
  }
  </style>