<template>
  <div class="employee-detail-view">
    <section class="employee-hero">
      <div class="employee-hero__mesh"></div>
      <div class="employee-hero__glow"></div>

      <div class="employee-hero__content">
        <div class="employee-hero__main">
          <div class="employee-hero__header">
            <div class="employee-hero__title-group">
              <div class="employee-hero__icon">
                <i class="ri-survey-line"></i>
              </div>

              <div class="employee-hero__copy">
                <h6 class="employee-hero__title">Purchase Request Details</h6>
              </div>
            </div>

            <div class="employee-hero__status-row">
              <b-badge
                :class="[
                  procurement.status?.bg || 'bg-secondary',
                  'employee-status-badge',
                ]"
              >
                <i
                  :class="statusIcons[procurement.status?.id] || 'ri-information-line'"
                  class="me-2"
                ></i>
                {{ procurement.status?.name || "N/A" }}
              </b-badge>

              <b-badge
                v-if="procurement.sub_status"
                class="employee-substatus-badge"
              >
                {{ procurement.sub_status.name }}
              </b-badge>
            </div>
          </div>

          <div class="employee-hero__meta">
            <span class="employee-meta-chip">
              <i class="ri-file-list-3-line"></i>
              <span class="employee-meta-chip__content">
                <span class="employee-meta-chip__label">PR Number</span>
                <span class="employee-meta-chip__value">
                  {{ procurement.code || "-" }}
                </span>
              </span>
            </span>

            <span class="employee-meta-chip">
              <i class="ri-calendar-line"></i>
              <span class="employee-meta-chip__content">
                <span class="employee-meta-chip__label">PR Date</span>
                <span class="employee-meta-chip__value">
                  {{ displayProcurementDate }}
                </span>
              </span>
            </span>

            <span class="employee-meta-chip">
              <i class="ri-building-line"></i>
              <span class="employee-meta-chip__content">
                <span class="employee-meta-chip__label">Division</span>
                <span class="employee-meta-chip__value">
                  {{ procurement.division?.name || "-" }}
                </span>
              </span>
            </span>

            <span class="employee-meta-chip">
              <i class="ri-community-line"></i>
              <span class="employee-meta-chip__content">
                <span class="employee-meta-chip__label">Unit</span>
                <span class="employee-meta-chip__value">
                  {{ procurement.unit?.name || "-" }}
                </span>
              </span>
            </span>
          </div>
        </div>

   
      </div>

  
    </section>

    <section class="employee-overview-grid">
      <article class="employee-panel employee-panel--statistics">
        <div class="employee-panel__header employee-panel__header--stack">
          <div class="employee-panel__header-icon">
            <i class="ri-bar-chart-box-line"></i>
          </div>
          <div class="employee-panel__header-copy">
            <h5 class="employee-panel__title">Procurement Activity</h5>
            <p class="employee-panel__subtitle">
              A quick read on quotations, reawards, and repeat bidding activity.
            </p>
          </div>
        </div>

        <div class="employee-metrics-grid">
          <div
            v-for="metric in activityMetrics"
            :key="metric.label"
            :class="[
              'employee-metric-card',
              `employee-metric-card--${metric.accent}`,
            ]"
          >
            <div class="employee-metric-card__icon">
              <i :class="metric.icon"></i>
            </div>
            <div class="employee-metric-card__copy">
              <div class="employee-metric-card__top">
                <span class="employee-metric-card__label">{{ metric.label }}</span>
                <strong class="employee-metric-card__value">{{ metric.value }}</strong>
              </div>
              <span class="employee-metric-card__hint">{{ metric.hint }}</span>
            </div>
          </div>
        </div>
      </article>

      <article class="employee-panel employee-panel--personnel">
        <div class="employee-panel__header employee-panel__header--stack">
          <div class="employee-panel__header-icon">
            <i class="ri-team-line"></i>
          </div>
          <div class="employee-panel__header-copy">
            <h5 class="employee-panel__title">Personnel</h5>
            <p class="employee-panel__subtitle">
              The people coordinating, requesting, and approving this procurement.
            </p>
          </div>
        </div>

        <div class="employee-personnel-list">
          <div
            v-for="person in personnelCards"
            :key="person.role"
            :class="[
              'employee-personnel-item',
              `employee-personnel-item--${person.accent}`,
              { 'employee-personnel-item--empty': !person.assigned },
            ]"
          >
            <div class="employee-personnel-item__avatar">
              {{ getInitials(person.name) }}
            </div>

            <div class="employee-personnel-item__copy">
              <div class="employee-personnel-item__top">
                <span class="employee-personnel-item__role">{{ person.role }}</span>
                <span class="employee-personnel-item__symbol">
                  <i :class="person.icon"></i>
                </span>
              </div>
              <span class="employee-personnel-item__name">{{ person.name }}</span>
            </div>
          </div>
        </div>
      </article>
    </section>

    <section class="employee-panel employee-panel--pap">
      <div class="employee-panel__header employee-panel__header--stack">
        <div class="employee-panel__header-icon">
          <i class="ri-price-tag-3-line"></i>
        </div>
        <div class="employee-panel__header-copy">
          <h5 class="employee-panel__title">PAP Codes</h5>
          <p class="employee-panel__subtitle">
            Program and budget references attached to this procurement request.
          </p>
        </div>
      </div>

      <div v-if="procurement.codes?.length" class="employee-pap-grid">
        <article
          v-for="(code, index) in procurement.codes"
          :key="code.id"
          class="employee-pap-card"
          :title="formatPapCode(code)"
        >
          <strong class="employee-pap-card__value">
            {{ code.procurement_code?.code || "Uncoded" }} - {{ code.procurement_code?.title || "No title provided." }}
          </strong>
    
        </article>
      </div>

      <div v-else class="employee-empty-inline">
        <div class="employee-empty-inline__icon">
          <i class="ri-price-tag-3-line"></i>
        </div>
        <div>
          <strong class="employee-empty-inline__title">No PAP Codes Yet</strong>
          <p class="employee-empty-inline__text mb-0">
            This procurement has not been linked to a PAP code.
          </p>
        </div>
      </div>
    </section>

    <section class="employee-panel employee-panel--table">
      <div class="employee-panel__header employee-panel__header--split">
        <div class="employee-panel__header-group">
          <div class="employee-panel__header-icon">
            <i class="ri-shopping-bag-3-line"></i>
          </div>

          <div class="employee-panel__header-copy">
            <h5 class="employee-panel__title">Procurement Items</h5>
            <p class="employee-panel__subtitle">
              Requested items with linked purchase orders and delivery dates.
            </p>
          </div>
        </div>

      
      </div>

      <div v-if="procurement.items?.length" class="employee-table-shell">
        <div class="table-responsive employee-table-responsive">
          <table class="employee-items-table">
            <thead>
              <tr>
                <th class="text-center">#</th>
                <th class="text-center">Status</th>
                <th>Item</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Unit</th>
                <th class="text-center">Purchase Orders</th>
                <th class="text-center">Delivered On</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in procurement.items" :key="item.id">
                <td class="text-center employee-row-number">{{ index + 1 }}</td>
                <td class="text-center">
                  <b-badge
                    v-if="item.status"
                    :class="item.status.bg"
                    class="employee-item-status-badge"
                  >
                    {{ item.status.name }}
                  </b-badge>
                  <span v-else class="text-muted">-</span>
                </td>
                <td class="employee-item-description">
                  <div class="employee-item-title">
                    {{ item.item_name || "-" }}
                  </div>
                  <div
                    v-if="item.item_description"
                    class="employee-item-richtext"
                    v-html="item.item_description"
                  ></div>
                  <span v-else class="employee-item-richtext text-muted">
                    No description provided.
                  </span>
                </td>
                <td class="text-center employee-item-quantity">
                  {{ item.item_quantity || "-" }}
                </td>
                <td class="text-center employee-item-unit">
                  {{
                    item.item_quantity > 1
                      ? item.item_unit_type?.name_long
                      : item.item_unit_type?.name_short || "N/A"
                  }}
                </td>
                <td class="text-center">
                  <div
                    v-if="getItemPurchaseOrders(item).length"
                    class="employee-po-stack"
                  >
                    <span
                      v-for="po in getItemPurchaseOrders(item)"
                      :key="`${item.id}-po-${po.id}`"
                      class="employee-po-action"
                    >
                      <span class="employee-po-chip">
                        {{ po.code }}
                      </span>

                      <b-button
                        v-if="canPrintPurchaseOrder(po)"
                        type="button"
                        variant="outline-primary"
                        size="sm"
                        class="employee-po-print-btn"
                        v-b-tooltip.hover
                        title="Print PO"
                        @click.stop="printPurchaseOrder(po)"
                      >
                        <i class="ri-printer-line"></i>
                        <span class="visually-hidden">Print {{ po.code }}</span>
                      </b-button>
                    </span>
                  </div>
                  <span v-else class="text-muted">-</span>
                </td>
                <td class="text-center">
                  <div
                    v-if="getItemPurchaseOrders(item).length"
                    class="employee-po-stack"
                  >
                    <span
                      v-for="po in getItemPurchaseOrders(item)"
                      :key="`${item.id}-delivery-${po.id}`"
                      class="employee-po-date"
                    >
                      {{ po.deliveredOn }}
                    </span>
                  </div>
                  <span v-else class="text-muted">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="employee-empty-state">
        <div class="employee-empty-state__icon">
          <i class="ri-shopping-bag-line"></i>
        </div>
        <h6 class="employee-empty-state__title">No Items Found</h6>
        <p class="employee-empty-state__text">
          This procurement has no items assigned yet.
        </p>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  props: ["procurement"],
  data() {
    return {
      statusIcons: {
        36: "ri-time-line",
        37: "ri-eye-line",
        38: "ri-check-circle-line",
        39: "ri-trophy-line",
        40: "ri-trophy-line",
        41: "ri-close-circle-line",
        42: "ri-file-text-line",
        43: "ri-auction-line",
        44: "ri-file-line",
        45: "ri-file-line",
        46: "ri-trophy-line",
        47: "ri-send-plane-line",
        48: "ri-send-plane-line",
        49: "ri-shopping-cart-line",
        50: "ri-check-line",
        51: "ri-check-line",
        52: "ri-truck-line",
        53: "ri-check-line",
        54: "ri-check-line",
        55: "ri-close-line",
        56: "ri-check-line",
        57: "ri-auction-line",
        58: "ri-check-line",
        59: "ri-trophy-line",
        60: "ri-refresh-line",
        61: "ri-close-line",
        62: "ri-time-line",
        63: "ri-time-line",
        64: "ri-shopping-cart-line",
        65: "ri-check-line",
        66: "ri-truck-line",
        67: "ri-check-line",
        68: "ri-close-line",
      },
    };
  },
  computed: {
    itemPurchaseOrderMap() {
      const map = {};
      const purchaseOrders = Array.isArray(this.procurement?.pos)
        ? this.procurement.pos
        : [];

      purchaseOrders.forEach((po) => {
        this.extractProcurementItemIds(po).forEach((itemId) => {
          if (!map[itemId]) {
            map[itemId] = [];
          }

          if (map[itemId].some((existing) => existing.id === po.id)) {
            return;
          }

          map[itemId].push({
            id: po.id,
            code: po.code || "-",
            status: po.status?.name || null,
            deliveredOn: this.resolvePurchaseOrderDeliveredOn(po),
          });
        });
      });

      return map;
    },
    totalItemsCount() {
      return Array.isArray(this.procurement?.items)
        ? this.procurement.items.length
        : 0;
    },
    linkedPurchaseOrdersCount() {
      return Array.isArray(this.procurement?.pos) ? this.procurement.pos.length : 0;
    },
    papCodesCount() {
      return Array.isArray(this.procurement?.codes)
        ? this.procurement.codes.length
        : 0;
    },
    displayProcurementDate() {
      return this.formatDisplayDate(this.procurement?.date);
    },
    assignedPersonnelCount() {
      return [
        this.procurement?.created_by,
        this.procurement?.requested_by,
        this.procurement?.approved_by,
      ].filter(Boolean).length;
    },

 
    activityMetrics() {
      return [
        {
          label: "Quotations",
          value: this.normalizeCount(this.procurement?.quotation_count),
          icon: "ri-file-list-3-line",
          accent: "blue",
          hint: "Recorded supplier quotations.",
        },
        {
          label: "Reawarded",
          value: this.normalizeCount(this.procurement?.reawarded_count),
          icon: "ri-refresh-line",
          accent: "amber",
          hint: "Award revisions after evaluation.",
        },
        {
          label: "Rebid",
          value: this.normalizeCount(this.procurement?.rebidded_count),
          icon: "ri-loop-left-line",
          accent: "rose",
          hint: "Repeat bidding cycles on record.",
        },
      ];
    },
    personnelCards() {
      return [
        {
          role: "Created By",
          name: this.getPersonnelName(this.procurement?.created_by),
          accent: "blue",
          icon: "ri-draft-line",
          assigned: Boolean(this.procurement?.created_by),
        },
        {
          role: "Requested By",
          name: this.getPersonnelName(this.procurement?.requested_by),
          accent: "amber",
          icon: "ri-user-received-line",
          assigned: Boolean(this.procurement?.requested_by),
        },
        {
          role: "Approved By",
          name: this.getPersonnelName(this.procurement?.approved_by),
          accent: "emerald",
          icon: "ri-shield-check-line",
          assigned: Boolean(this.procurement?.approved_by),
        },
      ];
    },
  },
  methods: {
    normalizeCount(value) {
      return Number(value ?? 0);
    },
    pluralize(count, singular, plural = `${singular}s`) {
      return Number(count) === 1 ? singular : plural;
    },
    getPersonnelName(person) {
      if (!person) {
        return "Not yet assigned";
      }

      if (typeof person === "string") {
        return person;
      }

      return (
        person?.profile?.fullname ||
        person?.profile?.full_name ||
        person?.name ||
        "Not yet assigned"
      );
    },
    getInitials(name) {
      if (!name) {
        return "NA";
      }

      return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join("");
    },
    formatPapCode(code) {
      const itemCode = code?.procurement_code?.code;
      const title = code?.procurement_code?.title;

      if (itemCode && title) {
        return `${itemCode} - ${title}`;
      }

      return itemCode || title || "-";
    },
    getItemPurchaseOrders(item) {
      return this.itemPurchaseOrderMap[item?.id] || [];
    },
    canPrintPurchaseOrder(po) {
      return Boolean(po?.id);
    },
    printPurchaseOrder(po) {
      if (!this.canPrintPurchaseOrder(po)) {
        return;
      }

      window.open(`/faims/purchase-orders/${po.id}?option=print&type=purchase_order`, "_blank");
    },
    extractProcurementItemIds(po) {
      const noaItems = Array.isArray(po?.noa?.items) ? po.noa.items : [];

      return [
        ...new Set(
          noaItems
            .map((noaItem) => {
              const quotationItem = noaItem?.item;
              const procurementItemId =
                quotationItem?.procurement_item_id || quotationItem?.item?.id;

              return Number(procurementItemId || 0);
            })
            .filter((itemId) => itemId > 0)
        ),
      ];
    },
    resolvePurchaseOrderDeliveredOn(po) {
      const latestIarDate =
        Array.isArray(po?.iars) && po.iars.length
          ? po.iars
              .map((iar) => iar?.created_at)
              .filter(Boolean)
              .sort((left, right) => new Date(right) - new Date(left))[0]
          : null;

      return this.formatDisplayDate(
        po?.actual_delivery_date || latestIarDate || po?.date_of_delivery
      );
    },
    formatDisplayDate(value) {
      if (!value) {
        return "-";
      }

      if (typeof value === "string") {
        const parts = value.match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (parts) {
          const [, year, month, day] = parts;
          return new Date(
            Number(year),
            Number(month) - 1,
            Number(day)
          ).toLocaleDateString("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric",
          });
        }
      }

      const parsedDate = new Date(value);

      if (Number.isNaN(parsedDate.getTime())) {
        return value;
      }

      return parsedDate.toLocaleDateString("en-US", {
        month: "long",
        day: "numeric",
        year: "numeric",
      });
    },
  },
};
</script>

