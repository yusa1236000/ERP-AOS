<!-- src/views/sales/CustomerEdit.vue -->
<template>
    <div class="customer-edit-page">
      <div class="page-header">
        <h2 v-if="isLoading">Loading...</h2>
        <h2 v-else-if="customer">Edit Customer: {{ customer.name }}</h2>
        <h2 v-else>Edit Customer</h2>
        
        <button class="btn btn-secondary" @click="goBack">
          <i class="fas fa-arrow-left"></i> Back
        </button>
      </div>
      
      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading customer data...
      </div>
      
      <div v-else-if="!customer" class="error-message">
        <i class="fas fa-exclamation-triangle"></i> Customer not found
        <button class="btn btn-primary" @click="goToCustomersList">Back to Customers List</button>
      </div>
      
      <div v-else>
        <div v-if="successMessage" class="success-message">
          {{ successMessage }}
        </div>
        
        <CustomerForm 
          :customer="customer"
          :is-edit-mode="true"
          :is-submitting="isSubmitting"
          :server-errors="serverErrors"
          @submit="updateCustomer"
          @cancel="goBack"
        />
      </div>
    </div>
  </template>
  
  <script>
  import { ref, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';
  import CustomerForm from '@/components/sales/CustomerForm.vue';
  
  export default {
    name: 'CustomerEdit',
    components: {
      CustomerForm
    },
    setup() {
      const route = useRoute();
      const router = useRouter();
      const customerId = route.params.id;
      
      // State
      const customer = ref(null);
      const isLoading = ref(true);
      const isSubmitting = ref(false);
      const serverErrors = ref({});
      const successMessage = ref('');
      
      // Fetch customer data
      const fetchCustomer = async () => {
        isLoading.value = true;
        
        try {
          const response = await axios.get(`/sales/api/customers/${customerId}`);
          customer.value = response.data.data;
        } catch (error) {
          console.error('Error fetching customer:', error);
          customer.value = null;
        } finally {
          isLoading.value = false;
        }
      };
      
      // Methods
      const updateCustomer = async (formData) => {
        isSubmitting.value = true;
        serverErrors.value = {};
        successMessage.value = '';
        
        try {
          await axios.put(`/sales/api/customers/${customerId}`, formData);
          
          successMessage.value = 'Customer updated successfully!';
          
          // Refresh customer data
          await fetchCustomer();
          
        } catch (error) {
          console.error('Error updating customer:', error);
          
          if (error.response && error.response.data && error.response.data.errors) {
            // Handle validation errors from the server
            serverErrors.value = error.response.data.errors;
          } else {
            // General error
            alert('An error occurred while updating the customer. Please try again.');
          }
        } finally {
          isSubmitting.value = false;
        }
      };
      
      const goBack = () => {
        router.back();
      };
      
      const goToCustomersList = () => {
        router.push('/sales/customers');
      };
      
      // Initialize
      onMounted(() => {
        fetchCustomer();
      });
      
      return {
        customer,
        isLoading,
        isSubmitting,
        serverErrors,
        successMessage,
        updateCustomer,
        goBack,
        goToCustomersList
      };
    }
  };
  </script>
  
  <style scoped>
  .customer-edit-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }
  
  .page-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #1e293b;
  }
  
  .btn {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
  }
  
  .btn-secondary:hover {
    background-color: #cbd5e1;
  }
  
  .btn-primary {
    background-color: #2563eb;
    color: white;
  }
  
  .btn-primary:hover {
    background-color: #1d4ed8;
  }
  
  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem 0;
    color: #64748b;
  }
  
  .loading-indicator i {
    margin-right: 0.5rem;
  }
  
  .error-message {
    background-color: #fee2e2;
    color: #b91c1c;
    padding: 1.5rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    text-align: center;
  }
  
  .error-message i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }
  
  .success-message {
    background-color: #d1fae5;
    color: #065f46;
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
  }
  
  .success-message:before {
    content: '✓';
    font-weight: bold;
    margin-right: 0.5rem;
  }
  </style>