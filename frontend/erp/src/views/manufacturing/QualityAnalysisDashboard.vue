<template>
  <div class="quality-analysis-dashboard">
    <div class="page-header">
      <h1>Dashboard Analisis Kualitas</h1>
      <div class="header-actions">
        <button @click="refreshData" class="btn-secondary" :disabled="loading">
          <span class="icon-refresh"></span> Refresh Data
        </button>
        <button @click="downloadReport" class="btn-primary">
          <span class="icon-download"></span> Download Laporan
        </button>
      </div>
    </div>

    <div class="filters-container">
      <div class="filters-card">
        <div class="filters-header">
          <h2>Filter Data</h2>
          <button @click="toggleFilters" class="btn-toggle">
            {{ showFilters ? 'Sembunyikan' : 'Tampilkan' }} Filter
          </button>
        </div>
        
        <div v-if="showFilters" class="filters-body">
          <div class="filter-row">
            <div class="filter-group">
              <label>Rentang Tanggal</label>
              <div class="date-range">
                <input
                  type="date"
                  v-model="filters.dateFrom"
                  placeholder="Dari Tanggal"
                />
                <span class="date-separator">sampai</span>
                <input
                  type="date"
                  v-model="filters.dateTo"
                  placeholder="Sampai Tanggal"
                />
              </div>
            </div>
            
            <div class="filter-group">
              <label>Item</label>
              <select v-model="filters.productId">
                <option value="">Semua Item</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.name }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="filter-row">
            <div class="filter-group">
              <label>Parameter</label>
              <select v-model="filters.parameterId">
                <option value="">Semua Parameter</option>
                <option v-for="param in parameters" :key="param.id" :value="param.id">
                  {{ param.name }}
                </option>
              </select>
            </div>
            
            <div class="filter-group">
              <label>Status</label>
              <select v-model="filters.status">
                <option value="">Semua Status</option>
                <option value="passed">Lulus</option>
                <option value="failed">Gagal</option>
                <option value="warning">Peringatan</option>
              </select>
            </div>
            
            <div class="filter-group">
              <label>Inspektor</label>
              <select v-model="filters.inspectorId">
                <option value="">Semua Inspektor</option>
                <option v-for="inspector in inspectors" :key="inspector.id" :value="inspector.id">
                  {{ inspector.name }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="filter-actions">
            <button @click="resetFilters" class="btn-text">Reset Filter</button>
            <button @click="applyFilters" class="btn-apply">Terapkan Filter</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Memuat data...</p>
    </div>

    <div class="dashboard-grid">
      <!-- KPI Cards -->
      <div class="kpi-cards">
        <div class="kpi-card" :class="getKpiColorClass(kpiData.passRate)">
          <div class="kpi-value">{{ formatPercentage(kpiData.passRate) }}</div>
          <div class="kpi-label">Tingkat Kelulusan</div>
          <div class="kpi-change" :class="kpiData.passRateChange >= 0 ? 'positive' : 'negative'">
            <span class="arrow">{{ kpiData.passRateChange >= 0 ? '▲' : '▼' }}</span> 
            {{ Math.abs(kpiData.passRateChange).toFixed(1) }}% dari periode sebelumnya
          </div>
        </div>
        
        <div class="kpi-card">
          <div class="kpi-value">{{ kpiData.totalInspections }}</div>
          <div class="kpi-label">Total Inspeksi</div>
          <div class="kpi-change" :class="kpiData.inspectionChange >= 0 ? 'positive' : 'negative'">
            <span class="arrow">{{ kpiData.inspectionChange >= 0 ? '▲' : '▼' }}</span> 
            {{ Math.abs(kpiData.inspectionChange) }} dari periode sebelumnya
          </div>
        </div>
        
        <div class="kpi-card">
          <div class="kpi-value">{{ kpiData.avgParameters.toFixed(1) }}</div>
          <div class="kpi-label">Rata-rata Parameter per Inspeksi</div>
        </div>
        
        <div class="kpi-card" :class="getKpiColorClass(100 - kpiData.criticalFailureRate)">
          <div class="kpi-value">{{ formatPercentage(kpiData.criticalFailureRate) }}</div>
          <div class="kpi-label">Tingkat Kegagalan Kritis</div>
          <div class="kpi-change" :class="kpiData.criticalFailureChange <= 0 ? 'positive' : 'negative'">
            <span class="arrow">{{ kpiData.criticalFailureChange <= 0 ? '▼' : '▲' }}</span> 
            {{ Math.abs(kpiData.criticalFailureChange).toFixed(1) }}% dari periode sebelumnya
          </div>
        </div>
      </div>

      <!-- Charts Row 1 -->
      <div class="charts-row">
        <div class="chart-card">
          <div class="chart-header">
            <h3>Tren Kelulusan Inspeksi</h3>
            <div class="chart-actions">
              <select v-model="timeGrouping" @change="updateTimeSeriesChart">
                <option value="day">Harian</option>
                <option value="week">Mingguan</option>
                <option value="month">Bulanan</option>
              </select>
            </div>
          </div>
          <div class="chart-body">
            <div id="passRateChart" class="chart-container"></div>
          </div>
        </div>
        
        <div class="chart-card">
          <div class="chart-header">
            <h3>Distribusi Status Parameter</h3>
          </div>
          <div class="chart-body">
            <div id="parameterStatusChart" class="chart-container"></div>
          </div>
        </div>
      </div>

      <!-- Charts Row 2 -->
      <div class="charts-row">
        <div class="chart-card">
          <div class="chart-header">
            <h3>Top 5 Parameter dengan Kegagalan Tertinggi</h3>
          </div>
          <div class="chart-body">
            <div id="topFailedParamsChart" class="chart-container"></div>
          </div>
        </div>
        
        <div class="chart-card">
          <div class="chart-header">
            <h3>Performa Item</h3>
            <div class="chart-actions">
              <select v-model="productMetric" @change="updateProductChart">
                <option value="passRate">Tingkat Kelulusan</option>
                <option value="inspectionCount">Jumlah Inspeksi</option>
                <option value="avgProcessingTime">Waktu Proses Rata-rata</option>
              </select>
            </div>
          </div>
          <div class="chart-body">
            <div id="productPerformanceChart" class="chart-container"></div>
          </div>
        </div>
      </div>

      <!-- Recent Inspections Table -->
      <div class="table-card">
        <div class="table-header">
          <h3>Inspeksi Terbaru</h3>
          <button @click="viewAllInspections" class="btn-text">Lihat Semua</button>
        </div>
        <div class="table-body">
          <table class="recent-inspections">
            <thead>
              <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Item</th>
                <th>Batch</th>
                <th>Status</th>
                <th>Parameter Lolos</th>
                <th>Parameter Gagal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inspection in recentInspections" :key="inspection.id">
                <td>{{ inspection.id }}</td>
                <td>{{ formatDate(inspection.inspection_date) }}</td>
                <td>{{ inspection.product_name }}</td>
                <td>{{ inspection.batch_number }}</td>
                <td>
                  <span class="status-badge" :class="inspection.status">
                    {{ getStatusText(inspection.status) }}
                  </span>
                </td>
                <td>{{ inspection.passed_parameters }}</td>
                <td>{{ inspection.failed_parameters }}</td>
                <td>
                  <button @click="viewInspection(inspection.id)" class="btn-view">Lihat</button>
                </td>
              </tr>
              <tr v-if="recentInspections.length === 0">
                <td colspan="8" class="no-data">Tidak ada data inspeksi terbaru</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Critical Parameters & Issues Section -->
      <div class="insights-row">
        <div class="insight-card">
          <div class="insight-header">
            <h3>Parameter Kritis</h3>
          </div>
          <div class="insight-body">
            <div v-if="criticalParameters.length === 0" class="no-data">
              Tidak ada parameter kritis yang ditemukan
            </div>
            <ul v-else class="critical-params-list">
              <li v-for="param in criticalParameters" :key="param.id" class="critical-param-item">
                <div class="critical-param-name">{{ param.name }}</div>
                <div class="critical-param-info">
                  <div class="critical-metric">
                    <span class="metric-label">Tingkat Kegagalan:</span>
                    <span class="metric-value">{{ formatPercentage(param.failure_rate) }}</span>
                  </div>
                  <div class="critical-metric">
                    <span class="metric-label">Produk:</span>
                    <span class="metric-value">{{ param.product_name }}</span>
                  </div>
                </div>
                <div class="critical-param-trend">
                  <span class="trend-arrow" :class="param.trend >= 0 ? 'negative' : 'positive'">
                    {{ param.trend >= 0 ? '▲' : '▼' }} {{ Math.abs(param.trend).toFixed(1) }}%
                  </span>
                  <button @click="viewParameterDetails(param.id)" class="btn-text">Detail</button>
                </div>
              </li>
            </ul>
          </div>
        </div>
        
        <div class="insight-card">
          <div class="insight-header">
            <h3>Masalah Teridentifikasi</h3>
          </div>
          <div class="insight-body">
            <div v-if="identifiedIssues.length === 0" class="no-data">
              Tidak ada masalah yang teridentifikasi
            </div>
            <ul v-else class="issues-list">
              <li v-for="(issue, index) in identifiedIssues" :key="index" class="issue-item">
                <div class="issue-icon" :class="getIssueSeverityClass(issue.severity)"></div>
                <div class="issue-content">
                  <div class="issue-title">{{ issue.title }}</div>
                  <div class="issue-description">{{ issue.description }}</div>
                  <div class="issue-meta">
                    <span class="issue-products">{{ issue.affected_products }} produk terpengaruh</span>
                    <span class="issue-date">Teridentifikasi: {{ formatDate(issue.identified_date) }}</span>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Report Modal -->
    <div v-if="showExportModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Ekspor Laporan Analisis Kualitas</h3>
          <button @click="showExportModal = false" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Format Laporan</label>
            <div class="report-format-options">
              <label class="format-option">
                <input type="radio" v-model="exportFormat" value="pdf">
                <span class="format-icon pdf">PDF</span>
                <span class="format-label">PDF Document</span>
              </label>
              <label class="format-option">
                <input type="radio" v-model="exportFormat" value="excel">
                <span class="format-icon excel">XLS</span>
                <span class="format-label">Excel Spreadsheet</span>
              </label>
              <label class="format-option">
                <input type="radio" v-model="exportFormat" value="csv">
                <span class="format-icon csv">CSV</span>
                <span class="format-label">CSV File</span>
              </label>
            </div>
          </div>
          
          <div class="form-group">
            <label>Konten Laporan</label>
            <div class="report-content-options">
              <label class="checkbox-label">
                <input type="checkbox" v-model="exportOptions.includeKpis">
                KPI dan Metrik Utama
              </label>
              <label class="checkbox-label">
                <input type="checkbox" v-model="exportOptions.includeCharts">
                Grafik dan Visualisasi
              </label>
              <label class="checkbox-label">
                <input type="checkbox" v-model="exportOptions.includeDetails">
                Detail Inspeksi Lengkap
              </label>
              <label class="checkbox-label">
                <input type="checkbox" v-model="exportOptions.includeIssues">
                Masalah dan Rekomendasi
              </label>
            </div>
          </div>
          
          <div class="form-group">
            <label>Rentang Tanggal Laporan</label>
            <div class="date-range">
              <input
                type="date"
                v-model="exportDateFrom"
                placeholder="Dari Tanggal"
              />
              <span class="date-separator">sampai</span>
              <input
                type="date"
                v-model="exportDateTo"
                placeholder="Sampai Tanggal"
              />
            </div>
          </div>
          
          <div class="modal-actions">
            <button @click="showExportModal = false" class="btn-secondary">Batal</button>
            <button @click="generateReport" class="btn-primary" :disabled="exportLoading">
              <span v-if="exportLoading">Memproses...</span>
              <span v-else>Generate Laporan</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Chart from 'chart.js/auto';

export default {
  name: 'QualityAnalysisDashboard',
  data() {
    return {
      loading: true,
      error: null,
      
      // Dashboard data
      kpiData: {
        passRate: 0,
        passRateChange: 0,
        totalInspections: 0,
        inspectionChange: 0,
        avgParameters: 0,
        criticalFailureRate: 0,
        criticalFailureChange: 0
      },
      
      // Chart data
      timeSeriesData: [],
      parameterStatusData: [],
      topFailedParamsData: [],
      productPerformanceData: [],
      
      // Table data
      recentInspections: [],
      
      // Insight data
      criticalParameters: [],
      identifiedIssues: [],
      
      // Filter options
      filters: {
        dateFrom: this.getDefaultDateFrom(),
        dateTo: new Date().toISOString().split('T')[0],
        productId: '',
        parameterId: '',
        status: '',
        inspectorId: ''
      },
      
      // Filter reference data
      products: [],
      parameters: [],
      inspectors: [],
      
      // UI state
      showFilters: true,
      
      // Chart configuration
      charts: {
        passRateChart: null,
        parameterStatusChart: null,
        topFailedParamsChart: null,
        productPerformanceChart: null
      },
      timeGrouping: 'week',
      productMetric: 'passRate',
      
      // Export modal
      showExportModal: false,
      exportFormat: 'pdf',
      exportOptions: {
        includeKpis: true,
        includeCharts: true,
        includeDetails: false,
        includeIssues: true
      },
      exportDateFrom: '',
      exportDateTo: '',
      exportLoading: false
    };
  },
  created() {
    // Set export date range same as filters by default
    this.exportDateFrom = this.filters.dateFrom;
    this.exportDateTo = this.filters.dateTo;
  },
  mounted() {
    this.fetchReferenceData();
    this.fetchDashboardData();
  },
  methods: {
    getDefaultDateFrom() {
      const date = new Date();
      date.setMonth(date.getMonth() - 1);
      return date.toISOString().split('T')[0];
    },
    
    async fetchReferenceData() {
      try {
        // Load items bukan products
        const itemsResponse = await axios.get('/manufacturing/quality-parameters/items');
        this.products = itemsResponse.data;
        
        // Load parameters
        const parametersResponse = await axios.get('/manufacturing/quality-parameters');
        this.parameters = parametersResponse.data;
        
        // Load inspectors
        const inspectorsResponse = await axios.get('/manufacturing/quality-inspections/inspectors');
        this.inspectors = inspectorsResponse.data;
      } catch (err) {
        console.error('Failed to load reference data:', err);
      }
    },
    
    async fetchDashboardData() {
      this.loading = true;
      
      try {
        const response = await axios.get('/manufacturing/quality-analysis/dashboard', {
          params: this.filters
        });
        
        // Update KPI data
        this.kpiData = response.data.kpis;
        
        // Update chart data
        this.timeSeriesData = response.data.charts.timeSeries;
        this.parameterStatusData = response.data.charts.parameterStatus;
        this.topFailedParamsData = response.data.charts.topFailedParams;
        this.productPerformanceData = response.data.charts.productPerformance;
        
        // Update table data
        this.recentInspections = response.data.recentInspections;
        
        // Update insight data
        this.criticalParameters = response.data.criticalParameters;
        this.identifiedIssues = response.data.identifiedIssues;
        
        this.loading = false;
        
        // Initialize charts after data is loaded
        this.$nextTick(() => {
          this.initCharts();
        });
      } catch (err) {
        this.error = 'Gagal memuat data dashboard: ' + (err.response?.data?.message || err.message);
        this.loading = false;
      }
    },
    
    initCharts() {
      this.initPassRateChart();
      this.initParameterStatusChart();
      this.initTopFailedParamsChart();
      this.initProductPerformanceChart();
    },
    
    initPassRateChart() {
      const ctx = document.getElementById('passRateChart');
      
      if (this.charts.passRateChart) {
        this.charts.passRateChart.destroy();
      }
      
      const data = this.processTimeSeriesData(this.timeSeriesData, this.timeGrouping);
      
      this.charts.passRateChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.labels,
          datasets: [
            {
              label: 'Tingkat Kelulusan (%)',
              data: data.passRates,
              borderColor: '#4caf50',
              backgroundColor: 'rgba(76, 175, 80, 0.1)',
              fill: true,
              tension: 0.3
            },
            {
              label: 'Jumlah Inspeksi',
              data: data.inspectionCounts,
              borderColor: '#2196f3',
              backgroundColor: 'rgba(33, 150, 243, 0.1)',
              fill: true,
              tension: 0.2,
              yAxisID: 'y1'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            tooltip: {
              mode: 'index',
              intersect: false
            },
            legend: {
              position: 'top'
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Tingkat Kelulusan (%)'
              },
              max: 100
            },
            y1: {
              position: 'right',
              beginAtZero: true,
              title: {
                display: true,
                text: 'Jumlah Inspeksi'
              },
              grid: {
                drawOnChartArea: false
              }
            }
          }
        }
      });
    },
    
    initParameterStatusChart() {
      const ctx = document.getElementById('parameterStatusChart');
      
      if (this.charts.parameterStatusChart) {
        this.charts.parameterStatusChart.destroy();
      }
      
      this.charts.parameterStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Lulus', 'Gagal', 'Peringatan'],
          datasets: [{
            data: [
              this.parameterStatusData.passed || 0,
              this.parameterStatusData.failed || 0,
              this.parameterStatusData.warning || 0
            ],
            backgroundColor: [
              '#4caf50',
              '#f44336',
              '#ff9800'
            ],
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw || 0;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = Math.round((value / total) * 100);
                  return `${label}: ${value} (${percentage}%)`;
                }
              }
            }
          }
        }
      });
    },
    
    initTopFailedParamsChart() {
      const ctx = document.getElementById('topFailedParamsChart');
      
      if (this.charts.topFailedParamsChart) {
        this.charts.topFailedParamsChart.destroy();
      }
      
      const labels = this.topFailedParamsData.map(item => item.name);
      const failureRates = this.topFailedParamsData.map(item => item.failure_rate);
      
      this.charts.topFailedParamsChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Tingkat Kegagalan (%)',
            data: failureRates,
            backgroundColor: failureRates.map(rate => {
              if (rate > 50) return 'rgba(244, 67, 54, 0.7)';
              if (rate > 25) return 'rgba(255, 152, 0, 0.7)';
              return 'rgba(255, 193, 7, 0.7)';
            }),
            borderColor: failureRates.map(rate => {
              if (rate > 50) return 'rgb(244, 67, 54)';
              if (rate > 25) return 'rgb(255, 152, 0)';
              return 'rgb(255, 193, 7)';
            }),
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y',
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            x: {
              beginAtZero: true,
              max: 100,
              title: {
                display: true,
                text: 'Tingkat Kegagalan (%)'
              }
            }
          }
        }
      });
    },
    
    initProductPerformanceChart() {
      const ctx = document.getElementById('productPerformanceChart');
      
      if (this.charts.productPerformanceChart) {
        this.charts.productPerformanceChart.destroy();
      }
      
      const data = this.processProductData(this.productPerformanceData, this.productMetric);
      
      this.charts.productPerformanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.labels,
          datasets: [{
            label: data.metricLabel,
            data: data.values,
            backgroundColor: 'rgba(33, 150, 243, 0.7)',
            borderColor: 'rgb(33, 150, 243)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            tooltip: {
              callbacks: {
                label: (context) => {
                  let label = context.dataset.label || '';
                  if (label) {
                    label += ': ';
                  }
                  if (this.productMetric === 'passRate') {
                    label += this.formatPercentage(context.raw);
                  } else if (this.productMetric === 'avgProcessingTime') {
                    label += context.raw.toFixed(1) + ' menit';
                  } else {
                    label += context.raw;
                  }
                  return label;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: data.metricLabel
              }
            }
          }
        }
      });
    },
    
    processTimeSeriesData(data, grouping) {
      const labels = [];
      const passRates = [];
      const inspectionCounts = [];
      
      // Group data by the selected time unit
      data.forEach(item => {
        let label;
        if (grouping === 'day') {
          label = this.formatDate(item.date);
        } else if (grouping === 'week') {
          label = `Minggu ${item.week}, ${item.year}`;
        } else if (grouping === 'month') {
          const date = new Date(item.year, item.month - 1, 1);
          label = date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        }
        
        labels.push(label);
        passRates.push(item.pass_rate);
        inspectionCounts.push(item.inspection_count);
      });
      
      return { labels, passRates, inspectionCounts };
    },
    
    processProductData(data, metric) {
      const labels = data.map(item => item.name);
      let values = [];
      let metricLabel = '';
      
      if (metric === 'passRate') {
        values = data.map(item => item.pass_rate);
        metricLabel = 'Tingkat Kelulusan (%)';
      } else if (metric === 'inspectionCount') {
        values = data.map(item => item.inspection_count);
        metricLabel = 'Jumlah Inspeksi';
      } else if (metric === 'avgProcessingTime') {
        values = data.map(item => item.avg_processing_time);
        metricLabel = 'Waktu Proses Rata-rata (menit)';
      }
      
      return { labels, values, metricLabel };
    },
    
    updateTimeSeriesChart() {
      this.initPassRateChart();
    },
    
    updateProductChart() {
      this.initProductPerformanceChart();
    },
    
    formatDate(dateString) {
      if (!dateString) return '-';
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    
    formatPercentage(value) {
      if (value === undefined || value === null) return '-';
      return value.toFixed(1) + '%';
    },
    
    getStatusText(status) {
      const statusMap = {
        'pending': 'Menunggu',
        'completed': 'Selesai',
        'failed': 'Gagal',
        'passed': 'Lulus'
      };
      return statusMap[status] || status;
    },
    
    getKpiColorClass(value) {
      if (value >= 90) return 'success';
      if (value >= 70) return 'warning';
      return 'danger';
    },
    
    getIssueSeverityClass(severity) {
      if (severity === 'high') return 'high-severity';
      if (severity === 'medium') return 'medium-severity';
      return 'low-severity';
    },
    
    toggleFilters() {
      this.showFilters = !this.showFilters;
    },
    
    resetFilters() {
      this.filters = {
        dateFrom: this.getDefaultDateFrom(),
        dateTo: new Date().toISOString().split('T')[0],
        productId: '',
        parameterId: '',
        status: '',
        inspectorId: ''
      };
    },
    
    applyFilters() {
      this.fetchDashboardData();
    },
    
    refreshData() {
      this.fetchDashboardData();
    },
    
    viewInspection(id) {
      this.$router.push(`/quality-inspections/${id}`);
    },
    
    viewAllInspections() {
      this.$router.push('/quality-inspections');
    },
    
    viewParameterDetails(id) {
      this.$router.push(`/quality-parameters/${id}`);
    },
    
    downloadReport() {
      this.exportDateFrom = this.filters.dateFrom;
      this.exportDateTo = this.filters.dateTo;
      this.showExportModal = true;
    },
    
    async generateReport() {
      this.exportLoading = true;
      
      try {
        const response = await axios.post('/manufacturing/quality-analysis/export-report', {
          format: this.exportFormat,
          options: this.exportOptions,
          dateFrom: this.exportDateFrom,
          dateTo: this.exportDateTo,
          filters: this.filters
        }, {
          responseType: 'blob'
        });
        
        // Create a URL for the blob data
        const blob = new Blob([response.data], { 
          type: this.getContentTypeForFormat(this.exportFormat)
        });
        const url = URL.createObjectURL(blob);
        
        // Create download link and trigger click
        const link = document.createElement('a');
        link.href = url;
        link.download = `quality-analysis-report-${new Date().toISOString().split('T')[0]}.${this.exportFormat}`;
        link.click();
        
        // Clean up
        URL.revokeObjectURL(url);
        this.showExportModal = false;
        this.exportLoading = false;
      } catch (err) {
        alert('Gagal membuat laporan: ' + (err.response?.data?.message || err.message));
        this.exportLoading = false;
      }
    },
    
    getContentTypeForFormat(format) {
      if (format === 'pdf') {
        return 'application/pdf';
      } else if (format === 'excel') {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      } else if (format === 'csv') {
        return 'text/csv';
      }
      return 'application/octet-stream';
    }
  }
};
</script>