<style scoped>
.employee-detail-view {
  --employee-primary: #4c5d98;
  --employee-primary-strong: #3f4d80;
  --employee-primary-light: #6d7fbc;
  --employee-primary-rgb: 76, 93, 152;
  --employee-secondary: #16233d;
  --employee-muted: #61738d;
  --employee-border: rgba(116, 137, 168, 0.2);
  --employee-surface: #ffffff;
  --employee-surface-alt: linear-gradient(180deg, #fcfdff 0%, #f4f6fd 100%);
  --employee-panel-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  width: 100%;
}

.employee-hero {
  position: relative;
  overflow: hidden;
  padding: 1rem;
  border-radius: 28px;
  background:
    radial-gradient(circle at 16% 14%, rgba(255, 255, 255, 0.18), transparent 26%),
    radial-gradient(circle at 100% 0%, rgba(var(--employee-primary-rgb), 0.2), transparent 28%),
    linear-gradient(
      135deg,
      var(--employee-primary-strong) 0%,
      var(--employee-primary) 52%,
      var(--employee-primary-light) 100%
    );
  box-shadow: 0 30px 70px rgba(15, 23, 42, 0.2);
}

.employee-hero__mesh,
.employee-hero__glow {
  pointer-events: none;
  position: absolute;
  inset: 0;
}

.employee-hero__mesh {
  background:
    linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px),
    linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
  background-size: 28px 28px;
  mask-image: linear-gradient(135deg, rgba(0, 0, 0, 0.24), transparent 78%);
}

