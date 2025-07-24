<!-- src/views/sales/SalesQuotationList.vue -->
<template>
    <div class="sales-quotations">
        <!-- Search and Filter Section -->
        <div class="page-actions">
            <div class="search-filter">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Cari penawaran..."
                        @input="handleSearch"
                    />
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        class="clear-search"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="filters">
                    <select v-model="statusFilter" @change="applyFilters">
                        <option value="">Semua Status</option>
                        <option value="Draft">Draft</option>
                        <option value="Sent">Terkirim</option>
                        <option value="Accepted">Diterima</option>
                        <option value="Rejected">Ditolak</option>
                        <option value="Expired">Kadaluarsa</option>
                        <option value="Converted">Dikonversi ke SO</option>
                    </select>

                    <select v-model="dateRangeFilter" @change="applyFilters">
                        <option value="all">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="week">Minggu Ini</option>
                        <option value="month">Bulan Ini</option>
                        <option value="custom">Kustom</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary" @click="openNewQuotationModal">
                <i class="fas fa-plus"></i> Buat Penawaran
            </button>
        </div>

        <!-- Custom Date Range (when Custom is selected) -->
        <div v-if="dateRangeFilter === 'custom'" class="custom-date-range">
            <div class="date-range-inputs">
                <div class="filter-group">
                    <label for="startDate">Tanggal Mulai</label>
                    <input
                        type="date"
                        id="startDate"
                        v-model="customDateRange.startDate"
                        @change="applyFilters"
                    />
                </div>

                <div class="filter-group">
                    <label for="endDate">Tanggal Akhir</label>
                    <input
                        type="date"
                        id="endDate"
                        v-model="customDateRange.endDate"
                        @change="applyFilters"
                    />
                </div>
            </div>
        </div>

        <!-- Quotations Grid -->
        <div class="quotations-container">
            <div v-if="isLoading" class="loading-indicator">
                <i class="fas fa-spinner fa-spin"></i> Memuat penawaran...
            </div>

            <div
                v-else-if="filteredQuotations.length === 0"
                class="empty-state"
            >
                <div class="empty-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h3>Tidak ada penawaran ditemukan</h3>
                <p>
                    Coba sesuaikan pencarian atau filter, atau buat penawaran
                    baru.
                </p>
            </div>

            <div v-else class="quotations-grid">
                <div
                    v-for="quotation in filteredQuotations"
                    :key="quotation.quotation_id"
                    class="quotation-card"
                >
                    <div class="quotation-header">
                        <div
                            class="quotation-status"
                            :class="getStatusClass(quotation.status)"
                        >
                            {{ getStatusLabel(quotation.status) }}
                        </div>
                        <div class="quotation-actions">
                            <button
                                class="action-btn"
                                title="Edit Penawaran"
                                @click.stop="editQuotation(quotation)"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            <button
                                class="action-btn"
                                title="Hapus Penawaran"
                                @click.stop="confirmDelete(quotation)"
                                v-if="canDelete(quotation)"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div
                        class="quotation-content"
                        @click="viewQuotation(quotation)"
                    >
                        <div class="quotation-id">
                            {{ quotation.quotation_number }}
                        </div>
                        <div class="quotation-customer">
                            {{ quotation.customer.name }}
                        </div>
                        <div class="quotation-dates">
                            <div class="date-item">
                                <i class="fas fa-calendar"></i>
                                <span>{{
                                    formatDate(quotation.quotation_date)
                                }}</span>
                            </div>
                            <div class="date-item">
                                <i class="fas fa-calendar-check"></i>
                                <span
                                    >Valid sampai:
                                    {{
                                        formatDate(quotation.validity_date)
                                    }}</span
                                >
                            </div>
                        </div>

                        <div
                            class="quotation-terms"
                            v-if="
                                quotation.payment_terms ||
                                quotation.delivery_terms
                            "
                        >
                            <div
                                v-if="quotation.payment_terms"
                                class="terms-item"
                            >
                                <strong>Syarat Pembayaran:</strong>
                                {{ quotation.payment_terms }}
                            </div>
                            <div
                                v-if="quotation.delivery_terms"
                                class="terms-item"
                            >
                                <strong>Syarat Pengiriman:</strong>
                                {{ quotation.delivery_terms }}
                            </div>
                        </div>
                    </div>

                    <div class="quotation-footer">
                        <button
                            v-if="quotation.status === 'Draft'"
                            class="btn btn-sm btn-secondary"
                            @click.stop="markAsSent(quotation)"
                        >
                            <i class="fas fa-paper-plane"></i> Kirim
                        </button>
                        <button
                            v-if="canConvert(quotation)"
                            class="btn btn-sm btn-primary"
                            @click.stop="convertToOrder(quotation)"
                        >
                            <i class="fas fa-file-invoice-dollar"></i> Konversi
                            ke SO
                        </button>
                        <button
                            v-if="quotation.status === 'Sent'"
                            class="btn btn-sm btn-success"
                            @click.stop="markAsAccepted(quotation)"
                        >
                            <i class="fas fa-check"></i> Terima
                        </button>
                        <button
                            v-if="quotation.status === 'Sent'"
                            class="btn btn-sm btn-danger"
                            @click.stop="markAsRejected(quotation)"
                        >
                            <i class="fas fa-times"></i> Tolak
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="filteredQuotations.length > 0" class="pagination">
            <div class="pagination-info">
                Menampilkan {{ paginationInfo.from }} -
                {{ paginationInfo.to }} dari
                {{ filteredQuotations.length }} penawaran
            </div>
            <div class="pagination-controls">
                <button
                    class="pagination-btn"
                    :disabled="currentPage === 1"
                    @click="goToPage(currentPage - 1)"
                >
                    <i class="fas fa-chevron-left"></i>
                </button>

                <template v-for="page in displayedPages" :key="page">
                    <button
                        v-if="page !== '...'"
                        class="pagination-btn"
                        :class="{ active: page === currentPage }"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </button>
                    <span v-else class="pagination-ellipsis">...</span>
                </template>

                <button
                    class="pagination-btn"
                    :disabled="currentPage === totalPages"
                    @click="goToPage(currentPage + 1)"
                >
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal">
            <div class="modal-backdrop" @click="closeDeleteModal"></div>
            <div class="modal-content modal-sm">
                <div class="modal-header">
                    <h2>Konfirmasi Hapus</h2>
                    <button class="close-btn" @click="closeDeleteModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>
                        Apakah Anda yakin ingin menghapus penawaran
                        <strong>{{ quotationToDelete.quotation_number }}</strong
                        >?
                    </p>
                    <p class="text-danger">
                        Tindakan ini tidak dapat dibatalkan.
                    </p>

                    <div class="form-actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="closeDeleteModal"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="deleteQuotation"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