<style scoped>
.quality-analysis-dashboard {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 15px;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.filters-container {
  margin-bottom: 25px;
}

.filters-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background-color: #f5f5f5;
  border-bottom: 1px solid #e0e0e0;
}

.filters-header h2 {
  margin: 0;
  font-size: 1.1rem;
  color: #333;
}

.filters-body {
  padding: 20px;
}

.filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 20px;
}

.filter-group {
  flex: 1;
  min-width: 250px;
}

.filter-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #555;
}

.filter-group select,
.filter-group input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.95rem;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 10px;
}

.date-separator {
  color: #666;
}

.filter-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.dashboard-grid {
  display: grid;
  gap: 25px;
}

.kpi-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.kpi-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  padding: 20px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.kpi-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 5px;
  background-color: #9e9e9e;
}

.kpi-card.success::before {
  background-color: #4caf50;
}

.kpi-card.warning::before {
  background-color: #ff9800;
}

.kpi-card.danger::before {
  background-color: #f44336;
}

.kpi-value {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 5px;
  color: #333;
}

.kpi-label {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 10px;
}

.kpi-change {
  font-size: 0.8rem;
  padding: 5px 10px;
  border-radius: 15px;
  display: inline-block;
  background-color: #f5f5f5;
}

.kpi-change.positive {
  color: #388e3c;
}