.employee-hero__glow {
  background:
    radial-gradient(circle at 85% 20%, rgba(201, 210, 243, 0.22), transparent 22%),
    radial-gradient(circle at 65% 100%, rgba(var(--employee-primary-rgb), 0.16), transparent 24%);
}

.employee-hero__status-row,
.employee-hero__content,
.employee-summary-grid {
  position: relative;
  z-index: 1;
}

.employee-hero__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.employee-hero__status-row {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.employee-status-badge,
.employee-substatus-badge {
  width: fit-content;
  max-width: 100%;
  padding: 0.42rem 0.8rem;
  border-radius: 999px;
  font-weight: 700;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.employee-hero__content {
  display: block;
}

.employee-hero__main {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  width: 100%;
}

.employee-eyebrow,
.employee-panel__eyebrow,
.employee-hero-card__eyebrow {
  display: inline-block;
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.employee-eyebrow {
  color: rgba(255, 255, 255, 0.76);
}

.employee-hero__title-group {
  display: flex;
  align-items: center;
  gap: 0.95rem;
}

.employee-hero__icon {
  width: 62px;
  height: 62px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #ffffff;
  font-size: 1.65rem;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.22);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.16);
}

.employee-hero__copy {
  min-width: 0;
  flex: 1;
}

.employee-hero__title {
  margin: 0;
  font-size: clamp(1.5rem, 2.5vw, 1.45rem);
  font-weight: 800;
  letter-spacing: -0.04em;
  color: #ffffff;
}

.employee-hero__subtitle {
  max-width: none;
  margin: 0.35rem 0 0;
  color: rgba(255, 255, 255, 0.86);
  font-size: 0.96rem;
  line-height: 1.55;
}

.employee-hero__meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0.7rem;
}

