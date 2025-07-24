<template>
  <div class="quality-parameter-form">
  <div class="page-header">
  <h1>{{ isEditMode ? 'Edit Parameter Kualitas' : 'Tambah Parameter Kualitas Baru' }}</h1>
  </div>
  
  <div v-if="loading" class="loading">
  <p>Memuat data...</p>
  </div>
  <div v-else-if="error" class="error-message">
  <p>{{ error }}</p>
  <button @click="isEditMode ? fetchParameterData() : resetForm()">Coba Lagi</button>
  </div>
  <div v-else>
  <form @submit.prevent="saveParameter" class="parameter-form">
  <div class="form-section">
  <div class="form-row">
  <div class="form-group">
  <label for="name">Nama Parameter</label>
  <input
    id="name"
    v-model="form.name"
    type="text"
    required
    :class="{ 'error-input': errors.name }"
  />
  <span v-if="errors.name" class="error-text">{{ errors.name }}</span>
  </div>
  <div class="form-group">
  <label for="type">Tipe Parameter</label>
  <select
    id="type"
    v-model="form.type"
    required
    @change="handleTypeChange"
    :class="{ 'error-input': errors.type }"
  >
    <option value="numeric">Numerik</option>
    <option value="boolean">Boolean (Ya/Tidak)</option>
    <option value="text">Teks</option>
    <option value="enum">Enum (Pilihan)</option>
  </select>
  <span v-if="errors.type" class="error-text">{{ errors.type }}</span>
  </div>
  </div>
  
  <div class="form-row">
  <div class="form-group">
  <label for="category">Kategori</label>
  <select
    id="category"
    v-model="form.category_id"
    :class="{ 'error-input': errors.category_id }"
  >
    <option value="">-- Pilih Kategori --</option>
    <option v-for="category in categories" :key="category.id" :value="category.id">
      {{ category.name }}
    </option>
  </select>
  <span v-if="errors.category_id" class="error-text">{{ errors.category_id }}</span>
  </div>
  <div class="form-group">
  <label for="order">Urutan</label>
  <input
    id="order"
    v-model.number="form.order"
    type="number"
    min="1"
    :class="{ 'error-input': errors.order }"
  />
  <span v-if="errors.order" class="error-text">{{ errors.order }}</span>
  </div>
  </div>
  
  <div class="form-group full-width">
  <label for="description">Deskripsi</label>
  <textarea
  id="description"
  v-model="form.description"
  rows="3"
  :class="{ 'error-input': errors.description }"
  ></textarea>
  <span v-if="errors.description" class="error-text">{{ errors.description }}</span>
  </div>
  
  <!-- Numeric Type Options -->
  <div v-if="form.type === 'numeric'" class="parameter-type-options">
  <h3>Konfigurasi Parameter Numerik</h3>
  
  <div class="form-row">
  <div class="form-group">
    <label for="min_value">Nilai Minimum</label>
    <input
      id="min_value"
      v-model.number="form.min_value"
      type="number"
      step="0.01"
      :class="{ 'error-input': errors.min_value }"
    />
    <span v-if="errors.min_value" class="error-text">{{ errors.min_value }}</span>
  </div>
  <div class="form-group">
    <label for="max_value">Nilai Maksimum</label>
    <input
      id="max_value"
      v-model.number="form.max_value"
      type="number"
      step="0.01"
      :class="{ 'error-input': errors.max_value }"
    />
    <span v-if="errors.max_value" class="error-text">{{ errors.max_value }}</span>
  </div>
  </div>
  
  <div class="form-row">
  <div class="form-group">
    <label for="target_value">Nilai Target</label>
    <input
      id="target_value"
      v-model.number="form.target_value"
      type="number"
      step="0.01"
      :class="{ 'error-input': errors.target_value }"
    />
    <span v-if="errors.target_value" class="error-text">{{ errors.target_value }}</span>
  </div>
  <div class="form-group">
    <label for="unit">Satuan</label>
    <input
      id="unit"
      v-model="form.unit"
      type="text"
      :class="{ 'error-input': errors.unit }"
    />
    <span v-if="errors.unit" class="error-text">{{ errors.unit }}</span>
  </div>
  </div>
  
  <div class="form-group">
  <label for="decimal_places">Jumlah Desimal</label>
  <input
    id="decimal_places"
    v-model.number="form.decimal_places"
    type="number"
    min="0"
    max="10"
    :class="{ 'error-input': errors.decimal_places }"
  />
  <span v-if="errors.decimal_places" class="error-text">{{ errors.decimal_places }}</span>
  </div>
  </div>
  
  <!-- Boolean Type Options -->
  <div v-if="form.type === 'boolean'" class="parameter-type-options">
  <h3>Konfigurasi Parameter Boolean</h3>
  
  <div class="form-group checkbox-group">
  <label>
    <input
      type="checkbox"
      v-model="form.default_boolean_value"
    />
    Nilai Default (centang untuk 'Ya')
  </label>
  </div>
  
  <div class="form-row">
  <div class="form-group">
    <label for="boolean_pass_value">Nilai untuk Lulus</label>
    <select
      id="boolean_pass_value"
      v-model="form.boolean_pass_value"
      :class="{ 'error-input': errors.boolean_pass_value }"
    >
      <option :value="true">Ya</option>
      <option :value="false">Tidak</option>
    </select>
    <span v-if="errors.boolean_pass_value" class="error-text">{{ errors.boolean_pass_value }}</span>
  </div>
  <div class="form-group">
    <label for="boolean_label_yes">Label untuk 'Ya'</label>
    <input
      id="boolean_label_yes"
      v-model="form.boolean_label_yes"
      type="text"
      placeholder="Ya"
      :class="{ 'error-input': errors.boolean_label_yes }"
    />
    <span v-if="errors.boolean_label_yes" class="error-text">{{ errors.boolean_label_yes }}</span>
  </div>
  <div class="form-group">
    <label for="boolean_label_no">Label untuk 'Tidak'</label>
    <input
      id="boolean_label_no"
      v-model="form.boolean_label_no"
      type="text"
      placeholder="Tidak"
      :class="{ 'error-input': errors.boolean_label_no }"
    />
    <span v-if="errors.boolean_label_no" class="error-text">{{ errors.boolean_label_no }}</span>
  </div>
  </div>
  </div>
  
  <!-- Text Type Options -->
  <div v-if="form.type === 'text'" class="parameter-type-options">
  <h3>Konfigurasi Parameter Teks</h3>
  
  <div class="form-group full-width">
  <label for="default_text_value">Nilai Default</label>
  <input
    id="default_text_value"
    v-model="form.default_text_value"
    type="text"
    :class="{ 'error-input': errors.default_text_value }"
  />
  <span v-if="errors.default_text_value" class="error-text">{{ errors.default_text_value }}</span>
  </div>
  
  <div class="form-row">
  <div class="form-group">
    <label for="min_length">Panjang Minimum</label>
    <input
      id="min_length"
      v-model.number="form.min_length"
      type="number"
      min="0"
      :class="{ 'error-input': errors.min_length }"
    />
    <span v-if="errors.min_length" class="error-text">{{ errors.min_length }}</span>