.kpi-change.negative {
  color: #d32f2f;
}

.arrow {
  font-size: 0.9rem;
  margin-right: 3px;
}

.charts-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: 25px;
}

.chart-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background-color: #f5f5f5;
  border-bottom: 1px solid #e0e0e0;
}

.chart-header h3 {
  margin: 0;
  font-size: 1rem;
  color: #333;
}

.chart-actions {
  display: flex;
  gap: 10px;
}

.chart-actions select {
  padding: 5px 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
}

.chart-body {
  padding: 20px;
  height: 300px;
}

.chart-container {
  width: 100%;
  height: 100%;
}

.table-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background-color: #f5f5f5;
  border-bottom: 1px solid #e0e0e0;
}

.table-header h3 {
  margin: 0;
  font-size: 1rem;
  color: #333;
}

.table-body {
  padding: 0;
  overflow-x: auto;
}

.recent-inspections {
  width: 100%;
  border-collapse: collapse;
}

.recent-inspections th {
  background-color: #f9f9f9;
  padding: 12px 15px;
  text-align: left;
  font-weight: 500;
  color: #333;
  white-space: nowrap;
}

.recent-inspections td {
  padding: 12px 15px;
  border-top: 1px solid #e0e0e0;
}

.recent-inspections tr:hover td {
  background-color: #f5f5f5;
}