.employee-meta-chip {
  display: flex;
  align-items: flex-start;
  gap: 0.72rem;
  padding: 0.8rem 0.9rem;
  border-radius: 20px;
  color: #ffffff;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(12px);
}

.employee-meta-chip i {
  flex-shrink: 0;
  margin-top: 0.1rem;
  font-size: 1rem;
}

.employee-meta-chip__content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.employee-meta-chip__label {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.68);
}

.employee-meta-chip__value {
  margin-top: 0.18rem;
  font-size: 0.96rem;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.35;
  word-break: break-word;
}

.employee-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.8rem;
  margin-top: 1rem;
}

.employee-summary-card,
.employee-panel {
  position: relative;
  background: var(--employee-surface);
  border-radius: 24px;
  border: 1px solid var(--employee-border);
  box-shadow: var(--employee-panel-shadow);
}

.employee-summary-card {
  overflow: hidden;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.42rem;
  min-height: 136px;
}

.employee-summary-card::before {
  content: "";
  position: absolute;
  inset: 0 0 auto;
  height: 4px;
}

.employee-summary-card__icon {
  width: 44px;
  height: 44px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.18rem;
}

.employee-summary-card__label {
  margin-top: 0.15rem;
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.11em;
  text-transform: uppercase;
  color: var(--employee-muted);
}

.employee-summary-card__value {
  font-size: 1.45rem;
  font-weight: 800;
  line-height: 1.1;
  color: var(--employee-secondary);
}

