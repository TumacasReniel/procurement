<template>
  <b-modal
    :model-value="modelValue"
    :title="title"
    centered
    :scrollable="type === 'stock'"
    :fullscreen="type === 'stock' ? 'lg' : false"
    :style="modalStyle"
    header-class="p-3 bg-body-tertiary border-bottom"
    content-class="border-0 shadow-lg"
    body-class="bg-body p-0"
    footer-class="bg-body-tertiary border-top py-2"
    :size="type === 'stock' ? 'xl' : 'lg'"
    class="v-modal-custom"
    modal-class="zoomIn"
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <div v-if="record" class="p-3">
      <BRow class="g-2">
        <BCol
          v-for="field in fields"
          :key="field.label"
          cols="6"
          :md="type === 'stock' ? undefined : 6"
          :lg="type === 'stock' ? undefined : 6"
          :class="type === 'stock' ? 'col-lg' : ''"
        >
          <BCard no-body class="h-100 border shadow-none">
            <BCardBody class="p-2 p-xl-3">
              <div class="text-muted small fw-semibold text-uppercase mb-1">{{ field.label }}</div>
              <div class="text-body fw-semibold">{{ field.value || '-' }}</div>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>

      <BCard v-if="type === 'stock'" no-body class="border shadow-none mt-3 mb-0">
        <div class="card-header bg-body-tertiary d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
          <div>
            <h6 class="mb-1 text-body">Items under this stock</h6>
            <p class="mb-0 text-muted small">Inventory items currently assigned to {{ record.name || 'this stock group' }}.</p>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge text-bg-primary rounded-pill px-3 py-2">
              {{ formatNumber(stockItemCount) }} item{{ stockItemCount === 1 ? '' : 's' }}
            </span>
            <b-button
              v-if="canAddStockItem"
              type="button"
              size="sm"
              variant="primary"
              @click="$emit('add-stock-item', record)"
            >
              <i class="ri-add-circle-fill me-1"></i>Add Item
            </b-button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0 text-nowrap">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Item Name</th>
                <th>Category</th>
                <th class="text-end">Quantity</th>
                <th class="text-end">Unit Cost</th>
                <th>Expiration</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="stockItemsLoading">
                <td colspan="6" class="text-center text-muted py-4">Loading linked items...</td>
              </tr>
              <tr v-else-if="stockItems.length === 0">
                <td colspan="6" class="text-center text-muted py-4">No items are linked to this stock yet.</td>
              </tr>
              <tr v-else v-for="item in stockItems" :key="item.id">
                <td class="fw-semibold text-primary">{{ item.code || '-' }}</td>
                <td>{{ item.name || '-' }}</td>
                <td>{{ item.category || '-' }}</td>
                <td class="text-end">{{ formatNumber(item.quantity) }}</td>
                <td class="text-end">{{ formatNumber(item.unit_cost) }}</td>
                <td>{{ item.expiration || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCard>
    </div>
    <div v-else class="text-muted p-3">No record selected.</div>

    <template #footer>
      <b-button type="button" variant="light" @click="$emit('update:modelValue', false)">Close</b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  name: 'RecordViewModal',
  props: ['modelValue', 'type', 'record', 'stockItems', 'stockItemsLoading', 'canAddStockItem'],
  emits: ['update:modelValue', 'add-stock-item'],
  computed: {
    modalStyle() {
      return this.type === 'stock' ? '--vz-modal-width: 96vw;' : '';
    },
    title() {
      if (this.type === 'item') return 'View Item';
      if (this.type === 'stock') return 'View Stock';
      if (this.type === 'receiving') return 'View Receiving';
      if (this.type === 'withdrawal') return 'View Withdrawal';
      return 'View Record';
    },
    introText() {
      if (this.type === 'stock') {
        return 'Review this stock group and the inventory items assigned under it.';
      }

      return 'Review the important details for this inventory record.';
    },
    stockItemCount() {
      return Number(this.record?.item_count ?? this.stockItems.length) || 0;
    },
    fields() {
      if (!this.record) return [];

      const maps = {
        stock: [
          ['Code', this.record.code],
          ['Stock Name', this.record.name],
          ['Entry Date', this.record.entry_date],
          ['Linked Items', this.record.item_count],
          ['Total Quantity', this.record.total_quantity],
        ],
        item: [
          ['Code', this.record.code],
          ['Item Name', this.record.name],
          ['Stock', this.record.stock_name],
          ['Category', this.record.category],
          ['Quantity', this.record.quantity],
          ['Unit Cost', this.record.unit_cost],
          ['Expiration', this.record.expiration],
        ],
        receiving: [
          ['Item', this.record.item_name],
          ['Approved By', this.record.approved_by],
          ['Status', this.record.status],
          ['Date Received', this.record.received_at],
          ['Remarks', this.record.remarks],
        ],
        withdrawal: [
          ['Item', this.record.item_name],
          ['Requested By', this.record.requested_by],
          ['Approved By', this.record.approved_by],
          ['Date Released', this.record.released_at],
          ['Status', this.record.status],
          ['Remarks', this.record.remarks],
        ],
      };

      const mappedFields = maps[this.type] || Object.entries(this.record).map(([key, value]) => [
        key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
        value,
      ]);

      return mappedFields.map(([label, value]) => ({
        label,
        value: this.formatValue(value),
      }));
    },
  },
  methods: {
    formatValue(value) {
      if (value === null || value === undefined || value === '') {
        return '-';
      }

      if (typeof value === 'object') {
        return value.name || value.code || JSON.stringify(value);
      }

      return value;
    },
    formatNumber(value) {
      return new Intl.NumberFormat().format(Number(value || 0));
    },
  },
};
</script>