.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-badge.pending {
  background-color: #fff8e1;
  color: #ffa000;
}

.status-badge.completed, .status-badge.passed {
  background-color: #e8f5e9;
  color: #388e3c;
}

.status-badge.failed {
  background-color: #ffebee;
  color: #d32f2f;
}

.btn-view {
  background-color: #2196f3;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.85rem;
}

.insights-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 25px;
}

.insight-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.insight-header {
  padding: 15px 20px;
  background-color: #f5f5f5;
  border-bottom: 1px solid #e0e0e0;
}

.insight-header h3 {
  margin: 0;
  font-size: 1rem;
  color: #333;
}

.insight-body {
  padding: 20px;
  max-height: 400px;
  overflow-y: auto;
}

.critical-params-list, .issues-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.critical-param-item {
  padding: 15px;
  border-bottom: 1px solid #e0e0e0;
}

.critical-param-item:last-child {
  border-bottom: none;
}

.critical-param-name {
  font-weight: 500;
  margin-bottom: 10px;
}

.critical-param-info {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 10px;
}

.critical-metric {
  font-size: 0.9rem;
}

.metric-label {
  color: #666;
  margin-right: 5px;
}

.critical-param-trend {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.trend-arrow {
  font-size: 0.85rem;
  padding: 3px 8px;
  border-radius: 10px;
  background-color: #f5f5f5;
}

.trend-arrow.positive {
  color: #388e3c;
}

.trend-arrow.negative {
  color: #d32f2f;
}

.issue-item {
  display: flex;
  align-items: flex-start;
  padding: 15px;
  border-bottom: 1px solid #e0e0e0;
}

.issue-item:last-child {
  border-bottom: none;
}

.issue-icon {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  margin-top: 5px;
  margin-right: 15px;
  flex-shrink: 0;
}

.issue-icon.high-severity {
  background-color: #f44336;
}

.issue-icon.medium-severity {
  background-color: #ff9800;
}

.issue-icon.low-severity {
  background-color: #ffc107;
}

.issue-content {
  flex-grow: 1;
}

.issue-title {
  font-weight: 500;
  margin-bottom: 5px;
}

.issue-description {
  font-size: 0.9rem;
  color: #555;
  margin-bottom: 10px;
}

.issue-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #666;
}