|</div>
<div class="form-group">
  <label for="max_length">Panjang Maksimum</label>
  <input
    id="max_length"
    v-model.number="form.max_length"
    type="number"
    min="0"
    :class="{ 'error-input': errors.max_length }"
  />
  <span v-if="errors.max_length" class="error-text">{{ errors.max_length }}</span>
</div>
</div>

<div class="form-group">
<label for="text_validation">Validasi (regex)</label>
<input
  id="text_validation"
  v-model="form.text_validation"
  type="text"
  placeholder="Contoh: ^[a-zA-Z0-9]+$"
  :class="{ 'error-input': errors.text_validation }"
/>
<span v-if="errors.text_validation" class="error-text">{{ errors.text_validation }}</span>
<small class="help-text">Masukkan pola regular expression untuk validasi nilai teks</small>
</div>
</div>

<!-- Enum Type Options -->
<div v-if="form.type === 'enum'" class="parameter-type-options">
<h3>Konfigurasi Parameter Enum</h3>

<div class="form-group">
<label>Opsi-opsi Pilihan</label>
<div class="enum-options">
  <div v-for="(option, index) in form.enum_options" :key="index" class="enum-option-item">
    <div class="enum-item-header">
      <strong>Opsi #{{ index + 1 }}</strong>
      <button type="button" class="btn-delete-sm" @click="removeEnumOption(index)">
        <span>&times;</span>
      </button>
    </div>
    
    <div class="form-row">
      <div class="form-group">
        <label :for="`enum_value_${index}`">Nilai</label>
        <input
          :id="`enum_value_${index}`"
          v-model="option.value"
          type="text"
          required
        />
      </div>
      <div class="form-group">
        <label :for="`enum_label_${index}`">Label</label>
        <input
          :id="`enum_label_${index}`"
          v-model="option.label"
          type="text"
          required
        />
      </div>
    </div>
    
    <div class="form-group checkbox-group">
      <label>
        <input
          type="checkbox"
          v-model="option.is_passing"
        />
        Nilai ini Lulus QC
      </label>
    </div>
  </div>
  
  <button type="button" @click="addEnumOption" class="btn-add-enum">
    <span>+</span> Tambah Opsi Baru
  </button>
