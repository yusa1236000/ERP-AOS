<!-- src/views/sales/SalesReturnForm.vue -->
<template>
    <div class="return-form">
        <div class="page-header">
            <h1>
                {{
                    isEditMode ? "Edit Pengembalian" : "Buat Pengembalian Baru"
                }}
            </h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button
                    class="btn btn-primary"
                    @click="saveReturn"
                    :disabled="isSubmitting"
                >
                    <i class="fas fa-save"></i>
                    {{ isSubmitting ? "Menyimpan..." : "Simpan" }}
                </button>
            </div>
        </div>

        <div v-if="error" class="alert alert-danger">
            {{ error }}
        </div>

        <div class="form-container">
            <div class="form-card">
                <div class="card-header">
                    <h2>Informasi Pengembalian</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="return_number"
                                >Nomor Pengembalian*</label
                            >
                            <input
                                type="text"
                                id="return_number"
                                v-model="form.return_number"
                                required
                                :readonly="isEditMode"
                                :class="{ readonly: isEditMode }"
                            />
                            <small v-if="isEditMode" class="text-muted"
                                >Nomor pengembalian tidak dapat diubah</small
                            >
                        </div>

                        <div class="form-group">
                            <label for="return_date"
                                >Tanggal Pengembalian*</label
                            >
                            <input
                                type="date"
                                id="return_date"
                                v-model="form.return_date"
                                required
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_id">Pelanggan*</label>
                            <select
                                id="customer_id"
                                v-model="form.customer_id"
                                required
                                @change="loadCustomerInvoices"
                                :disabled="isEditMode"
                            >
                                <option value="">-- Pilih Pelanggan --</option>
                                <option
                                    v-for="customer in customers"
                                    :key="customer.customer_id"
                                    :value="customer.customer_id"
                                >
                                    {{ customer.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="invoice_id">Faktur*</label>
                            <select
                                id="invoice_id"
                                v-model="form.invoice_id"
                                required
                                @change="loadInvoiceLines"
                                :disabled="isEditMode || !form.customer_id"
                            >
                                <option value="">-- Pilih Faktur --</option>
                                <option
                                    v-for="invoice in invoices"
                                    :key="invoice.invoice_id"
                                    :value="invoice.invoice_id"
                                >
                                    {{ invoice.invoice_number }} ({{
                                        formatDate(invoice.invoice_date)
                                    }})
                                </option>
                            </select>
                            <small v-if="!form.customer_id" class="text-muted">
                                Pilih pelanggan terlebih dahulu
                            </small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="return_reason"
                                >Alasan Pengembalian*</label
                            >
                            <textarea
                                id="return_reason"
                                v-model="form.return_reason"
                                required
                                rows="3"
                                placeholder="Contoh: Barang rusak, tidak sesuai pesanan, dll."
                            ></textarea>
                        </div>

                        <div class="form-group" v-if="isEditMode">
                            <label for="status">Status*</label>
                            <select id="status" v-model="form.status" required>
                                <option value="Pending">Pending</option>
                                <option
                                    value="Processed"
                                    :disabled="form.status !== 'Processed'"
                                >
                                    Diproses
                                </option>
                                <option
                                    value="Completed"
                                    :disabled="form.status !== 'Completed'"
                                >
                                    Selesai
                                </option>
                                <option value="Cancelled">Dibatalkan</option>
                            </select>
                            <small
                                v-if="
                                    form.status === 'Processed' ||
                                    form.status === 'Completed'
                                "
                                class="text-muted"
                            >
                                Status tidak dapat diubah ke status yang lebih
                                rendah
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="card-header">
                    <h2>Item Pengembalian</h2>
                </div>
                <div class="card-body">
                    <div
                        v-if="!form.invoice_id && !isEditMode"
                        class="empty-lines"
                    >
                        <p>
                            Pilih faktur terlebih dahulu untuk melihat item-item
                            yang dapat dikembalikan.
                        </p>
                    </div>

                    <div v-else>
                        <div
                            v-if="invoiceLines.length === 0"
                            class="empty-lines"
                        >
                            <p>
                                Tidak ada item yang tersedia untuk dikembalikan
                                pada faktur ini.
                            </p>
                        </div>

                        <div v-else class="return-lines">
                            <div class="lines-header">
                                <div class="header">Item</div>
                                <div class="header">Jumlah Faktur</div>
                                <div class="header">Jumlah Kembali*</div>
                                <div class="header">Kondisi*</div>
                                <div class="header">Catatan</div>
                            </div>

                            <div
                                v-for="(line, index) in invoiceLines"
                                :key="line.line_id"
                                class="return-line-item"
                            >
                                <div class="line-field">
                                    <div class="item-info">
                                        <div class="item-name">
                                            {{ line.item.name }}
                                        </div>
                                        <div class="item-code">
                                            {{ line.item.item_code }}
                                        </div>
                                    </div>
                                </div>

                                <div class="line-field quantity-field">
                                    {{ line.quantity }}
                                    <span class="uom">{{
                                        getUomSymbol(line.uom_id)
                                    }}</span>
                                </div>

                                <div class="line-field">
                                    <input
                                        type="number"
                                        v-model="
                                            form.lines[index].returned_quantity
                                        "
                                        min="0"
                                        :max="line.quantity"
                                        step="1"
                                        required
                                    />
                                </div>

                                <div class="line-field">
                                    <select
                                        v-model="form.lines[index].condition"
                                        required
                                    >
                                        <option value="">
                                            -- Pilih Kondisi --
                                        </option>
                                        <option value="Good">Baik</option>
                                        <option value="Damaged">Rusak</option>
                                        <option value="Defective">Cacat</option>
                                    </select>
                                </div>

                                <div class="line-field">
                                    <input
                                        type="text"
                                        v-model="form.lines[index].notes"
                                        placeholder="Catatan opsional"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="goBack">
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="saveReturn"
                    :disabled="isSubmitting || !isFormValid"
                >
                    {{ isSubmitting ? "Menyimpan..." : "Simpan Pengembalian" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import SalesReturnService from "@/services/SalesReturnService";
import UnitOfMeasureService from "@/services/UnitOfMeasureService";
import axios from 'axios';

export default {
    name: "SalesReturnForm",
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Form data
        const form = ref({
            return_number: "",
            return_date: new Date().toISOString().substr(0, 10),
            customer_id: "",
            invoice_id: "",
            return_reason: "",
            status: "Pending",
            lines: [],
        });

        // Reference data
        const customers = ref([]);
        const invoices = ref([]);
        const invoiceLines = ref([]);
        const unitOfMeasures = ref([]);

        // UI state
        const isLoading = ref(false);
        const isSubmitting = ref(false);
        const error = ref("");

        // Check if we're in edit mode
        const isEditMode = computed(() => {
            return route.params.id !== undefined;
        });

        // Check if form is valid for submission
        const isFormValid = computed(() => {
            // Basic validation
            if (
                !form.value.return_number ||
                !form.value.return_date ||
                !form.value.customer_id ||
                !form.value.invoice_id ||
                !form.value.return_reason
            ) {
                return false;
            }

            // Validate lines
            if (!form.value.lines.length) return false;

            // Check if at least one line has a quantity greater than 0
            const hasReturnedItems = form.value.lines.some(
                (line) => line.returned_quantity > 0 && line.condition
            );

            return hasReturnedItems;
        });

        // Generate a unique return number
        const generateReturnNumber = () => {
            const today = new Date();
            const year = today.getFullYear().toString().slice(-2);
            const month = (today.getMonth() + 1).toString().padStart(2, "0");
            const day = today.getDate().toString().padStart(2, "0");
            const random = Math.floor(Math.random() * 1000)
                .toString()
                .padStart(3, "0");

            return `RTN${year}${month}${day}-${random}`;
        };

        // Load reference data
        const loadReferenceData = async () => {
            try {
                // Load customers
                const response = await axios.get("/sales/customers");
                customers.value = response.data.data || response.data || [];
                // const data = await response.json();
                // customers.value = data.data;

                // Load UOM
                const uomResponse = await UnitOfMeasureService.getAll();
                unitOfMeasures.value = uomResponse.data || [];
            } catch (err) {
                console.error("Error loading reference data:", err);
                error.value = "Failed to load reference data.";
            }
        };

        // Load customer invoices
        const loadCustomerInvoices = async () => {
            if (!form.value.customer_id) {
                invoices.value = [];
                form.value.invoice_id = "";
                return;
            }

            isLoading.value = true;
            try {
                const response = await SalesReturnService.getCustomerInvoices(
                    form.value.customer_id
                );
                invoices.value = response.data || [];
            } catch (err) {
                console.error("Error loading customer invoices:", err);
                error.value = "Failed to load customer invoices.";
                invoices.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Load invoice lines
        const loadInvoiceLines = async () => {
            if (!form.value.invoice_id) {
                invoiceLines.value = [];
                form.value.lines = [];
                return;
            }

            isLoading.value = true;
            try {
                const response = await SalesReturnService.getInvoiceDetails(
                    form.value.invoice_id
                );

                // Get invoice lines
                const invoice = response.data;
                if (invoice && invoice.sales_invoice_lines) {
                    invoiceLines.value = invoice.sales_invoice_lines;

                    // Initialize form lines
                    form.value.lines = invoice.sales_invoice_lines.map(
                        (line) => ({
                            invoice_line_id: line.line_id,
                            returned_quantity: 0,
                            condition: "",
                            notes: "",
                        })
                    );
                } else {
                    invoiceLines.value = [];
                    form.value.lines = [];
                }
            } catch (err) {
                console.error("Error loading invoice details:", err);
                error.value = "Failed to load invoice details.";
                invoiceLines.value = [];
                form.value.lines = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Load return data for edit mode
        const loadReturn = async () => {
            if (!isEditMode.value) {
                // Generate a return number for new returns
                form.value.return_number = generateReturnNumber();
                return;
            }

            isLoading.value = true;
            error.value = "";

            try {
                const response = await SalesReturnService.getReturnById(
                    route.params.id
                );
                const salesReturn = response.data;

                // Set form data
                form.value = {
                    return_id: salesReturn.return_id,
                    return_number: salesReturn.return_number,
                    return_date: salesReturn.return_date.substr(0, 10),
                    customer_id: salesReturn.customer_id,
                    invoice_id: salesReturn.invoice_id,
                    return_reason: salesReturn.return_reason,
                    status: salesReturn.status,
                    lines: [],
                };

                // Load customer invoices
                await loadCustomerInvoices();

                // Load invoice lines
                if (salesReturn.invoice_id) {
                    await loadInvoiceLines();

                    // Set return line values from existing return
                    if (
                        salesReturn.salesReturnLines &&
                        salesReturn.salesReturnLines.length > 0
                    ) {
                        // Update form.lines with values from salesReturn.salesReturnLines
                        salesReturn.salesReturnLines.forEach((returnLine) => {
                            const lineIndex = form.value.lines.findIndex(
                                (line) =>
                                    line.invoice_line_id ===
                                    returnLine.invoice_line_id
                            );

                            if (lineIndex !== -1) {
                                form.value.lines[lineIndex].returned_quantity =
                                    returnLine.returned_quantity;
                                form.value.lines[lineIndex].condition =
                                    returnLine.condition;
                                form.value.lines[lineIndex].notes =
                                    returnLine.notes || "";
                            }
                        });
                    }
                }
            } catch (err) {
                console.error("Error loading return:", err);
                error.value = "Failed to load return data.";
            } finally {
                isLoading.value = false;
            }
        };

        // Format date
        const formatDate = (dateString) => {
            if (!dateString) return "-";
            const date = new Date(dateString);
            return date.toLocaleDateString("id-ID", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        };

        // Get UOM symbol
        const getUomSymbol = (uomId) => {
            if (!uomId) return "";
            const uom = unitOfMeasures.value.find((u) => u.uom_id === uomId);
            return uom ? uom.symbol : "";
        };

        // Navigation
        const goBack = () => {
            router.push("/sales/returns");
        };

        // Save return
        const saveReturn = async () => {
            // Check form validity
            if (!isFormValid.value) {
                error.value =
                    "Silakan lengkapi semua field yang diperlukan dan pastikan setidaknya satu item dikembalikan.";
                return;
            }

            isSubmitting.value = true;
            error.value = "";

            try {
                // Prepare return data - filter out lines with 0 returned quantity
                const returnData = {
                    ...form.value,
                    lines: form.value.lines
                        .filter(
                            (line) =>
                                line.returned_quantity > 0 && line.condition
                        )
                        .map((line) => ({
                            invoice_line_id: line.invoice_line_id,
                            returned_quantity: line.returned_quantity,
                            condition: line.condition,
                            notes: line.notes || "",
                        })),
                };

                if (isEditMode.value) {
                    // Update existing return
                    await SalesReturnService.updateReturn(
                        form.value.return_id,
                        returnData
                    );
                    alert("Pengembalian berhasil diperbarui!");
                } else {
                    // Create new return
                    await SalesReturnService.createReturn(returnData);
                    alert("Pengembalian berhasil dibuat!");
                }

                // Redirect to returns list
                router.push("/sales/returns");
            } catch (err) {
                console.error("Error saving return:", err);

                if (err.response?.data?.errors) {
                    const errors = err.response.data.errors;
                    const firstError = Object.values(errors)[0][0];
                    error.value = firstError;
                } else if (err.response?.data?.message) {
                    error.value = err.response.data.message;
                } else {
                    error.value =
                        "Terjadi kesalahan saat menyimpan pengembalian.";
                }
            } finally {
                isSubmitting.value = false;
            }
        };

        // Watch for invoice changes
        watch(
            () => form.value.invoice_id,
            (newVal, oldVal) => {
                if (newVal !== oldVal) {
                    loadInvoiceLines();
                }
            }
        );

        onMounted(async () => {
            await loadReferenceData();
            await loadReturn();
        });

        return {
            form,
            customers,
            invoices,
            invoiceLines,
            isLoading,
            isSubmitting,
            error,
            isEditMode,
            isFormValid,
            formatDate,
            getUomSymbol,
            loadCustomerInvoices,
            loadInvoiceLines,
            goBack,
            saveReturn,
        };
    },
};
</script>

<style scoped>
.return-form {
    padding: 1rem 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
    border: 1px solid var(--danger-light);
}

.form-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    background-color: var(--gray-50);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

.card-body {
    padding: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group input.readonly {
    background-color: var(--gray-50);
    cursor: not-allowed;
}

.text-muted {
    color: var(--gray-500);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.empty-lines {
    text-align: center;
    padding: 2rem;
    color: var(--gray-500);
    background-color: var(--gray-50);
    border: 1px dashed var(--gray-300);
    border-radius: 0.375rem;
}

.return-lines {
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    overflow: hidden;
}

.lines-header {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1.5fr 2fr;
    gap: 0.5rem;
    background-color: var(--gray-50);
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.header {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-600);
}

.return-line-item {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1.5fr 2fr;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    align-items: center;
}

.return-line-item:last-child {
    border-bottom: none;
}

.line-field input,
.line-field select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.item-name {
    font-weight: 500;
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.quantity-field {
    font-weight: 500;
}

.uom {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-left: 0.25rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
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

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-dark);
}

.btn-primary:disabled {
    background-color: var(--primary-light);
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

@media (max-width: 1024px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .return-line-item,
    .lines-header {
        grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
        gap: 0.25rem;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .return-line-item,
    .lines-header {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem;
    }

    .header {
        display: none;
    }

    .line-field {
        display: flex;
        align-items: center;
        width: 100%;
    }

    .line-field::before {
        content: attr(data-label);
        font-weight: 500;
        width: 8rem;
        text-align: left;
    }
}
</style>
