<!-- src/views/sales/CustomerCreate.vue -->
<template>
    <div class="customer-create-page">
        <div class="page-header">
            <h2>Create New Customer</h2>
            <button class="btn btn-secondary" @click="goBack">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>

        <div v-if="successMessage" class="success-message">
            {{ successMessage }}
        </div>

        <CustomerForm
            :is-submitting="isSubmitting"
            :server-errors="serverErrors"
            @submit="createCustomer"
            @cancel="goBack"
        />
    </div>
</template>

<script>
import { ref } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import CustomerForm from "@/components/sales/CustomerForm.vue";

export default {
    name: "CustomerCreate",
    components: {
        CustomerForm,
    },
    setup() {
        const router = useRouter();

        // State
        const isSubmitting = ref(false);
        const serverErrors = ref({});
        const successMessage = ref("");

        // Methods
        const createCustomer = async (formData) => {
            isSubmitting.value = true;
            serverErrors.value = {};
            successMessage.value = "";

            console.log("Creating customer with payload:", formData);

            try {
                const response = await axios.post("/sales/customers", formData);
                console.log(
                    "Customer creation successful. Response:",
                    response.data
                );

                successMessage.value = "Customer created successfully!";

                // Redirect to customer list after a short delay
                setTimeout(() => {
                    router.push("/sales/customers");
                }, 1500);
            } catch (error) {
                console.error("Error creating customer:", {
                    message: error.message,
                    response: error.response?.data,
                    status: error.response?.status,
                });

                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.errors
                ) {
                    // Handle validation errors from the server
                    serverErrors.value = error.response.data.errors;
                } else {
                    // General error
                    alert(
                        `An error occurred while creating the customer: ${error.message}`
                    );
                }
            } finally {
                isSubmitting.value = false;
            }
        };

        const goBack = () => {
            router.push("/sales/customers");
        };

        return {
            isSubmitting,
            serverErrors,
            successMessage,
            createCustomer,
            goBack,
        };
    },
};
</script>

<style scoped>
.customer-create-page {
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
    content: "✓";
    font-weight: bold;
    margin-right: 0.5rem;
}
</style>
