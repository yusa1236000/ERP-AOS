<!-- src/views/manufacturing/ProductionOrderPrint.vue -->
<template>
  <div class="print-report-container">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container no-print">
      <i class="fas fa-spinner fa-spin"></i>
      <span>Loading production order data...</span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container no-print">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>Error Loading Data</h3>
      <p>{{ error }}</p>
      <router-link to="/manufacturing/production-orders" class="btn btn-primary">
        Back to List
      </router-link>
    </div>

    <!-- Print Controls (hidden when printing) - Now Centered -->
    <div v-else class="print-controls no-print">
      <button @click="printDocument" class="btn btn-primary">
        <i class="fas fa-print"></i> Print Document
      </button>
      <button @click="printPdf" class="btn btn-danger ml-2">
        <i class="fas fa-file-pdf"></i> Save as PDF
      </button>
      <button @click="closeReport" class="btn btn-secondary ml-2">
        <i class="fas fa-times"></i> Close
      </button>
    </div>

    <!-- Report Content -->
    <div v-if="productionOrder" class="document-wrapper">
      <div id="printDocument" class="report-container" ref="printContent">
        <!-- Header Section -->
        <div class="header-section">
          <!-- <div class="header-title">
            Job Order Process - Material Request
          </div> -->

          <div class="main-content">
            <table class="info-table">
              <tr>
                <td class="info-cell">
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">JO No.</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ (productionOrder && productionOrder.production_number) || 'N/A' }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Intern Code</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ (productInfo && productInfo.internCode) || 'N/A' }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Cust Code</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ (customerInfo && customerInfo.customer_code) || 'N/A' }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Part Name</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ (productInfo && productInfo.partName) || 'N/A' }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Size</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ (productInfo && productInfo.size) || 'N/A' }}</span>
                  </div>
                </td>

                <td class="info-cell info-cell-center">
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Customer</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ (customerInfo && customerInfo.customer_name) || 'N/A' }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Issue WH</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ issueWarehouse || 'N/A' }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">JO Issue Date</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ formatDate(productionOrder && productionOrder.production_date) }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Print Date</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ formatDate(new Date()) }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Prod Qty</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ (productionOrder && productionOrder.planned_quantity) || 0 }} PCS</span>
                  </div>
                </td>

                <td class="info-cell">
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Prod Date</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ formatDate(productionOrder && productionOrder.production_date) }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">JOP Number</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ joProcessNumber || 'N/A' }}</span>
                  </div>
                  <div class="info-row info-row-table">
                    <span class="info-label info-label-table">Partial No.</span>
                    <span class="info-colon">:</span>
                    <span class="info-value info-value-table">{{ partialNumber || 'N/A' }}</span>
                  </div>
                </td>
              </tr>
            </table>
          </div>

          <!-- Material Table -->
          <div class="material-table-section">
            <table class="material-table">
              <thead>
                <tr>
                  <th style="width: 5%">No.</th>
                  <th style="width: 18%">Material Code</th>
                  <th style="width: 22%">Material Name</th>
                  <th style="width: 25%">Description</th>
                  <th style="width: 10%">UOM</th>
                  <th style="width: 10%">Planned Qty</th>
                  <th style="width: 10%">Actual Qty</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(material, index) in materialsToShow" :key="'material-' + index">
                  <td>{{ material && material.index || '' }}</td>
                  <td>{{ material && material.code || '' }}</td>
                  <td>{{ material && material.materialName || '' }}</td>
                  <td>
                    {{ material && material.description || '' }}<br>
                    <span v-if="material && material.partCode" class="part-code">{{ material.partCode }}</span>
                  </td>
                  <td>{{ material && material.uom || '' }}</td>
                  <td>{{ formatQuantity(material && material.plannedQty) }}</td>
                  <td>{{ formatQuantity(material && material.actualQty) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import html2pdf from "html2pdf.js";

export default {
  name: 'ProductionOrderPrint',
  props: {
    productionId: {
      type: [Number, String],
      required: true
    }
  },

  data() {
    return {
      productionOrder: null,
      workOrder: null,
      loading: true,
      error: null
    };
  },

  computed: {
    // More flexible approach to get customer info
    customerInfo() {
      try {
        // Try multiple possible paths for customer data
        const workOrder = this.workOrder || this.productionOrder?.workOrder || this.productionOrder?.work_order;
        const directCustomer = this.productionOrder?.customer;

        // Check work order related customer (prioritize this.workOrder since it's fetched separately)
        const woCustomer = workOrder?.primary_customer ||
                          workOrder?.primaryCustomer ||
                          workOrder?.customer;

        // Fallback to any customer data found
        const customer = woCustomer || directCustomer;

        return {
          customer_name: customer?.customer_name ||
                        customer?.name ||
                        this.productionOrder?.customer_name ||
                        '',
          customer_code: customer?.customer_code ||
                        customer?.code ||
                        this.productionOrder?.customer_code ||
                        ''
        };
      } catch (error) {
        console.warn('Error getting customer info:', error);
        return {
          customer_name: '',
          customer_code: ''
        };
      }
    },

    // More flexible approach to get product info
    productInfo() {
      try {
        const workOrder = this.workOrder ||
                         this.productionOrder?.workOrder ||
                         this.productionOrder?.work_order ||
                         this.productionOrder?.wo;

        const item = workOrder?.item ||
                    this.productionOrder?.item ||
                    this.productionOrder?.product;

        return {
          internCode: item?.item_code ||
                     item?.code ||
                     item?.product_code ||
                     '',
          partName: item?.name ||
                   item?.item_name ||
                   item?.product_name ||
                   '',
          materialName: item?.name ||
                       item?.item_name ||
                       item?.product_name ||
                       '',
          size: this.formatItemSize(item) ||
                item?.specifications ||
                item?.dimensions ||
                item?.size ||
                ''
        };
      } catch (error) {
        console.warn('Error getting product info:', error);
        return {
          internCode: '',
          partName: '',
          materialName: '',
          size: ''
        };
      }
    },

    issueWarehouse() {
      try {
        // Try to get warehouse from consumptions or default
        const consumptions = this.productionOrder?.productionConsumptions ||
                            this.productionOrder?.production_consumptions ||
                            this.productionOrder?.consumptions || [];

        const firstConsumption = consumptions[0];

        return firstConsumption?.warehouse?.name ||
               firstConsumption?.warehouse?.warehouse_name ||
               firstConsumption?.warehouse_name ||
               'Raw Materials';
      } catch (error) {
        console.warn('Error getting issue warehouse:', error);
        return 'Raw Materials';
      }
    },

    partialNumber() {
      try {
        // Generate partial number - could be based on production order sequence
        return this.productionOrder?.partial_number ||
               `P-${this.productionOrder?.production_number?.slice(-4) || '0001'}`;
      } catch (error) {
        console.warn('Error getting partial number:', error);
        return 'P-0001';
      }
    },

    joProcessNumber() {
      try {
        // Use work order number as JOP Number
        const workOrder = this.workOrder || this.productionOrder?.workOrder || this.productionOrder?.work_order;

        return workOrder?.wo_number ||
               this.productionOrder?.jo_process_number ||
               '';
      } catch (error) {
        console.warn('Error getting JO process number:', error);
        return '';
      }
    },

    materialsData() {
      try {
        // Use enhanced method that can fallback to BOM data
        const materials = this.getEnhancedMaterialsData();

        if (materials.length === 0) {
          console.warn('No materials data found from consumptions or BOM');
        }

        return materials;
      } catch (error) {
        console.error('Error in materialsData computed:', error);
        return [];
      }
    },

    materialsToShow() {
      try {
        // Return only actual materials data without padding with empty rows
        return this.materialsData || [];
      } catch (error) {
        console.error('Error in materialsToShow computed:', error);
        return [];
      }
    }
  },

  async created() {
    console.log('ProductionOrderPrint created with productionId:', this.productionId);
    await this.fetchProductionOrder();
  },

  methods: {
    async fetchProductionOrder() {
      this.loading = true;
      this.error = null;

      try {
        console.log('Fetching production order:', this.productionId);

        const response = await axios.get(`/manufacturing/production-orders/${this.productionId}`);
        console.log('API Response:', response.data);

        this.productionOrder = response.data.data || response.data;

        console.log('Production Order Data:', this.productionOrder);

        // If no consumptions, try to fetch them separately
        if (!this.getConsumptions().length) {
          console.log('No consumptions found, trying to fetch separately...');

          try {
            const consumptionResponse = await axios.get(`/manufacturing/production-orders/${this.productionId}/consumptions`);
            console.log('Consumption Response:', consumptionResponse.data);

            const consumptions = consumptionResponse.data.data || consumptionResponse.data;

            // Add consumptions to production order
            this.productionOrder.productionConsumptions = consumptions;

          } catch (consumptionError) {
            console.warn('Could not fetch consumptions:', consumptionError);
          }
        }

        console.log('Final Production Order:', this.productionOrder);
        console.log('Materials Data:', this.materialsData);

        // Fetch work order details if wo_id exists
        if (this.productionOrder?.wo_id || this.productionOrder?.work_order_id) {
          const workOrderId = this.productionOrder.wo_id || this.productionOrder.work_order_id;
          console.log('Fetching work order:', workOrderId);
          await this.fetchWorkOrder(workOrderId);
        } else {
          console.warn('No work order ID found in production order');
        }

      } catch (error) {
        console.error('Error fetching production order:', error);
        console.error('Error response:', error.response?.data);

        this.error = error.response?.data?.message ||
                    error.message ||
                    'Failed to load production order data';
      } finally {
        this.loading = false;
      }
    },

    async fetchWorkOrder(workOrderId) {
      try {
        console.log('Fetching work order details:', workOrderId);

        const response = await axios.get(`/manufacturing/work-orders/${workOrderId}`);
        this.workOrder = response.data.data || response.data;

      } catch (error) {
        console.error('Error fetching work order:', error);
        console.error('Work Order Error response:', error.response?.data);

        // Don't throw error here as work order is supplementary data
        console.warn('Failed to load work order details, continuing with production order data only');
      }
    },

    // Helper method to get consumptions from various possible paths
    getConsumptions() {
      try {
        return this.productionOrder?.productionConsumptions ||
               this.productionOrder?.production_consumptions ||
               this.productionOrder?.consumptions || [];
      } catch (error) {
        console.warn('Error getting consumptions:', error);
        return [];
      }
    },

    // Helper method to get BOM lines from work order if available
    getBOMLines() {
      try {
        const workOrder = this.workOrder || this.productionOrder?.workOrder;
        return workOrder?.bom?.bomLines ||
               workOrder?.bom?.bom_lines ||
               [];
      } catch (error) {
        console.warn('Error getting BOM lines:', error);
        return [];
      }
    },

    // Enhanced materials data that can fallback to BOM if no consumptions
    getEnhancedMaterialsData() {
      try {
        const consumptions = this.getConsumptions();

        // If we have consumption data, use it
        if (consumptions.length > 0) {
          return consumptions.map((consumption, index) => {
            const item = consumption.item || consumption.material || {};

            return {
              index: index + 1,
              code: item.item_code || item.code || item.material_code || '',
              materialName: item.name || item.item_name || item.material_name || '',
              description: item.description || item.desc || item.name || '',
              partCode: item.internal_code || item.part_code || item.part_no || '',
              uom: item.unitOfMeasure?.symbol ||
                   item.unit_of_measure?.symbol ||
                   item.uom?.symbol ||
                   item.uom ||
                   'PCS',
              plannedQty: consumption.planned_quantity || consumption.quantity || 0,
              actualQty: consumption.actual_quantity || consumption.used_quantity || 0,
              warehouseName: consumption.warehouse?.name ||
                            consumption.warehouse?.warehouse_name ||
                            consumption.warehouse_name ||
                            'N/A'
            };
          });
        }

        // Fallback to BOM lines if no consumption data
        const bomLines = this.getBOMLines();
        if (bomLines.length > 0) {
          return bomLines.map((bomLine, index) => {
            const item = bomLine.item || {};

            return {
              index: index + 1,
              code: item.item_code || item.code || '',
              materialName: item.name || item.item_name || '',
              description: item.description || item.name || '',
              partCode: item.internal_code || item.part_code || '',
              uom: item.unitOfMeasure?.symbol ||
                   item.unit_of_measure?.symbol ||
                   item.uom?.symbol ||
                   'PCS',
              plannedQty: bomLine.quantity || 0,
              actualQty: 0, // No actual quantity in BOM
              warehouseName: 'N/A'
            };
          });
        }

        return [];
      } catch (error) {
        console.warn('Error getting enhanced materials data:', error);
        return [];
      }
    },

    // Helper method to format item size from dimensions
    formatItemSize(item) {
      try {
        if (!item) return '';

        const length = item.length;
        const width = item.width;
        const thickness = item.thickness;

        if (length && width && thickness) {
          return `${thickness}MM x ${width}MM x ${length}MM`;
        } else if (length && width) {
          return `${width}MM x ${length}MM`;
        }

        return '';
      } catch (error) {
        console.warn('Error formatting item size:', error);
        return '';
      }
    },

    formatDate(date) {
      try {
        if (!date) return '';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '';

        const day = String(d.getDate()).padStart(2, '0');
        const year = String(d.getFullYear()).slice(-2);

        return `${day} ${this.getMonthName(d.getMonth())} ${year}`;
      } catch (error) {
        console.warn('Error formatting date:', error);
        return '';
      }
    },

    getMonthName(monthIndex) {
      try {
        const months = [
          'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        return months[monthIndex] || '';
      } catch (error) {
        console.warn('Error getting month name:', error);
        return '';
      }
    },

    formatQuantity(quantity) {
      try {
        if (!quantity && quantity !== 0) return '';

        const num = parseFloat(quantity);
        if (isNaN(num)) return '';

        return num.toLocaleString('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 8
        });
      } catch (error) {
        console.warn('Error formatting quantity:', error);
        return '';
      }
    },

    // Print document - Modified to use SalesOrderPrint.vue approach
    printDocument() {
      try {
        if (!this.productionOrder) {
          console.error('No production order data to print');
          return;
        }

        // Create a new window for printing
        const printWindow = window.open('', '_blank');

        // Get the current document content
        const documentElement = document.getElementById('printDocument');
        const documentHTML = documentElement.outerHTML;

        // Create the print HTML with styling
        const printHTML = `
          <!DOCTYPE html>
          <html>
          <head>
            <meta charset="utf-8">
            <title></title>
            <style>
              ${this.getPrintStyles()}
            </style>
          </head>
          <body>
            <div class="print-page">
              ${documentHTML}
            </div>
          </body>
          </html>
        `;

        // Write the HTML to the new window
        printWindow.document.write(printHTML);
        printWindow.document.close();

        // Wait for content to load, then print
        printWindow.onload = () => {
          setTimeout(() => {
            printWindow.print();
            printWindow.close();
          }, 500);
        };
      } catch (error) {
        console.error('Error printing document:', error);
        alert('Error printing document. Please try again.');
      }
    },

    // Print PDF - Modified to use html2pdf library like SalesOrderPrint.vue
    async printPdf() {
      try {
        if (this.loading) {
          console.warn("Data is still loading. Please wait before printing PDF.");
          return;
        }

        if (!this.productionOrder) {
          console.error('No production order data to save as PDF');
          return;
        }

        // Create a container for PDF generation
        const container = document.createElement('div');
        container.style.position = 'static';
        container.style.width = '21cm';
        container.style.minHeight = '9.9cm';
        container.style.backgroundColor = 'white';
        container.style.padding = '0';
        container.style.margin = '0';

        // Clone the document element
        const element = document.getElementById('printDocument');
        const clonedElement = element.cloneNode(true);

        // Remove any pagination controls if they exist
        const paginationControls = clonedElement.querySelector('.pagination-controls');
        if (paginationControls) {
          paginationControls.remove();
        }

        container.appendChild(clonedElement);
        document.body.appendChild(container);

        // PDF generation options
        const opt = {
          margin: 0,
          filename: `MaterialRequest_${this.productionOrder?.production_number || 'document'}.pdf`,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: {
            scale: 2,
            useCORS: true,
            letterRendering: true
          },
          jsPDF: {
            unit: 'cm',
            format: [21, 9.9],
            orientation: 'landscape'
          }
        };

        // Generate and save PDF
        await html2pdf().set(opt).from(container).save();

      } catch (error) {
        console.error("Error generating PDF:", error);
        alert("Error generating PDF. Please try again.");
      } finally {
        // Clean up - remove temporary container
        const containers = document.querySelectorAll('body > div');
        containers.forEach(container => {
          if (container.querySelector('#printDocument')) {
            document.body.removeChild(container);
          }
        });
      }
    },

    // Get print styles for both print and PDF
    getPrintStyles() {
      return `
        @page {
          size: 21cm 9.9cm landscape;
          margin: 15mm;
        }

        @media print {
          body {
            margin: 0;
            padding: 0;
          }
          .no-print {
            display: none !important;
          }

          /* Hide browser headers and footers */
          @page {
            margin: 0;
            size: 21cm 9.9cm landscape;
          }

          html, body {
            width: 21cm;
            height: 9.9cm;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden;
          }
        }

        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }

        body {
          font-family: Arial, sans-serif;
          font-size: 10px;
          line-height: 1.3;
          color: #000;
          background: white;
        }

        .print-page {
          width: 21cm;
          min-height: 9.9cm;
          margin: 0 auto;
          background: white;
          padding: 0;
          box-shadow: none;
        }

        .report-container {
          max-width: 100%;
          margin: 0 auto;
          background: white;
        }

        .header-section {
          padding: 15px;
        }

        .header-title {
          text-align: left;
          font-size: 14px;
          font-weight: bold;
          margin-bottom: 12px;
        }

        .main-content {
          margin-bottom: 12px;
        }

        .info-table {
          width: 100%;
          border-collapse: collapse;
          table-layout: fixed;
        }

        .info-cell {
          width: 33.33%;
          vertical-align: top;
          padding-right: 15px;
          border: none;
        }

        .info-cell:last-child {
          padding-right: 0;
        }

        .info-row {
          display: flex;
          margin-bottom: 6px;
          align-items: flex-end;
        }

        .info-label {
          font-weight: bold;
          min-width: 80px;
          display: inline-block;
          font-size: 9px;
        }

        .info-value {
          flex: 1;
          padding-left: 8px;
          min-height: 16px;
          font-size: 9px;
        }

        .info-row-table {
          display: table;
          width: 100%;
          margin-bottom: 6px;
          table-layout: fixed;
        }

        .info-label-table {
          display: table-cell;
          width: 80px;
          font-weight: bold;
          font-size: 11px;
          vertical-align: top;
          padding-right: 5px;
        }

        .info-colon {
          display: table-cell;
          width: 8px;
          font-size: 11px;
          vertical-align: top;
          text-align: left;
        }

        .info-value-table {
          display: table-cell;
          font-size: 11px;
          vertical-align: top;
          padding-left: 8px;
        }

        .material-table-section {
          margin-top: 8px;
        }

        .material-table {
          width: 100%;
          border-collapse: collapse;
          border: none;
          table-layout: fixed;
        }

        .material-table th,
        .material-table td {
          border: none;
          padding: 6px 4px;
          text-align: left;
          vertical-align: top;
          font-size: 11px;
          word-wrap: break-word;
        }

        .material-table th {
          font-weight: bold;
          text-align: center;
          font-size: 12px;
          border-top: 2px solid #000;
          border-bottom: 2px solid #000;
          padding: 6px 4px;
        }

        .material-table th:nth-child(1),
        .material-table td:nth-child(1) {
          width: 5%;
          text-align: center;
        }

        .material-table th:nth-child(2),
        .material-table td:nth-child(2) {
          width: 18%;
          text-align: left;
        }

        .material-table th:nth-child(3),
        .material-table td:nth-child(3) {
          width: 22%;
          text-align: left;
        }

        .material-table th:nth-child(4),
        .material-table td:nth-child(4) {
          width: 25%;
          text-align: left;
        }

        .material-table th:nth-child(5),
        .material-table td:nth-child(5) {
          width: 10%;
          text-align: center;
        }

        .material-table th:nth-child(6),
        .material-table td:nth-child(6) {
          width: 10%;
          text-align: right;
        }

        .material-table th:nth-child(7),
        .material-table td:nth-child(7) {
          width: 10%;
          text-align: right;
        }

        .part-code {
          font-size: 10px;
          color: #666;
        }
      `;
    },

    closeReport() {
      try {
        // Go back to detail view or list
        this.$router.push(`/manufacturing/production-orders/${this.productionId}`);
      } catch (error) {
        console.error('Error closing report:', error);
        // Fallback to production orders list
        this.$router.push('/manufacturing/production-orders');
      }
    }
  }
};
</script>

<style scoped>
/* Screen styles */
.print-report-container {
  min-height: 100vh;
  background-color: #f1f5f9;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
}

.loading-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.loading-container i,
.error-container i {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: #6c757d;
}

.error-container i {
  color: #dc3545;
}

.fa-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Centered print controls */
.print-controls {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  justify-content: center;
  width: 100%;
  max-width: 21cm;
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
  background-color: #059669;
  color: white;
}

.btn-primary:hover {
  background-color: #047857;
}

.btn-secondary {
  background-color: #e2e8f0;
  color: #1e293b;
}

.btn-secondary:hover {
  background-color: #cbd5e1;
}

.btn-danger {
  background-color: #ef4444;
  color: white;
}

.btn-danger:hover {
  background-color: #dc2626;
}

.ml-2 {
  margin-left: 0.5rem;
}

/* Document wrapper for A4 sizing - Landscape orientation */
.document-wrapper {
  width: 21cm;
  min-height: 9.9cm;
  margin: 0;
  background-color: white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
  position: relative;
  display: block;
}

.report-container {
  width: 100%;
  min-height: 9.9cm;
  background: white;
  box-sizing: border-box;
  position: relative;
}

.header-section {
  padding: 8px;
  height: 100%;
}

.header-title {
  text-align: left;
  font-size: 11px;
  font-weight: bold;
  margin-bottom: 6px;
}

.main-content {
  margin-bottom: 6px;
}

.info-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.info-cell {
  width: 33.33%;
  vertical-align: top;
  padding-right: 15px;
  border: none;
}

.info-cell:last-child {
  padding-right: 0;
}

.material-table-section {
  margin-top: 8px;
}

.info-row {
  display: flex;
  margin-bottom: 6px;
  align-items: flex-end;
}

.info-label {
  font-weight: bold;
  min-width: 80px;
  display: inline-block;
  font-size: 9px;
}

.info-value {
  flex: 1;
  padding-left: 8px;
  min-height: 16px;
  font-size: 9px;
}

.info-row-table {
  display: table;
  width: 100%;
  margin-bottom: 6px;
  table-layout: fixed;
}

.info-label-table {
  display: table-cell;
  width: 80px;
  font-weight: bold;
  font-size: 9px;
  vertical-align: top;
  padding-right: 5px;
}

.info-colon {
  display: table-cell;
  width: 8px;
  font-size: 9px;
  vertical-align: top;
  text-align: left;
}

.info-value-table {
  display: table-cell;
  font-size: 9px;
  vertical-align: top;
  padding-left: 8px;
}

.material-table {
  width: 100%;
  border-collapse: collapse;
  border: none;
  table-layout: fixed;
}

.material-table th,
.material-table td {
  border: none;
  padding: 3px 2px;
  text-align: left;
  vertical-align: top;
  font-size: 9px;
  word-wrap: break-word;
}

.material-table th {
  font-weight: bold;
  text-align: center;
  font-size: 10px;
  border-top: 2px solid #000;
  border-bottom: 2px solid #000;
  padding: 3px 2px;
}

.material-table th:nth-child(1),
.material-table td:nth-child(1) {
  width: 5%;
  text-align: center;
}

.material-table th:nth-child(2),
.material-table td:nth-child(2) {
  width: 18%;
  text-align: left;
}

.material-table th:nth-child(3),
.material-table td:nth-child(3) {
  width: 22%;
  text-align: left;
}

.material-table th:nth-child(4),
.material-table td:nth-child(4) {
  width: 25%;
  text-align: left;
}

.material-table th:nth-child(5),
.material-table td:nth-child(5) {
  width: 10%;
  text-align: center;
}

.material-table th:nth-child(6),
.material-table td:nth-child(6) {
  width: 10%;
  text-align: right;
}

.material-table th:nth-child(7),
.material-table td:nth-child(7) {
  width: 10%;
  text-align: right;
}

.part-code {
  font-size: 8px;
  color: #666;
}

/* Print styles */
@media print {
  .print-report-container {
    padding: 0;
    background: white;
  }

  .no-print {
    display: none !important;
  }

  .document-wrapper {
    box-shadow: none;
    max-width: none;
    height: auto;
  }
}

/* Mobile adjustments */
@media (max-width: 768px) {
  .print-controls {
    flex-direction: column;
    gap: 0.5rem;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }

  .document-wrapper {
    width: 100%;
  }

  .header-section {
    padding: 6px;
  }

  .info-label {
    min-width: 50px;
    font-size: 6px;
  }

  .info-label-table {
    width: 60px;
    font-size: 8px;
  }

  .info-colon {
    width: 6px;
    font-size: 8px;
  }

  .info-value-table {
    font-size: 8px;
  }

  .info-value {
    font-size: 6px;
    min-height: 10px;
  }

  .material-table th,
  .material-table td {
    padding: 2px 1px;
    font-size: 8px;
  }

  .material-table th {
    font-size: 9px;
  }
}
</style>