.employee-summary-card__hint {
  color: var(--employee-muted);
  font-size: 0.86rem;
  line-height: 1.45;
}

.employee-summary-card--blue::before {
  background: linear-gradient(
    90deg,
    var(--employee-primary-strong),
    var(--employee-primary)
  );
}

.employee-summary-card--blue .employee-summary-card__icon {
  color: var(--employee-primary);
  background: rgba(var(--employee-primary-rgb), 0.12);
}

.employee-summary-card--teal::before {
  background: linear-gradient(
    90deg,
    var(--employee-primary),
    var(--employee-primary-light)
  );
}

.employee-summary-card--teal .employee-summary-card__icon {
  color: var(--employee-primary-light);
  background: rgba(var(--employee-primary-rgb), 0.16);
}

.employee-summary-card--amber::before {
  background: linear-gradient(90deg, #5869a7, #7d8dc7);
}

.employee-summary-card--amber .employee-summary-card__icon {
  color: #5869a7;
  background: rgba(var(--employee-primary-rgb), 0.14);
}

.employee-summary-card--slate::before {
  background: linear-gradient(90deg, #394671, #57679f);
}

.employee-summary-card--slate .employee-summary-card__icon {
  color: #394671;
  background: rgba(var(--employee-primary-rgb), 0.12);
}

.employee-overview-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
  gap: 0.9rem;
}

.employee-panel {
  overflow: hidden;
  background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
}

.employee-panel--statistics,
.employee-panel--personnel {
  display: flex;
  flex-direction: column;
}

.employee-panel__header {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  padding: 0.88rem 0.95rem 0.82rem;
  border-bottom: 1px solid rgba(226, 232, 240, 0.85);
}

.employee-panel__header--stack {
  align-items: center;
}

.employee-panel__header--split {
  justify-content: space-between;
  gap: 1rem;
}

.employee-panel__header-group {
  display: flex;
  align-items: flex-start;
  gap: 0.8rem;
  min-width: 0;
}

.employee-panel__header-copy {
  min-width: 0;
}

.employee-panel__header-icon {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1.2rem;
  color: var(--employee-primary);
  background: linear-gradient(
    180deg,
    rgba(var(--employee-primary-rgb), 0.12) 0%,
    rgba(var(--employee-primary-rgb), 0.2) 100%
  );
}

.employee-panel__title {
  margin: 0;
  font-size: 1.06rem;
  font-weight: 800;
  color: var(--employee-secondary);
}

.employee-panel__subtitle {
  margin: 0.18rem 0 0;
  color: var(--employee-muted);
  font-size: 0.8rem;
  line-height: 1.35;
}

.employee-metrics-grid {
  padding: 0.82rem 0.95rem 0.9rem;
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.55rem;
}

.employee-metric-card {
  display: flex;
  align-items: center;
  gap: 0.68rem;
  padding: 0.72rem 0.8rem;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.12);
}

.employee-metric-card__icon {
  width: 38px;
  height: 38px;
  border-radius: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #ffffff;
  font-size: 0.88rem;
  box-shadow: 0 8px 18px rgba(var(--employee-primary-rgb), 0.16);
}

.employee-metric-card__copy {
  min-width: 0;
  flex: 1;
}

.employee-metric-card__top {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.6rem;
}