</div>
<span v-if="errors.enum_options" class="error-text">{{ errors.enum_options }}</span>
</div>

<div class="form-group">
<label for="default_enum_index">Nilai Default</label>
<select
  id="default_enum_index"
  v-model="form.default_enum_index"
  :class="{ 'error-input': errors.default_enum_index }"
>
  <option value="-1">-- Tidak Ada Default --</option>
  <option 
    v-for="(option, index) in form.enum_options" 
    :key="index" 
    :value="index"
  >
    {{ option.label }}
  </option>
</select>
<span v-if="errors.default_enum_index" class="error-text">{{ errors.default_enum_index }}</span>
</div>
</div>
</div>

<div class="form-section">
<h2>Tampilan & Penggunaan</h2>

<div class="form-row">
<div class="form-group">
<label for="is_required">Wajib Diisi?</label>
<div class="toggle-switch">
  <input
    id="is_required"
    v-model="form.is_required"
    type="checkbox"
  />
  <label for="is_required" class="toggle-label"></label>
</div>
</div>
<div class="form-group">
<label for="is_visible">Terlihat?</label>
<div class="toggle-switch">
  <input
    id="is_visible"
    v-model="form.is_visible"
    type="checkbox"
  />
  <label for="is_visible" class="toggle-label"></label>
</div>
</div>
</div>

<div class="form-row">
<div class="form-group">
<label for="display_in_summary">Tampilkan di Ringkasan?</label>
<div class="toggle-switch">
  <input
    id="display_in_summary"
    v-model="form.display_in_summary"
    type="checkbox"
  />
  <label for="display_in_summary" class="toggle-label"></label>
</div>
</div>
<div class="form-group">
<label for="track_history">Lacak Riwayat Perubahan?</label>
<div class="toggle-switch">
  <input
    id="track_history"
    v-model="form.track_history"
    type="checkbox"
  />
  <label for="track_history" class="toggle-label"></label>
</div>
</div>
</div>
</div>

<div class="form-section">
<h2>Aplikasi Terhadap Produk</h2>

<div class="form-group">
<label>Item yang Menggunakan Parameter Ini</label>
<div class="products-selector">
<div class="search-products">
  <input
    type="text"
    v-model="productSearch"
    placeholder="Cari item..."
    @input="searchProducts"
  />
</div>

<div class="products-list">
  <div v-if="loadingProducts" class="loading-inline">
    Memuat produk...
  </div>
  <div v-else-if="filteredProducts.length === 0" class="no-data-inline">
    Tidak ada produk yang ditemukan
  </div>
  <div v-else>
    <div v-for="product in filteredProducts" :key="product.id" class="product-item">
      <label class="checkbox-label">
        <input 
          type="checkbox" 
          :value="product.id" 
          v-model="form.applicable_product_ids"
        />
        <span class="product-name">{{ product.name }}</span>
        <span class="product-code">{{ product.item_code }}</span>
      </label>
    </div>
  </div>
</div>