.btn-primary {
  background-color: #4caf50;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-secondary {
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
  padding: 10px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-text {
  background: none;
  border: none;
  color: #2196f3;
  cursor: pointer;
  padding: 5px;
  font-size: 0.9rem;
}

.btn-toggle {
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-apply {
  background-color: #2196f3;
  color: white;
  border: none;
  padding: 8px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.icon-refresh, .icon-download {
  display: inline-block;
  width: 16px;
  height: 16px;
  background-repeat: no-repeat;
  background-position: center;
}

.icon-refresh {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M23 4v6h-6'/%3E%3Cpath d='M1 20v-6h6'/%3E%3Cpath d='M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15'/%3E%3C/svg%3E");
}

.icon-download {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/%3E%3Cpolyline points='7 10 12 15 17 10'/%3E%3Cline x1='12' y1='15' x2='12' y2='3'/%3E%3C/svg%3E");
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(255, 255, 255, 0.7);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.loading-spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #4caf50;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.no-data {
  text-align: center;
  padding: 30px;
  color: #666;
  font-style: italic;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  width: 600px;
  max-width: 90%;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.modal-header {
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #f9f9f9;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.2rem;
}

.modal-body {
  padding: 20px;
}

.modal-body .form-group {
  margin-bottom: 20px;
}

.modal-body label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #555;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  padding: 0;
  cursor: pointer;
  color: #666;
}

.report-format-options {
  display: flex;
  gap: 15px;
  margin-top: 10px;
}

.format-option {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 15px;
  border: 1px solid #ddd;
  border-radius: 5px;
  cursor: pointer;
  transition: all 0.2s;
}

.format-option:hover {
  background-color: #f9f9f9;
}

.format-option input[type="radio"] {
  margin-bottom: 10px;
}

.format-icon {
  width: 40px;
  height: 40px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 5px;
  margin-bottom: 10px;
  font-weight: bold;
  font-size: 0.9rem;
}

.format-icon.pdf {
  background-color: #f44336;
  color: white;
}

.format-icon.excel {
  background-color: #4caf50;
  color: white;
}

.format-icon.csv {
  background-color: #2196f3;
  color: white;
}

.format-label {
  font-size: 0.9rem;
  color: #555;
}

.report-content-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
  margin-top: 10px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  margin-right: 8px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 25px;
}

@media (max-width: 768px) {
  .charts-row {
    grid-template-columns: 1fr;
  }
  
  .filter-group {
    min-width: 100%;
  }
  
  .date-range {
    flex-direction: column;
    align-items: stretch;
  }
  
  .format-option {
    padding: 10px;
  }
  
  .format-icon {
    width: 30px;
    height: 30px;
    font-size: 0.8rem;
  }
  
  .report-content-options {
    grid-template-columns: 1fr;
  }
}
</style>