export default {
    name: "SalesQuotationList",
    setup() {
        const router = useRouter();
        const quotations = ref([]);
        const isLoading = ref(true);

        // Search and filtering
        const searchQuery = ref("");
        const statusFilter = ref("");
        const dateRangeFilter = ref("month");
        const customDateRange = ref({
            startDate: new Date().toISOString().substr(0, 10),
            endDate: new Date().toISOString().substr(0, 10),
        });

        // Pagination
        const currentPage = ref(1);
        const itemsPerPage = ref(12);

        // Modals
        const showDeleteModal = ref(false);
        const quotationToDelete = ref({});

        // Fetch data
        const fetchQuotations = async () => {
            isLoading.value = true;
            try {
                const response = await axios.get("/sales/quotations");
                quotations.value = response.data.data;
            } catch (error) {
                console.error("Error fetching quotations:", error);
                const errorMsg =
                    error.response?.data?.message ||
                    "Terjadi kesalahan saat memuat data penawaran";
                alert(errorMsg);
            } finally {
                isLoading.value = false;
            }
        };

        // Computed properties
        const filteredQuotations = computed(() => {
            let result = [...quotations.value];

            // Apply search filter
            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                result = result.filter(
                    (quotation) =>
                        quotation.quotation_number
                            .toLowerCase()
                            .includes(query) ||
                        quotation.customer.name.toLowerCase().includes(query)
                );
            }

            // Apply status filter
            if (statusFilter.value) {
                result = result.filter(
                    (quotation) => quotation.status === statusFilter.value
                );
            }

            // Apply date range filter
            if (dateRangeFilter.value !== "all") {
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                const firstDayOfWeek = new Date(today);
                firstDayOfWeek.setDate(
                    firstDayOfWeek.getDate() - firstDayOfWeek.getDay()
                );

                const firstDayOfMonth = new Date(
                    today.getFullYear(),
                    today.getMonth(),
                    1
                );

                switch (dateRangeFilter.value) {
                    case "today": {
                        result = result.filter((quotation) => {
                            const date = new Date(quotation.quotation_date);
                            return (
                                date.setHours(0, 0, 0, 0) === today.getTime()
                            );
                        });
                        break;
                    }
                    case "week": {
                        result = result.filter((quotation) => {
                            const date = new Date(quotation.quotation_date);
                            return date >= firstDayOfWeek && date <= today;
                        });
                        break;
                    }

                    case "month": {
                        result = result.filter((quotation) => {
                            const date = new Date(quotation.quotation_date);
                            return date >= firstDayOfMonth && date <= today;
                        });
                        break;
                    }
                    case "custom": {
                        const startDate = new Date(
                            customDateRange.value.startDate
                        );
                        const endDate = new Date(customDateRange.value.endDate);
                        endDate.setHours(23, 59, 59, 999);

                        result = result.filter((quotation) => {
                            const date = new Date(quotation.quotation_date);
                            return date >= startDate && date <= endDate;
                        });
                        break;
                    }
                }
            }

            return result;
        });

        const totalPages = computed(() => {
            return Math.ceil(
                filteredQuotations.value.length / itemsPerPage.value
            );
        });

        const paginatedQuotations = computed(() => {
            const start = (currentPage.value - 1) * itemsPerPage.value;
            const end = start + itemsPerPage.value;
            return filteredQuotations.value.slice(start, end);
        });

        const paginationInfo = computed(() => {
            const total = filteredQuotations.value.length;
            const from =
                total === 0
                    ? 0
                    : (currentPage.value - 1) * itemsPerPage.value + 1;
            const to = Math.min(from + itemsPerPage.value - 1, total);

            return { from, to, total };
        });

        const displayedPages = computed(() => {
            const total = totalPages.value;
            const current = currentPage.value;
            const pages = [];

            if (total <= 7) {
                for (let i = 1; i <= total; i++) {
                    pages.push(i);
                }
            } else {
                pages.push(1);

                if (current > 3) {
                    pages.push("...");
                }

                const start = Math.max(2, current - 1);
                const end = Math.min(total - 1, current + 1);

                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }

                if (current < total - 2) {
                    pages.push("...");
                }

                pages.push(total);
            }

            return pages;
        });

        // Methods
        const formatDate = (dateString) => {
            if (!dateString) return "-";
            const date = new Date(dateString);
            return date.toLocaleDateString("id-ID", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        };

        const getStatusLabel = (status) => {
            switch (status) {
                case "Draft":
                    return "Draft";
                case "Sent":
                    return "Terkirim";
                case "Accepted":
                    return "Diterima";
                case "Rejected":
                    return "Ditolak";
                case "Expired":
                    return "Kadaluarsa";
                case "Converted":
                    return "Dikonversi ke SO";
                default:
                    return status;
            }
        };

        const getStatusClass = (status) => {
            switch (status) {
                case "Draft":
                    return "status-draft";
                case "Sent":
                    return "status-sent";
                case "Accepted":
                    return "status-accepted";
                case "Rejected":
                    return "status-rejected";
                case "Expired":
                    return "status-expired";
                case "Converted":
                    return "status-converted";
                default:
                    return "";
            }
        };

        const canDelete = (quotation) => {
            // Cannot delete if already converted to sales order
            return quotation.status !== "Converted";
        };

        const canConvert = (quotation) => {
            // Can only convert if accepted
            return quotation.status === "Accepted";
        };

        const handleSearch = () => {
            currentPage.value = 1;
        };

        const clearSearch = () => {
            searchQuery.value = "";
            handleSearch();
        };

        const applyFilters = () => {
            currentPage.value = 1;
        };

        const goToPage = (page) => {
            currentPage.value = page;
        };

        const openNewQuotationModal = () => {
            router.push("/sales/quotations/create");
        };

        const viewQuotation = (quotation) => {
            router.push(`/sales/quotations/${quotation.quotation_id}`);
        };

        const editQuotation = (quotation) => {
            router.push(`/sales/quotations/${quotation.quotation_id}/edit`);
        };

        const confirmDelete = (quotation) => {
            quotationToDelete.value = quotation;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteQuotation = async () => {
            try {
                await axios.delete(
                    `/sales/quotations/${quotationToDelete.value.quotation_id}`
                );

                // Remove from local state
                quotations.value = quotations.value.filter(
                    (q) =>
                        q.quotation_id !== quotationToDelete.value.quotation_id
                );

                closeDeleteModal();
                alert("Penawaran berhasil dihapus!");
            } catch (error) {
                console.error("Error deleting quotation:", error);
                const errorMsg =
                    error.response?.data?.message ||
                    "Terjadi kesalahan saat menghapus penawaran";
                alert(errorMsg);
            }
        };

        const markAsSent = async (quotation) => {
            try {
                await axios.put(`/sales/quotations/${quotation.quotation_id}`, {
                    ...quotation,
                    status: "Sent",
                });

                // Update local state
                const index = quotations.value.findIndex(
                    (q) => q.quotation_id === quotation.quotation_id
                );
                if (index !== -1) {
                    quotations.value[index].status = "Sent";
                }

                alert("Status penawaran berhasil diubah menjadi Terkirim!");
            } catch (error) {
                console.error("Error updating quotation:", error);
                alert("Terjadi kesalahan saat mengubah status penawaran.");
            }
        };

        const markAsAccepted = async (quotation) => {
            try {
                await axios.put(`/sales/quotations/${quotation.quotation_id}`, {
                    ...quotation,
                    status: "Accepted",
                });

                // Update local state
                const index = quotations.value.findIndex(
                    (q) => q.quotation_id === quotation.quotation_id
                );
                if (index !== -1) {
                    quotations.value[index].status = "Accepted";
                }

                alert("Status penawaran berhasil diubah menjadi Diterima!");
            } catch (error) {
                console.error("Error updating quotation:", error);
                alert("Terjadi kesalahan saat mengubah status penawaran.");
            }
        };

        const markAsRejected = async (quotation) => {
            try {
                await axios.put(`/sales/quotations/${quotation.quotation_id}`, {
                    ...quotation,
                    status: "Rejected",
                });

                // Update local state
                const index = quotations.value.findIndex(
                    (q) => q.quotation_id === quotation.quotation_id
                );
                if (index !== -1) {
                    quotations.value[index].status = "Rejected";
                }

                alert("Status penawaran berhasil diubah menjadi Ditolak!");
            } catch (error) {
                console.error("Error updating quotation:", error);
                const errorMsg =
                    error.response?.data?.message ||
                    "Terjadi kesalahan saat mengubah status penawaran";
                alert(errorMsg);
            }
        };

        const convertToOrder = (quotation) => {
            router.push(
                `/sales/orders/create-from-quotation/${quotation.quotation_id}`
            );
        };

        onMounted(() => {
            fetchQuotations();
        });

        return {
            quotations,
            filteredQuotations,
            paginatedQuotations,
            isLoading,
            searchQuery,
            statusFilter,
            dateRangeFilter,
            customDateRange,
            currentPage,
            totalPages,
            paginationInfo,
            displayedPages,
            showDeleteModal,
            quotationToDelete,
            handleSearch,
            clearSearch,
            applyFilters,
            goToPage,
            formatDate,
            getStatusLabel,
            getStatusClass,
            canDelete,
            canConvert,
            openNewQuotationModal,
            viewQuotation,
            editQuotation,
            confirmDelete,
            closeDeleteModal,
            deleteQuotation,
            markAsSent,
            markAsAccepted,
            markAsRejected,
            convertToOrder,
        };
    },
};
</script>