<div v-if="form.applicable_product_ids.length > 0" class="selected-products">
  <div class="selected-header">
    <h4>Item Terpilih ({{ form.applicable_product_ids.length }})</h4>
    <button type="button" @click="form.applicable_product_ids = []" class="btn-text">
      Hapus Semua
    </button>
  </div>
  
  <div class="selected-list">
    <div v-for="productId in form.applicable_product_ids" :key="productId" class="selected-item">
      <div class="selected-name">
        {{ getProductName(productId) }}
      </div>
      <button type="button" @click="removeProduct(productId)" class="btn-remove">
        &times;
      </button>
    </div>
  </div>
</div>
</div>
</div>
</div>

<div class="form-actions">
<button type="button" @click="goBack" class="btn-secondary">Batal</button>
<button type="submit" class="btn-primary">{{ isEditMode ? 'Perbarui Parameter' : 'Simpan Parameter' }}</button>
</div>
</form>
</div>
</div>
</template>

<script>
import axios from 'axios';

export default {
name: 'QualityParameterForm',
props: {
id: {
type: [Number, String],
default: null
}
},
data() {
return {
isEditMode: false,
loading: false,
error: null,
form: {
name: '',
type: 'numeric',
description: '',
category_id: '',
order: 1,
is_required: true,
is_visible: true,
display_in_summary: false,
track_history: true,

// Numeric properties
min_value: null,
max_value: null,
target_value: null,
unit: '',
decimal_places: 2,

// Boolean properties
default_boolean_value: true,
boolean_pass_value: true,
boolean_label_yes: 'Ya',
boolean_label_no: 'Tidak',

// Text properties
default_text_value: '',
min_length: 0,
max_length: 255,
text_validation: '',

// Enum properties
enum_options: [],
default_enum_index: -1,

// Products where this parameter applies
applicable_product_ids: []
},
errors: {},

// Categories
categories: [],
loadingCategories: false,

// Products
allProducts: [],
filteredProducts: [],
productSearch: '',
loadingProducts: false
};
},
created() {
// Determine if we're in edit mode
this.isEditMode = !!this.id;

// Load categories
this.fetchCategories();

// Load products
this.fetchProducts();

if (this.isEditMode) {
this.fetchParameterData();
} else {
// Add one default enum option
this.addEnumOption();
}
},
methods: {
async fetchCategories() {
this.loadingCategories = true;

try {
const response = await axios.get('/manufacturing/quality/parameters/categories');
this.categories = response.data.data;
} catch (err) {
console.error('Failed to load categories:', err);
} finally {
this.loadingCategories = false;
}
},

async fetchProducts() {
this.loadingProducts = true;

try {
// Menggunakan endpoint items yang sesuai dengan controller Anda
const response = await axios.get('/manufacturing/quality/parameters/items');
this.allProducts = response.data.data;
this.filteredProducts = [...this.allProducts];
} catch (err) {
console.error('Failed to load items:', err);
} finally {
this.loadingProducts = false;
}
},

async fetchParameterData() {
this.loading = true;
this.error = null;

try {
const response = await axios.get(`/manufacturing/quality/parameters/${this.id}`);

// Map API data to form structure
const parameter = response.data;

// Base fields
this.form = {
name: parameter.name,
type: parameter.type,
description: parameter.description || '',
category_id: parameter.category_id || '',
order: parameter.order || 1,
is_required: parameter.is_required !== false,
is_visible: parameter.is_visible !== false,
display_in_summary: parameter.display_in_summary || false,
track_history: parameter.track_history !== false,
applicable_product_ids: parameter.applicable_product_ids || []
};

// Type-specific fields
if (parameter.type === 'numeric') {
this.form.min_value = parameter.min_value;
this.form.max_value = parameter.max_value;
this.form.target_value = parameter.target_value;
this.form.unit = parameter.unit || '';
this.form.decimal_places = parameter.decimal_places || 2;
} else if (parameter.type === 'boolean') {
this.form.default_boolean_value = parameter.default_boolean_value !== false;
this.form.boolean_pass_value = parameter.boolean_pass_value !== false;
this.form.boolean_label_yes = parameter.boolean_label_yes || 'Ya';
this.form.boolean_label_no = parameter.boolean_label_no || 'Tidak';
} else if (parameter.type === 'text') {
this.form.default_text_value = parameter.default_text_value || '';
this.form.min_length = parameter.min_length || 0;
this.form.max_length = parameter.max_length || 255;
this.form.text_validation = parameter.text_validation || '';
} else if (parameter.type === 'enum') {
this.form.enum_options = parameter.enum_options || [];
this.form.default_enum_index = parameter.default_enum_index || -1;

// Ensure at least one option
if (!this.form.enum_options.length) {
this.addEnumOption();
}
}

this.loading = false;
} catch (err) {
this.error = 'Gagal memuat data parameter: ' + (err.response?.data?.message || err.message);
this.loading = false;
}
},

handleTypeChange() {
// Reset appropriate fields based on type
if (this.form.type === 'numeric') {
this.form.min_value = null;
this.form.max_value = null;
this.form.target_value = null;
this.form.unit = '';
this.form.decimal_places = 2;
} else if (this.form.type === 'boolean') {
this.form.default_boolean_value = true;
this.form.boolean_pass_value = true;
this.form.boolean_label_yes = 'Ya';
this.form.boolean_label_no = 'Tidak';
} else if (this.form.type === 'text') {
this.form.default_text_value = '';
this.form.min_length = 0;
this.form.max_length = 255;
this.form.text_validation = '';
} else if (this.form.type === 'enum') {
if (!this.form.enum_options || !this.form.enum_options.length) {
this.form.enum_options = [];
this.addEnumOption();
}
this.form.default_enum_index = -1;
}
},

addEnumOption() {
this.form.enum_options.push({
value: '',
label: '',
is_passing: true
});
},

removeEnumOption(index) {
if (this.form.enum_options.length > 1) {
this.form.enum_options.splice(index, 1);

// Update default enum index if needed
if (this.form.default_enum_index === index) {
this.form.default_enum_index = -1;
} else if (this.form.default_enum_index > index) {
this.form.default_enum_index--;
}
} else {
alert('Setidaknya harus ada satu opsi pilihan');
}
},

searchProducts() {
if (!this.productSearch.trim()) {
this.filteredProducts = [...this.allProducts];
return;
}

const search = this.productSearch.toLowerCase();
this.filteredProducts = this.allProducts.filter(product => 
(product.name || '').toLowerCase().includes(search) || 
(product.item_code || '').toLowerCase().includes(search)
);
},

getProductName(productId) {
const product = this.allProducts.find(p => p.id === productId);
return product ? product.name : `Product #${productId}`;
},

removeProduct(productId) {
const index = this.form.applicable_product_ids.indexOf(productId);
if (index !== -1) {
this.form.applicable_product_ids.splice(index, 1);
}
},

validateForm() {
this.errors = {};
let isValid = true;

// Basic validation
if (!this.form.name.trim()) {
this.errors.name = 'Nama parameter harus diisi';
isValid = false;
}

// Type-specific validation
if (this.form.type === 'numeric') {
if (this.form.min_value !== null && this.form.max_value !== null && this.form.min_value > this.form.max_value) {
this.errors.min_value = 'Nilai minimum tidak boleh lebih besar dari nilai maksimum';
isValid = false;
}

if (this.form.decimal_places < 0) {
this.errors.decimal_places = 'Jumlah desimal tidak boleh negatif';
isValid = false;
}
} else if (this.form.type === 'text') {
if (this.form.min_length < 0) {
this.errors.min_length = 'Panjang minimum tidak boleh negatif';
isValid = false;
}

if (this.form.max_length < this.form.min_length) {
this.errors.max_length = 'Panjang maksimum harus lebih besar dari panjang minimum';
isValid = false;
}

if (this.form.text_validation) {
try {
new RegExp(this.form.text_validation);
} catch (e) {
this.errors.text_validation = 'Pola regex tidak valid';
isValid = false;
}
}
} else if (this.form.type === 'enum') {
const enumErrors = [];
let hasEmptyValues = false;

this.form.enum_options.forEach((option, index) => {
if (!option.value.trim() || !option.label.trim()) {
hasEmptyValues = true;
enumErrors.push(`Opsi #${index + 1}: Nilai dan label harus diisi`);
}
});

if (hasEmptyValues) {
this.errors.enum_options = enumErrors.join(', ');
isValid = false;
}

// Check for duplicate values
const values = this.form.enum_options.map(o => o.value.trim());
const hasDuplicates = values.some((v, i) => values.indexOf(v) !== i);

if (hasDuplicates) {
if (this.errors.enum_options) {
this.errors.enum_options += ', Nilai tidak boleh duplikat';
} else {
this.errors.enum_options = 'Nilai tidak boleh duplikat';
}
isValid = false;
}
}

return isValid;
},

async saveParameter() {
if (!this.validateForm()) {
// Scroll to first error
const firstErrorEl = document.querySelector('.error-input, .error-text');
if (firstErrorEl) {
firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
return;
}

this.loading = true;
this.error = null;

try {
if (this.isEditMode) {
  await axios.put(`/manufacturing/quality/parameters/${this.id}`, this.form);
} else {
  await axios.post('/manufacturing/quality/parameters', this.form);
}

// Redirect to parameters list with success message
this.$router.push({
path: '/quality-parameters',
query: { 
success: 'true',
message: this.isEditMode ? 'Parameter berhasil diperbarui' : 'Parameter baru berhasil dibuat'
}
});
} catch (err) {
if (err.response && err.response.data && err.response.data.errors) {
this.errors = err.response.data.errors;

// Scroll to first error
const firstErrorEl = document.querySelector('.error-input, .error-text');
if (firstErrorEl) {
firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
} else {
this.error = 'Gagal menyimpan data: ' + (err.response?.data?.message || err.message);
}
this.loading = false;
}
},

resetForm() {
this.form = {
name: '',
type: 'numeric',
description: '',
category_id: '',
order: 1,
is_required: true,
is_visible: true,
display_in_summary: false,
track_history: true,

min_value: null,
max_value: null,
target_value: null,
unit: '',
decimal_places: 2,

applicable_product_ids: []
};
this.errors = {};
this.error = null;
},

goBack() {
this.$router.go(-1);
}
}
};
</script>

<style scoped>
.quality-parameter-form {
padding: 20px;
max-width: 1200px;
margin: 0 auto;
}

.page-header {
margin-bottom: 30px;
}

.parameter-form {
background: #fff;
border-radius: 5px;
box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.error-message {
color: #f44336;
}

.error-message button {
margin-top: 15px;
background-color: #f5f5f5;
border: 1px solid #ddd;
padding: 8px 15px;
border-radius: 4px;
cursor: pointer;
}

@media (max-width: 768px) {
.form-group {
flex: 0 0 100%;
}
}

.form-section {
padding: 25px;
border-bottom: 1px solid #eee;
}

.form-section h2 {
margin-top: 0;
margin-bottom: 20px;
font-size: 1.2rem;
color: #333;
}

.form-section h3 {
margin-top: 25px;
margin-bottom: 15px;
font-size: 1rem;
color: #555;
}

.form-row {
display: flex;
flex-wrap: wrap;
margin: 0 -10px 15px -10px;
}

.form-group {
flex: 1;
min-width: 250px;
padding: 0 10px;
margin-bottom: 15px;
}

.form-group.full-width {
flex: 0 0 100%;
}

label {
display: block;
margin-bottom: 5px;
font-weight: 500;
color: #555;
}

.checkbox-group label {
display: flex;
align-items: center;
font-weight: normal;
}

input[type="text"],
input[type="number"],
select,
textarea {
width: 100%;
padding: 10px;
border: 1px solid #ddd;
border-radius: 4px;
font-size: 1rem;
}

input[type="checkbox"] {
margin-right: 8px;
}

.error-input {
border-color: #f44336 !important;
}

.error-text {
display: block;
color: #f44336;
font-size: 0.8rem;
margin-top: 5px;
}

.help-text {
display: block;
color: #666;
font-size: 0.8rem;
margin-top: 5px;
}

.parameter-type-options {
background-color: #f9f9f9;
padding: 20px;
border-radius: 5px;
margin-top: 20px;
}

.enum-options {
margin-top: 15px;
}

.enum-option-item {
border: 1px solid #ddd;
border-radius: 5px;
padding: 15px;
margin-bottom: 15px;
background-color: white;
}

.enum-item-header {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 15px;
}

.btn-add-enum {
display: flex;
align-items: center;
justify-content: center;
width: 100%;
padding: 12px;
border: 2px dashed #ddd;
border-radius: 5px;
background: none;
color: #666;
font-weight: 500;
cursor: pointer;
}

.btn-add-enum:hover {
border-color: #4caf50;
color: #4caf50;
}

.btn-add-enum span {
font-size: 1.2rem;
margin-right: 8px;
}

.btn-delete-sm {
background: none;
border: none;
color: #f44336;
font-size: 1.2rem;
cursor: pointer;
padding: 5px;
border-radius: 50%;
height: 30px;
width: 30px;
display: flex;
align-items: center;
justify-content: center;
}

.btn-delete-sm:hover {
background-color: #ffebee;
}

.toggle-switch {
position: relative;
display: inline-block;
width: 60px;
height: 30px;
}

.toggle-switch input {
opacity: 0;
width: 0;
height: 0;
}

.toggle-label {
position: absolute;
cursor: pointer;
top: 0;
left: 0;
right: 0;
bottom: 0;
background-color: #ccc;
transition: .4s;
border-radius: 30px;
}

.toggle-label:before {
position: absolute;
content: "";
height: 22px;
width: 22px;
left: 4px;
bottom: 4px;
background-color: white;
transition: .4s;
border-radius: 50%;
}

input:checked + .toggle-label {
background-color: #4caf50;
}

input:checked + .toggle-label:before {
transform: translateX(30px);
}

.products-selector {
border: 1px solid #ddd;
border-radius: 5px;
padding: 0;
background-color: #f9f9f9;
margin-top: 10px;
}

.search-products {
padding: 15px;
border-bottom: 1px solid #eee;
}

.search-products input {
width: 100%;
padding: 10px;
border: 1px solid #ddd;
border-radius: 4px;
}

.products-list {
max-height: 200px;
overflow-y: auto;
padding: 0 15px;
}

.product-item {
padding: 10px 0;
border-bottom: 1px solid #eee;
}

.product-item:last-child {
border-bottom: none;
}

.checkbox-label {
display: flex;
align-items: center;
cursor: pointer;
}

.product-name {
flex-grow: 1;
margin-left: 10px;
}

.product-code {
color: #666;
font-size: 0.9rem;
margin-left: 10px;
}

.selected-products {
border-top: 1px solid #eee;
padding: 15px;
}

.selected-header {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 15px;
}

.selected-header h4 {
margin: 0;
font-size: 1rem;
}

.selected-list {
display: flex;
flex-wrap: wrap;
gap: 10px;
}

.selected-item {
display: flex;
align-items: center;
gap: 8px;
background-color: #e8f5e9;
border-radius: 15px;
padding: 5px 10px;
font-size: 0.9rem;
}

.btn-remove {
background: none;
border: none;
color: #666;
font-size: 1.2rem;
padding: 0;
cursor: pointer;
display: flex;
align-items: center;
justify-content: center;
}

.loading-inline, .no-data-inline {
padding: 20px;
text-align: center;
color: #666;
}

.form-actions {
padding: 25px;
display: flex;
justify-content: flex-end;
gap: 15px;
}

.btn-primary {
background-color: #4caf50;
color: white;
border: none;
padding: 12px 20px;
border-radius: 4px;
cursor: pointer;
font-weight: 500;
min-width: 150px;
}

.btn-secondary {
background-color: #f5f5f5;
color: #333;
border: 1px solid #ddd;
padding: 12px 20px;
border-radius: 4px;
cursor: pointer;
font-weight: 500;
}

.btn-text {
background: none;
border: none;
color: #666;
padding: 5px;
cursor: pointer;
font-size: 0.9rem;
}

.loading, .error-message {
text-align: center;
padding: 50px;
background: #fff;
border-radius: 5px;
box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}
</style>