.employee-metric-card__label {
  display: inline-block;
  color: var(--employee-muted);
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.employee-metric-card__value {
  display: inline-block;
  color: var(--employee-secondary);
  font-size: 1rem;
  font-weight: 800;
  line-height: 1;
  white-space: nowrap;
}

.employee-metric-card__hint {
  display: block;
  margin-top: 0.1rem;
  color: var(--employee-muted);
  font-size: 0.78rem;
  line-height: 1.28;
}

.employee-metric-card--blue {
  background: linear-gradient(135deg, #f7f8fd 0%, #edf1fb 100%);
}

.employee-metric-card--blue .employee-metric-card__icon {
  background: linear-gradient(
    135deg,
    var(--employee-primary),
    var(--employee-primary-light)
  );
}

.employee-metric-card--amber {
  background: linear-gradient(135deg, #f8f9fe 0%, #eef1fa 100%);
}

.employee-metric-card--amber .employee-metric-card__icon {
  background: linear-gradient(135deg, #5869a7, #7b8cc8);
}

.employee-metric-card--rose {
  background: linear-gradient(135deg, #f4f6fc 0%, #eaedf8 100%);
}

.employee-metric-card--rose .employee-metric-card__icon {
  background: linear-gradient(
    135deg,
    var(--employee-primary-strong),
    var(--employee-primary)
  );
}

.employee-personnel-list {
  padding: 0.82rem 0.95rem 0.9rem;
  display: grid;
  gap: 0.55rem;
}

.employee-personnel-item {
  display: flex;
  align-items: center;
  gap: 0.68rem;
  padding: 0.72rem 0.8rem;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.12);
}

.employee-personnel-item--blue {
  background: linear-gradient(135deg, #f7f8fd 0%, #edf1fb 100%);
}

.employee-personnel-item--amber {
  background: linear-gradient(135deg, #f8f9fe 0%, #eef1fa 100%);
}

.employee-personnel-item--emerald {
  background: linear-gradient(135deg, #f4f6fc 0%, #eaedf8 100%);
}

.employee-personnel-item--empty {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.employee-personnel-item__avatar {
  width: 38px;
  height: 38px;
  border-radius: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #ffffff;
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  box-shadow: 0 8px 18px rgba(var(--employee-primary-rgb), 0.16);
}

.employee-personnel-item--blue .employee-personnel-item__avatar {
  background: linear-gradient(
    135deg,
    var(--employee-primary),
    var(--employee-primary-light)
  );
}

.employee-personnel-item--amber .employee-personnel-item__avatar {
  background: linear-gradient(135deg, #5869a7, #7b8cc8);
}

.employee-personnel-item--emerald .employee-personnel-item__avatar {
  background: linear-gradient(
    135deg,
    var(--employee-primary-strong),
    var(--employee-primary)
  );
}

.employee-personnel-item--empty .employee-personnel-item__avatar {
  background: linear-gradient(135deg, #64748b, #94a3b8);
  box-shadow: none;
}

.employee-personnel-item__copy {
  min-width: 0;
  flex: 1;
}

.employee-personnel-item__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.45rem;
}

.employee-personnel-item__role {
  display: block;
  color: var(--employee-muted);
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.employee-personnel-item__symbol {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 999px;
  color: var(--employee-secondary);
  background: rgba(255, 255, 255, 0.72);
  font-size: 0.74rem;
}

.employee-personnel-item__name {
  display: block;
  margin-top: 0.08rem;
  color: var(--employee-secondary);
  font-size: 0.88rem;
  font-weight: 800;
  line-height: 1.25;
  word-break: break-word;
}

.employee-personnel-item--empty .employee-personnel-item__name {
  color: var(--employee-muted);
}

.employee-pap-grid {
  padding: 1rem 1.05rem 1.05rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0.75rem;
}

.employee-pap-card {
  padding: 0.95rem 1rem;
  border-radius: 20px;
  background: linear-gradient(180deg, #fbfdff 0%, #f5f8ff 100%);
  border: 1px solid rgba(148, 163, 184, 0.12);
}

.employee-pap-card__eyebrow {
  display: block;
  color: var(--employee-muted);
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.employee-pap-card__value {
  display: block;
  margin-top: 0.28rem;
  color: var(--employee-secondary);
  font-size: 1rem;
  font-weight: 800;
  line-height: 1.35;
}

.employee-pap-card__text {
  margin: 0.32rem 0 0;
  color: var(--employee-muted);
  font-size: 0.9rem;
  line-height: 1.5;
}

.employee-empty-inline {
  padding: 1rem 1.05rem 1.1rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  color: var(--employee-muted);
}

.employee-empty-inline__icon {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--employee-primary);
  font-size: 1.2rem;
  background: rgba(var(--employee-primary-rgb), 0.12);
}

.employee-empty-inline__title {
  display: block;
  color: var(--employee-secondary);
  font-size: 0.95rem;
  font-weight: 800;
}

.employee-empty-inline__text {
  color: var(--employee-muted);
  font-size: 0.88rem;
  line-height: 1.45;
}

.employee-table-summary {
  display: flex;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.employee-table-summary__item {
  min-width: 110px;
  padding: 0.55rem 0.78rem;
  border-radius: 16px;
  background: linear-gradient(180deg, #f7f8fd 0%, #eef2fa 100%);
  border: 1px solid rgba(148, 163, 184, 0.12);
}

.employee-table-summary__label {
  display: block;
  color: var(--employee-muted);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.employee-table-summary__value {
  display: block;
  margin-top: 0.12rem;
  color: var(--employee-secondary);
  font-size: 1rem;
  font-weight: 800;
}

.employee-table-shell {
  padding: 1rem 1.05rem 1.05rem;
}

.employee-table-responsive {
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 22px;
  overflow: hidden;
}

.employee-items-table {
  width: 100%;
  min-width: 980px;
  border-collapse: separate;
  border-spacing: 0;
}

.employee-items-table thead {
  color: #ffffff;
}

.employee-items-table th {
  padding: 0.86rem 0.8rem;
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  background: linear-gradient(
    135deg,
    var(--employee-primary-strong) 0%,
    var(--employee-primary) 100%
  );
}

.employee-items-table td {
  padding: 0.9rem 0.8rem;
  vertical-align: top;
  border-bottom: 1px solid #e2e8f0;
  background: #ffffff;
}

.employee-items-table tbody tr:nth-child(even) td {
  background: #fcfdff;
}

.employee-items-table tbody tr:hover td {
  background: #f7fbff;
}

.employee-items-table tbody tr:last-child td {
  border-bottom: 0;
}

.employee-row-number {
  color: var(--employee-primary);
  font-weight: 800;
}

.employee-item-status-badge {
  padding: 0.4rem 0.68rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
}

.employee-item-description {
  min-width: 230px;
  color: #334155;
}

.employee-item-title {
  color: var(--employee-secondary);
  font-size: 0.97rem;
  font-weight: 800;
  line-height: 1.34;
}

.employee-item-richtext {
  display: block;
  margin-top: 0.28rem;
  color: #475569;
  font-size: 0.86rem;
  line-height: 1.5;
}

.employee-item-richtext :deep(p) {
  margin-bottom: 0.35rem;
}

.employee-item-richtext :deep(ul),
.employee-item-richtext :deep(ol) {
  margin-bottom: 0.35rem;
  padding-left: 1.1rem;
}

.employee-item-quantity,
.employee-item-unit {
  color: var(--employee-secondary);
  font-weight: 700;
}

.employee-po-stack {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
}

.employee-po-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
}

.employee-po-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.3rem 0.62rem;
  border-radius: 999px;
  background: rgba(var(--employee-primary-rgb), 0.12);
  color: var(--employee-primary-strong);
  font-size: 0.8rem;
  font-weight: 800;
  white-space: nowrap;
}

.employee-po-print-btn {
  width: 1.85rem;
  height: 1.85rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border-radius: 999px;
  color: var(--employee-primary-strong);
  border-color: rgba(var(--employee-primary-rgb), 0.28);
  background: rgba(var(--employee-primary-rgb), 0.06);
}

.employee-po-print-btn:hover {
  color: #ffffff;
  background: var(--employee-primary);
  border-color: var(--employee-primary);
}

.employee-po-date {
  color: #334155;
  font-size: 0.82rem;
  font-weight: 700;
  white-space: nowrap;
}

.employee-empty-state {
  padding: 2.2rem 1rem 2.4rem;
  text-align: center;
}

.employee-empty-state__icon {
  width: 80px;
  height: 80px;
  border-radius: 26px;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: var(--employee-muted);
  background: linear-gradient(135deg, #f8f9fd, #eef1fa);
}

.employee-empty-state__title {
  margin-bottom: 0.35rem;
  font-size: 1.18rem;
  font-weight: 800;
  color: var(--employee-secondary);
}

.employee-empty-state__text {
  margin: 0;
  color: var(--employee-muted);
}

[data-bs-theme="dark"] .employee-detail-view {
  --employee-primary: #334a86;
  --employee-primary-strong: #111827;
  --employee-primary-light: #5269b7;
  --employee-primary-rgb: 82, 105, 183;
  --employee-secondary: #e5edf7;
  --employee-muted: #9fb0c7;
  --employee-border: rgba(148, 163, 184, 0.18);
  --employee-surface: #1b2230;
  --employee-surface-alt: linear-gradient(180deg, #202937 0%, #1a2230 100%);
  --employee-panel-shadow: none;
}

[data-bs-theme="dark"] .employee-detail-view .employee-substatus-badge {
  background: rgba(15, 23, 42, 0.28) !important;
  color: #e5edf7 !important;
  border: 1px solid rgba(148, 163, 184, 0.24);
}

[data-bs-theme="dark"] .employee-detail-view .employee-pap-card,
[data-bs-theme="dark"] .employee-detail-view .employee-table-summary__item {
  background: linear-gradient(180deg, #232c3a 0%, #1d2531 100%);
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .employee-detail-view .employee-table-responsive {
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .employee-detail-view .employee-items-table td {
  border-bottom-color: rgba(148, 163, 184, 0.14);
  background: #1b2230;
}

[data-bs-theme="dark"] .employee-detail-view .employee-items-table tbody tr:nth-child(even) td {
  background: #202937;
}

[data-bs-theme="dark"] .employee-detail-view .employee-items-table tbody tr:hover td {
  background: rgba(59, 130, 246, 0.08);
}

[data-bs-theme="dark"] .employee-detail-view .employee-item-description,
[data-bs-theme="dark"] .employee-detail-view .employee-item-richtext,
[data-bs-theme="dark"] .employee-detail-view .employee-po-date {
  color: #cbd5e1;
}

[data-bs-theme="dark"] .employee-detail-view .employee-po-print-btn {
  color: #dbeafe;
  border-color: rgba(147, 197, 253, 0.32);
  background: rgba(147, 197, 253, 0.08);
}

[data-bs-theme="dark"] .employee-detail-view .employee-po-print-btn:hover {
  color: #ffffff;
  background: var(--employee-primary);
  border-color: var(--employee-primary);
}

[data-bs-theme="dark"] .employee-detail-view .employee-empty-state__icon {
  background: linear-gradient(135deg, #232c3a, #1d2531);
}

@media (max-width: 1400px) {
  .employee-summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .employee-metrics-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1200px) {
  .employee-overview-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 992px) {
  .employee-panel__header--split {
    flex-direction: column;
    align-items: flex-start;
  }

  .employee-hero__header {
    align-items: flex-start;
  }

  .employee-hero__status-row {
    justify-content: flex-start;
  }
}

@media (max-width: 768px) {
  .employee-detail-view {
    gap: 0.8rem;
  }

  .employee-hero {
    padding: 0.85rem;
    border-radius: 24px;
  }

  .employee-hero__header {
    align-items: flex-start;
  }

  .employee-hero__title-group {
    flex-direction: column;
    align-items: flex-start;
  }

  .employee-hero__icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    font-size: 1.45rem;
  }

  .employee-hero__meta,
  .employee-summary-grid {
    grid-template-columns: 1fr;
  }

  .employee-panel__header,
  .employee-metrics-grid,
  .employee-personnel-list,
  .employee-pap-grid,
  .employee-empty-inline,
  .employee-table-shell {
    padding-left: 0.85rem;
    padding-right: 0.85rem;
  }

  .employee-personnel-item {
    align-items: flex-start;
  }
}

@media (max-width: 576px) {
  .employee-hero__subtitle {
    font-size: 0.9rem;
  }

  .employee-meta-chip {
    padding: 0.72rem 0.8rem;
  }

  .employee-empty-inline {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>

<style>
[data-bs-theme="dark"] .employee-detail-view {
  --employee-primary: #334a86;
  --employee-primary-strong: #111827;
  --employee-primary-light: #5269b7;
  --employee-primary-rgb: 82, 105, 183;
  --employee-secondary: #e5edf7;
  --employee-muted: #9fb0c7;
  --employee-border: rgba(148, 163, 184, 0.18);
  --employee-surface: #1b2230;
  --employee-surface-alt: linear-gradient(180deg, #202937 0%, #1a2230 100%);
  --employee-panel-shadow: none;
}

[data-bs-theme="dark"] .employee-detail-view .employee-hero {
  background:
    radial-gradient(circle at 15% 12%, rgba(147, 197, 253, 0.16), transparent 28%),
    radial-gradient(circle at 100% 0%, rgba(82, 105, 183, 0.2), transparent 30%),
    linear-gradient(135deg, #111827 0%, #182235 52%, #273961 100%);
  border: 1px solid rgba(148, 163, 184, 0.18);
  box-shadow: none;
}

[data-bs-theme="dark"] .employee-detail-view .employee-hero__icon {
  background: rgba(15, 23, 42, 0.28);
  border-color: rgba(191, 219, 254, 0.18);
}

[data-bs-theme="dark"] .employee-detail-view .employee-hero__mesh {
  background:
    linear-gradient(90deg, rgba(191, 219, 254, 0.05) 1px, transparent 1px),
    linear-gradient(rgba(191, 219, 254, 0.05) 1px, transparent 1px);
}

[data-bs-theme="dark"] .employee-detail-view .employee-hero__glow {
  background:
    radial-gradient(circle at 86% 16%, rgba(147, 197, 253, 0.12), transparent 24%),
    radial-gradient(circle at 64% 100%, rgba(59, 130, 246, 0.1), transparent 26%);
}

[data-bs-theme="dark"] .employee-detail-view .employee-meta-chip {
  background: rgba(15, 23, 42, 0.42);
  border-color: rgba(191, 219, 254, 0.14);
}

[data-bs-theme="dark"] .employee-detail-view .employee-panel {
  background: linear-gradient(180deg, #1b2230 0%, #18212e 100%);
  border-color: rgba(148, 163, 184, 0.18);
  box-shadow: none;
}

[data-bs-theme="dark"] .employee-detail-view .employee-panel__header {
  border-bottom-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .employee-detail-view .employee-metric-card,
[data-bs-theme="dark"] .employee-detail-view .employee-personnel-item {
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .employee-detail-view .employee-metric-card--blue,
[data-bs-theme="dark"] .employee-detail-view .employee-metric-card--amber,
[data-bs-theme="dark"] .employee-detail-view .employee-metric-card--rose,
[data-bs-theme="dark"] .employee-detail-view .employee-personnel-item--blue,
[data-bs-theme="dark"] .employee-detail-view .employee-personnel-item--amber,
[data-bs-theme="dark"] .employee-detail-view .employee-personnel-item--emerald,
[data-bs-theme="dark"] .employee-detail-view .employee-personnel-item--empty {
  background: linear-gradient(135deg, #232c3a 0%, #1d2531 100%);
}

[data-bs-theme="dark"] .employee-detail-view .employee-empty-inline__icon {
  background: rgba(59, 130, 246, 0.12);
}

[data-bs-theme="dark"] .employee-detail-view .employee-substatus-badge {
  background: rgba(15, 23, 42, 0.28) !important;
  color: #e5edf7 !important;
  border: 1px solid rgba(148, 163, 184, 0.24);
}

[data-bs-theme="dark"] .employee-detail-view .employee-pap-card,
[data-bs-theme="dark"] .employee-detail-view .employee-table-summary__item {
  background: linear-gradient(180deg, #232c3a 0%, #1d2531 100%);
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .employee-detail-view .employee-table-responsive {
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .employee-detail-view .employee-items-table td {
  border-bottom-color: rgba(148, 163, 184, 0.14);
  background: #1b2230;
}

[data-bs-theme="dark"] .employee-detail-view .employee-items-table tbody tr:nth-child(even) td {
  background: #202937;
}

[data-bs-theme="dark"] .employee-detail-view .employee-items-table tbody tr:hover td {
  background: rgba(59, 130, 246, 0.08);
}

[data-bs-theme="dark"] .employee-detail-view .employee-item-description,
[data-bs-theme="dark"] .employee-detail-view .employee-item-richtext,
[data-bs-theme="dark"] .employee-detail-view .employee-po-date {
  color: #cbd5e1;
}

[data-bs-theme="dark"] .employee-detail-view .employee-po-print-btn {
  color: #dbeafe;
  border-color: rgba(147, 197, 253, 0.32);
  background: rgba(147, 197, 253, 0.08);
}

[data-bs-theme="dark"] .employee-detail-view .employee-po-print-btn:hover {
  color: #ffffff;
  background: var(--employee-primary);
  border-color: var(--employee-primary);
}

[data-bs-theme="dark"] .employee-detail-view .employee-empty-state__icon {
  background: linear-gradient(135deg, #232c3a, #1d2531);
}
</style>