<style scoped>
.sales-quotations {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.search-filter {
    display: flex;
    flex-grow: 1;
    gap: 1rem;
    align-items: center;
}

.search-box {
    position: relative;
    width: 100%;
    max-width: 400px;
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.search-box input {
    width: 100%;
    padding: 0.625rem 2.5rem 0.625rem 2.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.clear-search {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
}

.filters {
    display: flex;
    gap: 1rem;
}

.filters select {
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
}

.custom-date-range {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.date-range-inputs {
    display: flex;
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.filter-group input {
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.quotations-container {
    width: 100%;
}

.quotations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.quotation-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.3s;
}

.quotation-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.quotation-header {
    padding: 0.75rem 1rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.quotation-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.status-draft {
    background-color: #e2e8f0;
    color: #475569;
}

.status-sent {
    background-color: #dbeafe;
    color: #2563eb;
}

.status-accepted {
    background-color: #d1fae5;
    color: #059669;
}

.status-rejected {
    background-color: #fee2e2;
    color: #dc2626;
}

.status-expired {
    background-color: #fef3c7;
    color: #d97706;
}

.status-converted {
    background-color: #ede9fe;
    color: #7c3aed;
}

.quotation-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s, color 0.2s;
}

.action-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}

.quotation-content {
    padding: 1.25rem;
    flex: 1;
    cursor: pointer;
}

.quotation-id {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1e293b;
}

.quotation-customer {
    font-size: 0.875rem;
    color: #334155;
    margin-bottom: 0.75rem;
}

.quotation-dates {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    margin-bottom: 0.75rem;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
}

.quotation-terms {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #64748b;
}

.quotation-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
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

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.btn-primary {
    background-color: #2563eb;
    color: white;
}

.btn-primary:hover {
    background-color: #1d4ed8;
}

.btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
}

.btn-secondary:hover {
    background-color: #cbd5e1;
}

.btn-success {
    background-color: #059669;
    color: white;
}

.btn-success:hover {
    background-color: #047857;
}

.btn-danger {
    background-color: #dc2626;
    color: white;
}

.btn-danger:hover {
    background-color: #b91c1c;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 0;
    text-align: center;
    color: #64748b;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #cbd5e1;
}

.empty-state h3 {
    font-size: 1.25rem;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
}

.empty-state p {
    margin: 0;
    font-size: 0.875rem;
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem 0;
    color: #64748b;
    font-size: 1rem;
}

.loading-indicator i {
    margin-right: 0.5rem;
}

.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
}

.pagination-info {
    color: #64748b;
    font-size: 0.875rem;
}

.pagination-controls {
    display: flex;
    gap: 0.25rem;
}

.pagination-btn {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    background: none;
    border: 1px solid #e2e8f0;
    color: #64748b;
    cursor: pointer;
}

.pagination-btn:hover:not(:disabled) {
    background-color: #f1f5f9;
    color: #0f172a;
}

.pagination-btn.active {
    background-color: #2563eb;
    color: white;
    border-color: #2563eb;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-ellipsis {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 2rem;
    height: 2rem;
    color: #64748b;
}

/* Modal styles */
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
    max-width: 600px;
    z-index: 60;
    overflow: hidden;
}

.modal-sm {
    max-width: 400px;
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

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
}

.text-danger {
    color: #dc2626;
}

@media (max-width: 768px) {
    .page-actions,
    .search-filter {
        flex-direction: column;
        align-items: stretch;
    }

    .date-range-inputs {
        flex-direction: column;
    }

    .quotations-grid {
        grid-template-columns: 1fr;
    }
}
</style>
