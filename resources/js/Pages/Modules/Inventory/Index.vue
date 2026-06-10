<template>
  <div class="inventory-page">
    <Head title="Inventory Management" />
    <PageHeader title="Inventory Management" pageTitle="Inventory" />

    <!-- Enhanced Hero -->
    <section class="inv-hero mb-3">
      <!-- Decorative background elements -->
      <div class="inv-hero-deco" aria-hidden="true">
        <div class="inv-hero-deco-ring inv-hero-deco-ring--1"></div>
        <div class="inv-hero-deco-ring inv-hero-deco-ring--2"></div>
        <div class="inv-hero-deco-grid"></div>
      </div>

      <div class="inv-hero-body">
        <!-- Left: identity + actions -->
        <div class="inv-hero-left">
          <div class="inv-hero-kicker">
            <i class="ri-store-3-line"></i>
            <span>Inventory Management</span>
          </div>
          <h2 class="inv-hero-title">Inventory<br /></h2>
          <p class="inv-hero-desc">
            Catalog items, manage stocks, track receivings, withdrawals and issue slips —
            all from one workspace.
          </p>

          <div class="inv-hero-actions">

            <Link href="/inventory-dashboard" class="inv-hero-btn-primary">
              <i class="ri-bar-chart-box-line"></i>
              <span>Dashboard</span>
            </Link>
          </div>

          <!-- Quick-links strip -->
          <div class="inv-hero-quicklinks">
            <button
              v-for="mod in modules"
              :key="mod.key"
              class="inv-hero-ql"
              :class="{ active: activeModule === mod.key }"
              @click="activeModule = mod.key"
            >
              <i :class="mod.icon"></i>
              <span>{{ mod.label }}</span>
            </button>
          </div>
        </div>

        <!-- Right: stat cards 2×2 -->
        <div class="inv-hero-stats">
          <div
            v-for="card in inventoryHeroCards"
            :key="card.label"
            class="inv-hero-stat"
            :style="{ '--stat-accent': card.accent }"
          >
            <div class="inv-hero-stat-top">
              <div class="inv-hero-stat-icon">
                <i :class="card.icon"></i>
              </div>
              <strong class="inv-hero-stat-val">{{ card.value }}</strong>
            </div>
            <span class="inv-hero-stat-lbl">{{ card.label }}</span>
            <div class="inv-hero-stat-bar"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- Module Shell -->
    <div class="inv-shell">
      <!-- Tab Navigation -->
      <nav class="inv-tab-nav">
        <button
          v-for="mod in modules"
          :key="mod.key"
          type="button"
          class="inv-tab"
          :class="{ active: activeModule === mod.key }"
          @click="activeModule = mod.key"
        >
          <i :class="mod.icon"></i>
          <span>{{ mod.label }}</span>
          <em class="inv-tab-count">{{ moduleMeta(mod.key) }}</em>
        </button>
      </nav>

      <!-- Content Area -->
      <div class="inv-tab-content">
        <div class="module-content">
          <div v-if="activeModule === 'items'" class="inv-module-card">
            <!-- Unified Header -->
            <div class="inv-module-header">
              <div class="inv-module-header-left">
                <div class="inv-module-header-icon">
                  <i class="ri-store-3-line"></i>
                </div>
                <div>
                  <h5 class="inv-module-title">Inventory Items</h5>
                  <p class="inv-module-subtitle">
                    {{ itemMeta?.total ?? itemRows.length }} items · catalog with stock
                    quantities
                  </p>
                </div>
              </div>
              <div class="inv-module-header-actions">
                <div class="inv-toolbar">
                  <div class="inv-search-wrap">
                    <i class="ri-search-line inv-search-icon"></i>
                    <input
                      v-model="itemKeyword"
                      type="text"
                      placeholder="Search items…"
                      class="inv-search-input"
                    />
                    <button
                      v-if="itemKeyword"
                      class="inv-search-clear"
                      @click="itemKeyword = ''"
                    >
                      <i class="ri-close-line"></i>
                    </button>
                  </div>
                  <select
                    v-model="itemCategoryFilter"
                    class="inv-select"
                    style="min-width: 148px"
                  >
                    <option value="">All Categories</option>
                    <option
                      v-for="cat in categoryRows"
                      :key="cat.id"
                      :value="String(cat.id)"
                    >
                      {{ cat.name }}
                    </option>
                  </select>
                  <select v-model="itemSort" class="inv-select">
                    <option value="latest">Latest</option>
                    <option value="oldest">Oldest</option>
                    <option value="name_asc">Name A–Z</option>
                    <option value="name_desc">Name Z–A</option>
                  </select>
                  <button
                    class="inv-icon-btn"
                    title="Refresh"
                    v-b-tooltip.hover
                    @click="handleItemRefresh"
                  >
                    <i class="ri-refresh-line"></i>
                  </button>
                  <div class="inv-view-toggle">
                    <button
                      :class="{ active: itemViewMode === 'list' }"
                      @click="itemViewMode = 'list'"
                      title="List"
                    >
                      <i class="ri-list-check-2"></i>
                    </button>
                    <button
                      :class="{ active: itemViewMode === 'grid' }"
                      @click="itemViewMode = 'grid'"
                      title="Grid"
                    >
                      <i class="ri-layout-grid-line"></i>
                    </button>
                  </div>
                  <button class="inv-create-btn" @click="openItemCreate">
                    <i class="ri-add-line"></i> Add Item
                  </button>
                </div>
                <div v-if="itemSort === 'custom'" class="inv-custom-order-note">
                  <i class="ri-drag-move-2-line me-1"></i>Use arrows to reorder
                </div>
              </div>
            </div>

            <!-- List -->
            <div v-if="itemViewMode === 'list'">
              <div class="inv-table-shell">
                <table class="inv-table">
                  <thead>
                    <tr>
                      <th style="width: 36px"></th>
                      <th style="width: 80px" class="text-center">Order</th>
                      <th style="width: 130px">Code</th>
                      <th>Name</th>
                      <th style="width: 150px">Category</th>
                      <th style="width: 110px" class="text-center">Stock Qty</th>
                      <th style="width: 160px" class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="loading" class="inv-table-empty">
                      <td colspan="7">
                        <div class="inv-loading-state">
                          <div class="inv-spinner"></div>
                          <span>Loading...</span>
                        </div>
                      </td>
                    </tr>
                    <tr v-else-if="sortedItemRows.length === 0" class="inv-table-empty">
                      <td colspan="7">
                        <div class="inv-empty-state">
                          <i class="ri-inbox-line"></i>
                          <p>No items yet.</p>
                      
                        </div>
                      </td>
                    </tr>
                    <template
                      v-else
                      v-for="(item, index) in sortedItemRows"
                      :key="item.id"
                    >
                      <tr
                        class="inv-table-row"
                        :class="{ 'item-row-open': isItemExpanded(item.id) }"
                        style="cursor: pointer"
                        @click="toggleItemExpand(item)"
                      >
                        <!-- Expand chevron (visual only, click handled by row) -->
                        <td class="text-center" style="padding: 0.4rem">
                          <span
                            class="item-expand-btn"
                            :class="{ active: isItemExpanded(item.id) }"
                          >
                            <i
                              class="ri-arrow-right-s-line item-expand-icon"
                              :class="{ rotated: isItemExpanded(item.id) }"
                            ></i>
                          </span>
                        </td>
                        <!-- Order controls — stop row click from firing -->
                        <td class="text-center" @click.stop>
                          <div class="inventory-order-controls">
                            <button
                              type="button"
                              class="btn btn-sm btn-light inventory-order-btn"
                              :disabled="index === 0"
                              @click="moveItemRow(item, -1)"
                            >
                              <i class="ri-arrow-up-s-line"></i>
                            </button>
                            <span class="inventory-order-number">{{
                              displayItemNumber(index)
                            }}</span>
                            <button
                              type="button"
                              class="btn btn-sm btn-light inventory-order-btn"
                              :disabled="index === sortedItemRows.length - 1"
                              @click="moveItemRow(item, 1)"
                            >
                              <i class="ri-arrow-down-s-line"></i>
                            </button>
                          </div>
                        </td>
                        <td>
                          <span class="inv-code-chip">{{ item.code || "&#8212;" }}</span>
                        </td>
                        <td class="fw-semibold">{{ item.name }}</td>
                        <td>
                          <span v-if="item.category" class="inv-count-badge">{{
                            item.category
                          }}</span
                          ><span v-else class="inv-muted-val">&#8212;</span>
                        </td>
                        <td class="text-center fw-bold">
                          {{
                            item.total_quantity != null
                              ? formatNumber(item.total_quantity)
                              : "&#8212;"
                          }}
                          <span
                            v-if="item.stock_count > 0"
                            class="inv-stock-badge ms-1"
                            :title="`${item.stock_count} stock entr${
                              item.stock_count === 1 ? 'y' : 'ies'
                            }`"
                            >{{ item.stock_count }}</span
                          >
                        </td>
                        <td class="text-center" @click.stop>
                          <div class="inv-row-actions">
                            <button
                              class="inv-action-btn view"
                              title="View"
                              v-b-tooltip.hover
                              @click="openViewModal('item', item)"
                            >
                              <i class="ri-eye-line"></i>
                            </button>
                            <button
                              class="inv-action-btn edit"
                              title="Edit"
                              v-b-tooltip.hover
                              @click="openItemEdit(item)"
                            >
                              <i class="ri-pencil-line"></i>
                            </button>
                            <button
                              class="inv-action-btn stock"
                              title="Add Stock"
                              v-b-tooltip.hover
                              @click="openStockCreate(item)"
                            >
                              <i class="ri-add-box-line"></i>
                            </button>
                            <button
                              v-if="!item.stock_count"
                              class="inv-action-btn del"
                              title="Delete"
                              v-b-tooltip.hover
                              @click.stop="confirmDeleteItem(item)"
                            >
                              <i class="ri-delete-bin-line"></i>
                            </button>
                          </div>
                        </td>
                      </tr>

                      <!-- ── Expanded stocks sub-row ─────────────────────── -->
                      <tr v-if="isItemExpanded(item.id)" class="item-sub-row">
                        <td colspan="7" class="p-0">
                          <div class="item-stocks-panel">
                            <!-- Panel header -->
                            <div class="isp-header">
                              <div class="isp-header-left">
                                <span class="isp-header-icon"
                                  ><i class="ri-stack-line"></i
                                ></span>
                                <div>
                                  <span class="isp-header-title">Stock Entries</span>
                                  <span class="isp-header-sub">{{ item.name }}</span>
                                </div>
                              </div>
                              <div class="isp-header-right">
                                <template v-if="itemStocksCache[item.id]?.length">
                                  <span class="isp-summary-chip">
                                    <i class="ri-archive-line"></i>
                                    {{ itemStocksCache[item.id].length }} entr{{
                                      itemStocksCache[item.id].length === 1 ? "y" : "ies"
                                    }}
                                  </span>
                                  <span class="isp-summary-chip green">
                                    <i class="ri-money-dollar-circle-line"></i>
                                    ₱{{
                                      formatNumber(
                                        itemStocksCache[item.id].reduce(
                                          (s, r) =>
                                            s +
                                            Number(r.quantity || 0) *
                                              Number(r.unit_cost || 0),
                                          0
                                        )
                                      )
                                    }}
                                  </span>
                                </template>
                                <button
                                  class="isp-add-btn"
                                  @click.stop="openStockCreate(item)"
                                >
                                  <i class="ri-add-line"></i> Add Stock
                                </button>
                              </div>
                            </div>

                            <!-- Loading -->
                            <div v-if="itemStocksLoading[item.id]" class="isp-loading">
                              <div class="isp-spinner"></div>
                              <span>Loading stock entries…</span>
                            </div>

                            <!-- Empty -->
                            <div
                              v-else-if="!itemStocksCache[item.id]?.length"
                              class="isp-empty"
                            >
                              <div class="isp-empty-icon">
                                <i class="ri-inbox-line"></i>
                              </div>
                              <p class="isp-empty-title">No stock entries yet</p>
                              <p class="isp-empty-sub">
                                Click <strong>Add Stock</strong> to record the first entry
                                for this item.
                              </p>
                            </div>

                            <!-- Stocks table -->
                            <template v-else>
                              <div class="isp-table-wrap">
                                <table class="isp-table">
                                  <thead>
                                    <tr>
                                      <th class="text-center" style="width: 36px">#</th>
                                      <th style="width: 200px">Qty / Unit</th>
                                      <th class="text-center" style="width: 120px">
                                        Unit Cost
                                      </th>
                                      <th class="text-end" style="width: 140px">
                                        Total Value
                                      </th>
                                      <th class="text-center" style="width: 115px">
                                        Date Added
                                      </th>
                                      <th style="min-width: 140px">Description</th>
                                      <th class="text-center" style="width: 80px">
                                        Actions
                                      </th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr
                                      v-for="(stock, si) in itemStocksCache[item.id]"
                                      :key="stock.id"
                                      class="isp-row"
                                    >
                                      <td class="text-center">
                                        <span class="isp-row-num">{{ si + 1 }}</span>
                                      </td>
                                      <td>
                                        <div class="isp-qty-cell">
                                          <span class="isp-qty-val">{{
                                            formatNumber(stock.quantity)
                                          }}</span>
                                          <span class="isp-unit-pill">{{
                                            stock.unit || "—"
                                          }}</span>
                                        </div>
                                        <span
                                          v-if="
                                            stock.unit_long &&
                                            stock.unit_long !== stock.unit
                                          "
                                          class="isp-unit-long"
                                          >{{ stock.unit_long }}</span
                                        >
                                      </td>
                                      <td class="text-center">
                                        <span class="isp-cost-val"
                                          >₱{{ formatNumber(stock.unit_cost) }}</span
                                        >
                                      </td>
                                      <td class="text-end">
                                        <span class="isp-total-val"
                                          >₱{{
                                            formatNumber(
                                              Number(stock.quantity) *
                                                Number(stock.unit_cost)
                                            )
                                          }}</span
                                        >
                                      </td>
                                      <td class="text-center">
                                        <span class="isp-date">
                                          <i class="ri-calendar-line me-1"></i
                                          >{{
                                            stock.created_at
                                              ? stock.created_at.slice(0, 10)
                                              : "—"
                                          }}
                                        </span>
                                      </td>
                                      <td>
                                        <span v-if="stock.description" class="isp-desc">{{
                                          stock.description
                                        }}</span>
                                        <span v-else class="isp-desc-empty">—</span>
                                      </td>
                                      <td class="text-center">
                                        <div class="inv-row-actions">
                                          <button
                                            class="inv-action-btn edit"
                                            title="Edit"
                                            v-b-tooltip.hover
                                            @click.stop="openStockEdit(stock)"
                                          >
                                            <i class="ri-pencil-line"></i>
                                          </button>
                                          <button
                                            class="inv-action-btn del"
                                            title="Delete"
                                            v-b-tooltip.hover
                                            @click.stop="removeExpandedStock(stock)"
                                          >
                                            <i class="ri-delete-bin-line"></i>
                                          </button>
                                        </div>
                                      </td>
                                    </tr>
                                  </tbody>
                                  <tfoot>
                                    <tr class="isp-totals-row">
                                      <td colspan="2" class="text-end">
                                        <span class="isp-totals-label"
                                          >Total across all entries</span
                                        >
                                      </td>
                                      <td></td>
                                      <td class="text-end">
                                        <span class="isp-totals-value">
                                          ₱{{
                                            formatNumber(
                                              itemStocksCache[item.id].reduce(
                                                (s, r) =>
                                                  s +
                                                  Number(r.quantity || 0) *
                                                    Number(r.unit_cost || 0),
                                                0
                                              )
                                            )
                                          }}
                                        </span>
                                      </td>
                                      <td colspan="3"></td>
                                    </tr>
                                  </tfoot>
                                </table>
                              </div>
                            </template>
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
              <!-- /inv-table-shell -->
              <div v-if="itemMeta && itemMeta.total" class="inv-pagination-bar">
                <Pagination
                  :links="itemLinks"
                  :pagination="itemMeta"
                  :lists="sortedItemRows.length"
                  @fetch="fetchItems"
                />
              </div>
            </div>
            <!-- Grid -->
            <div v-else class="inv-grid-shell">
              <div v-if="loading" class="inv-loading-state">
                <div class="inv-spinner"></div>
                <span>Loading...</span>
              </div>
              <div v-else-if="sortedItemRows.length === 0" class="inv-empty-state">
                <i class="ri-inbox-line"></i>
                <p>No items yet.</p>
              </div>
              <div v-else class="inv-grid">
                <div
                  v-for="(item, idx) in sortedItemRows"
                  :key="item.id"
                  class="inv-stock-card"
                >
                  <div
                    class="inv-stock-card-top"
                    :style="{ '--card-hue': [220, 262, 168, 32, 340, 195][idx % 6] }"
                  >
                    <div class="inv-stock-card-icon">
                      <i class="ri-barcode-box-line"></i>
                    </div>
                    <div class="inv-stock-card-actions">
                      <button
                        title="View"
                        v-b-tooltip.hover
                        @click="openViewModal('item', item)"
                      >
                        <i class="ri-eye-line"></i>
                      </button>
                      <button title="Edit" v-b-tooltip.hover @click="openItemEdit(item)">
                        <i class="ri-pencil-line"></i>
                      </button>
                      <button
                        title="Add Stock"
                        v-b-tooltip.hover
                        @click="openStockCreate(item)"
                      >
                        <i class="ri-add-box-line"></i>
                      </button>
                      <button
                        v-if="!item.stock_count"
                        class="del"
                        title="Delete"
                        v-b-tooltip.hover
                        @click="confirmDeleteItem(item)"
                      >
                        <i class="ri-delete-bin-line"></i>
                      </button>
                    </div>
                    <span class="inv-stock-card-code">{{ item.code || "&#8212;" }}</span>
                  </div>
                  <div class="inv-stock-card-body">
                    <h6 class="inv-stock-card-name">{{ item.name }}</h6>
                    <div class="mb-2">
                      <span class="inv-count-badge"
                        ><i class="ri-price-tag-3-line me-1"></i
                        >{{ item.category || "Uncategorized" }}</span
                      >
                    </div>
                    <div class="inv-stock-card-stats">
                      <div class="inv-stock-stat">
                        <span class="inv-stock-stat-val">{{
                          item.total_quantity != null
                            ? formatNumber(item.total_quantity)
                            : item.quantity != null
                            ? formatNumber(item.quantity)
                            : "&#8212;"
                        }}</span
                        ><span class="inv-stock-stat-lbl">Qty</span>
                      </div>
                      <div class="inv-stock-stat-sep"></div>
                      <div class="inv-stock-stat">
                        <span class="inv-stock-stat-val">{{
                          item.unit_cost != null
                            ? formatNumber(item.unit_cost)
                            : "&#8212;"
                        }}</span
                        ><span class="inv-stock-stat-lbl">Cost</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Stocks Module -->
          <Stocks
            v-else-if="activeModule === 'stocks'"
            :rows="stockRows"
            :loading="loading"
            :meta="stockMeta"
            :links="stockLinks"
            :keyword="stockKeyword"
            @create="openStockCreate()"
            @edit="openStockEdit"
            @delete="removeStock"
            @fetch="fetchStocks"
            @refresh="refreshStocks"
            @update:keyword="handleStockKeywordChange"
          />

          <!-- Categories Module -->
          <div
            v-else-if="activeModule === 'categories'"
            class="card bg-light-subtle shadow-none border ledger-card"
          >
            <div class="card-header bg-light-subtle">
              <div
                class="d-flex flex-wrap align-items-center justify-content-between gap-3"
              >
                <div class="d-flex align-items-center gap-3">
                  <span
                    class="avatar-title bg-primary-subtle rounded p-2"
                    style="width: 2.5rem; height: 2.5rem"
                  >
                    <i class="ri-price-tag-3-line text-primary fs-20"></i>
                  </span>
                  <div>
                    <h5 class="mb-0 fs-14">Inventory Categories</h5>
                    <p class="text-muted fs-12 mb-0">
                      Organize items into logical groups for filtering and reporting.
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  class="btn btn-primary btn-sm rounded-pill px-3"
                  @click="openCategoryCreate"
                >
                  <i class="ri-add-line me-1"></i>Add Category
                </button>
              </div>
            </div>
            <div class="card-body bg-white rounded-bottom" style="padding: 0.85rem">
              <div class="table-responsive inv-table-wrap">
                <table class="table table-hover align-middle mb-0 inv-table">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th class="text-center" style="width: 110px">Status</th>
                      <th class="text-center" style="width: 110px">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="categoriesLoading">
                      <td colspan="3" class="text-center text-muted py-4">
                        Loading categories...
                      </td>
                    </tr>
                    <tr v-else-if="categoryRows.length === 0">
                      <td colspan="3" class="text-center text-muted py-4">
                        No categories yet. Click <strong>Add Category</strong> to create
                        one.
                      </td>
                    </tr>
                    <tr v-else v-for="cat in categoryRows" :key="cat.id">
                      <td class="fw-semibold">{{ cat.name }}</td>
                      <td class="text-center">
                        <span
                          class="badge rounded-pill fw-semibold"
                          :class="
                            cat.is_active
                              ? 'bg-success-subtle text-success'
                              : 'bg-secondary-subtle text-secondary'
                          "
                        >
                          {{ cat.is_active ? "Active" : "Inactive" }}
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="d-inline-flex gap-1">
                          <button
                            class="btn btn-sm btn-outline-warning"
                            @click="openCategoryEdit(cat)"
                            title="Edit"
                            v-b-tooltip.hover
                          >
                            <i class="ri-pencil-line"></i>
                          </button>
                          <button
                            class="btn btn-sm btn-outline-danger"
                            @click="removeCategory(cat)"
                            title="Delete"
                            v-b-tooltip.hover
                          >
                            <i class="ri-delete-bin-line"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <ReceivingLedger
            v-else-if="activeModule === 'receivings'"
            :rows="receivingRows"
            :loading="loading"
            :meta="receivingMeta"
            :links="receivingLinks"
            @fetch="fetchReceivings"
            @refresh="() => fetchReceivings()"
            @view="openReceivingPoItems"
          />

          <WithdrawalLedger
            v-else-if="activeModule === 'withdrawals'"
            :rows="withdrawalRows"
            :loading="loading"
            :meta="withdrawalMeta"
            :links="withdrawalLinks"
            @create="openWithdrawalCreate"
            @fetch="fetchWithdrawals"
            @refresh="() => fetchWithdrawals()"
            @view="(row) => openViewModal('withdrawal', row)"
            @edit="openWithdrawalEdit"
            @delete="removeWithdrawal"
          />

          <!-- RIS Module -->
          <RisLedger
            v-else-if="activeModule === 'ris'"
            ref="risLedger"
            :rows="risRows"
            :loading="risLoading"
            :meta="risMeta"
            :links="risLinks"
            :items="itemOptionRows"
            :users="userOptions"
            :statuses="statusOptions"
            @create="openRisCreate"
            @fetch="fetchRis"
            @refresh="() => fetchRis()"
          />

          <!-- Report Module -->
          <ReportPanel
            v-else-if="activeModule === 'report'"
            :item-rows="itemRows"
            :item-meta="itemMeta"
            :stock-rows="stockRows"
            :stock-meta="stockMeta"
            :receiving-rows="receivingRows"
            :receiving-meta="receivingMeta"
            :withdrawal-rows="withdrawalRows"
            :withdrawal-meta="withdrawalMeta"
            :category-rows="categoryRows"
            :ris-rows="risRows"
            :ris-meta="risMeta"
          />
        </div>
      </div>
    </div>

    <StockModal
      v-model="showStockModal"
      :form="stockForm"
      :errors="stockErrors"
      :saving="saving"
      :items="itemOptionRows"
      :units="dropdowns.unitTypes || []"
      @update:form="stockForm = $event"
      @update:errors="stockErrors = $event"
      @submit="saveStock"
    />

    <ItemModal
      v-model="showItemModal"
      :form="itemForm"
      :errors="itemErrors"
      :saving="saving"
      :categories="categories"
      @update:form="itemForm = $event"
      @submit="saveItem"
    />

    <ReceivingModal
      v-model="showReceivingModal"
      :form="receivingForm"
      :errors="receivingErrors"
      :saving="saving"
      :items="itemOptionRows"
      :users="userOptions"
      :statuses="statusOptions"
      @update:form="receivingForm = $event"
      @submit="saveReceiving"
    />

    <WithdrawModal
      v-model="showWithdrawalModal"
      :form="withdrawalForm"
      :errors="withdrawalErrors"
      :saving="saving"
      :items="itemOptionRows"
      :users="userOptions"
      :statuses="statusOptions"
      @update:form="withdrawalForm = $event"
      @submit="saveWithdrawal"
    />

    <RecordViewModal
      :model-value="showViewModal"
      :type="viewRecordType"
      :record="viewRecord"
      :stock-items="viewStockItems"
      :stock-items-loading="viewStockItemsLoading"
      :can-add-stock-item="canAddStockItemToViewedStock"
      @update:modelValue="handleViewModalVisibility"
      @add-stock-item="openStockItemCreate"
    />

    <ReceivedPOItems
      :model-value="showReceivingPoItemsModal"
      :po="selectedReceivingPo"
      :can-edit="false"
      @update:modelValue="handleReceivingPoItemsVisibility"
    />

    <!-- Delete Item Modal -->
    <b-modal
      v-model="showDeleteItemModal"
      size="sm"
      centered
      no-close-on-backdrop
      hide-header
      footer-class="border-0 pt-0 pb-3 px-4 gap-2 justify-content-end"
    >
      <div class="text-center px-2 pt-4 pb-2">
        <div class="del-modal-icon mb-3">
          <i class="ri-delete-bin-2-line"></i>
        </div>
        <h5 class="fw-bold mb-1">Delete Item?</h5>
        <p class="text-muted mb-0 small">
          <strong class="text-dark">{{ deletingItem?.name }}</strong
          ><br />
          <span v-if="deletingItem?.code" class="font-monospace text-muted">{{
            deletingItem.code
          }}</span>
        </p>
        <p class="text-danger small mt-2 mb-0">This action cannot be undone.</p>
      </div>
      <template #footer>
        <b-button variant="light" class="px-4" @click="showDeleteItemModal = false"
          >Cancel</b-button
        >
        <b-button
          variant="danger"
          class="px-4"
          :disabled="deletingItemLoading"
          @click="executeDeleteItem"
        >
          <span v-if="deletingItemLoading"
            ><span class="spinner-border spinner-border-sm me-1"></span>Deleting…</span
          >
          <span v-else>Delete</span>
        </b-button>
      </template>
    </b-modal>

    <!-- Category Modal -->
    <b-modal
      v-model="showCategoryModal"
      :title="categoryForm.id ? 'Edit Category' : 'Add Category'"
      size="md"
      centered
      no-close-on-backdrop
      header-class="border-0 pb-0"
      footer-class="border-top"
    >
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-semibold"
            >Name <span class="text-danger">*</span></label
          >
          <input
            v-model="categoryForm.name"
            class="form-control"
            :class="{ 'is-invalid': categoryErrors.name }"
            placeholder="e.g. Office Supplies"
            @keyup.enter="saveCategory"
          />
          <div v-if="categoryErrors.name" class="invalid-feedback">
            {{
              Array.isArray(categoryErrors.name)
                ? categoryErrors.name[0]
                : categoryErrors.name
            }}
          </div>
        </div>
        <div class="col-12">
          <div class="form-check form-switch">
            <input
              v-model="categoryForm.is_active"
              class="form-check-input"
              type="checkbox"
              id="cat_is_active"
            />
            <label class="form-check-label fw-semibold" for="cat_is_active">Active</label>
          </div>
        </div>
      </div>
      <template #footer>
        <b-button variant="light" @click="showCategoryModal = false">Cancel</b-button>
        <b-button variant="primary" :disabled="saving" @click="saveCategory">
          <span v-if="saving"
            ><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span
          >
          <span v-else>{{ categoryForm.id ? "Update" : "Save" }}</span>
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from "axios";
import { Head, Link } from "@inertiajs/vue3";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import StockModal from "@/Pages/Modules/Inventory/Modals/Stock.vue";
import ItemModal from "@/Pages/Modules/Inventory/Modals/Item.vue";
import ReceivingModal from "@/Pages/Modules/Inventory/Modals/Receiving.vue";
import WithdrawModal from "@/Pages/Modules/Inventory/Modals/Withdraw.vue";
import RecordViewModal from "@/Pages/Modules/Inventory/Modals/RecordViewModal.vue";
import Stocks from "@/Pages/Modules/Inventory/Tabs/Stocks.vue";
import ReceivingLedger from "@/Pages/Modules/Inventory/Tabs/Receiving.vue";
import WithdrawalLedger from "@/Pages/Modules/Inventory/Tabs/Withdrawal.vue";
import ReceivedPOItems from "@/Pages/Modules/FAIMS/Procurement/Modals/ReceivedPOItems.vue";
import RisLedger from "@/Pages/Modules/Inventory/Tabs/Ris.vue";
import ReportPanel from "@/Pages/Modules/Inventory/Tabs/Report.vue";

export default {
  components: {
    Head,
    Link,
    PageHeader,
    Pagination,
    StockModal,
    ItemModal,
    ReceivingModal,
    WithdrawModal,
    RecordViewModal,
    Stocks,
    ReceivingLedger,
    WithdrawalLedger,
    ReceivedPOItems,
    RisLedger,
    ReportPanel,
  },
  props: {
    initialTab: { type: String, default: "stocks" },
    dropdowns: { type: Object, default: () => ({}) },
    users: { type: Array, default: () => [] },
    stockOptions: { type: Array, default: () => [] },
    itemOptions: { type: Array, default: () => [] },
    stocks: { type: [Array, Object], default: () => [] },
    items: { type: [Array, Object], default: () => [] },
    receivings: { type: [Array, Object], default: () => [] },
    withdrawals: { type: [Array, Object], default: () => [] },
  },
  data() {
    return {
      loading: false,
      saving: false,
      activeModule: "items",
      modules: [
        { key: "items", label: "Inventory", icon: "ri-store-3-line" },
        { key: "stocks", label: "Stocks", icon: "ri-stack-line" },
        { key: "categories", label: "Categories", icon: "ri-price-tag-3-line" },
        { key: "receivings", label: "Receivings", icon: "ri-inbox-archive-line" },
        { key: "withdrawals", label: "Withdrawals", icon: "ri-shopping-cart-line" },
        { key: "ris", label: "RIS", icon: "ri-file-list-3-line" },
        { key: "report", label: "Report", icon: "ri-bar-chart-2-line" },
      ],
      stockRows: [],
      stockMeta: null,
      stockLinks: null,
      itemRows: [],
      itemMeta: null,
      itemLinks: null,
      itemViewMode: "grid",
      receivingRows: [],
      receivingMeta: null,
      receivingLinks: null,
      withdrawalRows: [],
      withdrawalMeta: null,
      withdrawalLinks: null,
      stockKeyword: "",
      stockSearchTimer: null,
      stockOptionRows: [],
      itemOptionRows: [],
      itemSort: "latest",
      itemSearchTimer: null,
      manualItemOrder: [],
      lockItemStock: false,
      showStockModal: false,
      showItemModal: false,
      showReceivingModal: false,
      showReceivingPoItemsModal: false,
      showWithdrawalModal: false,
      showViewModal: false,
      viewRecordType: "",
      viewRecord: null,
      selectedReceivingPo: null,
      viewStockItems: [],
      viewStockItemsLoading: false,
      itemKeyword: "",
      stockForm: {
        id: null,
        item_id: "",
        quantity: "",
        unit_id: "",
        unit_cost: "",
        description: "",
      },
      itemForm: { id: null, code: "", name: "", category_id: "" },
      receivingForm: {
        id: null,
        item_id: "",
        approved_by_id: "",
        status_id: "",
        received_at: "",
        remarks: "",
      },
      withdrawalForm: {
        id: null,
        inventory_id: "",
        requested_by_id: "",
        approved_by_id: "",
        status_id: "",
        released_at: "",
        remarks: "",
      },
      categoryRows: [],
      categoryForm: { id: null, name: "", is_active: true },
      categoryErrors: {},
      showCategoryModal: false,
      showDeleteItemModal: false,
      deletingItem: null,
      deletingItemLoading: false,
      categoriesLoading: false,
      risRows: [],
      risMeta: null,
      risLinks: null,
      risLoading: false,
      stockErrors: {},
      itemErrors: {},
      receivingErrors: {},
      withdrawalErrors: {},
      expandedItems: {},
      itemStocksCache: {},
      itemStocksLoading: {},
    };
  },
  computed: {
    categories() {
      return this.categoryRows;
    },
    userOptions() {
      return this.users || [];
    },
    statusOptions() {
      return this.dropdowns?.statuses?.data || this.dropdowns?.statuses || [];
    },
    currentCreateLabel() {
      return (
        {
          items: "Add Item",
          stocks: "Add Stock",
          receivings: "Log Receiving",
          withdrawals: "Log Withdrawal",
          ris: "Create RIS",
        }[this.activeModule] || "Create"
      );
    },
    totalTrackedQuantity() {
      return this.itemRows.reduce((total, item) => total + Number(item.quantity || 0), 0);
    },
    lowBalanceItems() {
      return this.itemRows.filter((item) => {
        const quantity = Number(item.quantity || 0);
        return quantity > 0 && quantity <= 5;
      }).length;
    },
    inventoryHeroCards() {
      return [
        {
          label: "Total Items",
          value: this.formatNumber(this.itemMeta?.total ?? this.itemRows.length),
          icon: "ri-barcode-box-line",
          accent: "#60a5fa",
        },
        {
          label: "Categories",
          value: this.formatNumber(this.categoryRows.length),
          icon: "ri-price-tag-3-line",
          accent: "#a78bfa",
        },
        {
          label: "Receivings",
          value: this.formatNumber(
            this.receivingMeta?.total ?? this.receivingRows.length
          ),
          icon: "ri-inbox-archive-line",
          accent: "#34d399",
        },
        {
          label: "Withdrawals",
          value: this.formatNumber(
            this.withdrawalMeta?.total ?? this.withdrawalRows.length
          ),
          icon: "ri-shopping-cart-line",
          accent: "#fb923c",
        },
      ];
    },
    filteredItemRows() {
      const keyword = (this.itemKeyword || "").toLowerCase();

      return this.itemRows.filter((item) => {
        const searchable = [item.code, item.name, item.category]
          .filter(Boolean)
          .join(" ")
          .toLowerCase();

        return searchable.includes(keyword);
      });
    },
    sortedItemRows() {
      const rows = [...this.filteredItemRows];
      const normalizeText = (value) => String(value || "").toLowerCase();
      const normalizeNumber = (value) => Number(value || 0);
      const normalizeDate = (value) => {
        if (!value) return 0;

        const timestamp = new Date(value).getTime();
        return Number.isNaN(timestamp) ? 0 : timestamp;
      };

      if (this.itemSort === "custom") {
        const orderMap = new Map(
          this.manualItemOrder.map((id, index) => [Number(id), index])
        );

        return rows.sort((left, right) => {
          const leftOrder = orderMap.has(Number(left.id))
            ? orderMap.get(Number(left.id))
            : Number.MAX_SAFE_INTEGER;
          const rightOrder = orderMap.has(Number(right.id))
            ? orderMap.get(Number(right.id))
            : Number.MAX_SAFE_INTEGER;

          if (leftOrder !== rightOrder) {
            return leftOrder - rightOrder;
          }

          return Number(right.id || 0) - Number(left.id || 0);
        });
      }

      const sorters = {
        oldest: (left, right) => Number(left.id || 0) - Number(right.id || 0),
        name_asc: (left, right) =>
          normalizeText(left.name).localeCompare(normalizeText(right.name)),
        name_desc: (left, right) =>
          normalizeText(right.name).localeCompare(normalizeText(left.name)),
        quantity_desc: (left, right) =>
          normalizeNumber(right.quantity) - normalizeNumber(left.quantity),
        quantity_asc: (left, right) =>
          normalizeNumber(left.quantity) - normalizeNumber(right.quantity),
        expiration_asc: (left, right) =>
          normalizeDate(left.expiration) - normalizeDate(right.expiration),
        expiration_desc: (left, right) =>
          normalizeDate(right.expiration) - normalizeDate(left.expiration),
      };

      return rows.sort(
        sorters[this.itemSort] ||
          ((left, right) => Number(right.id || 0) - Number(left.id || 0))
      );
    },
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    canManageStockItems() {
      const allowedRoles = ["administrator", "supply", "supply officer", "supply staff"];
      return this.currentRoles.some((role) =>
        allowedRoles.includes(String(role || "").toLowerCase())
      );
    },
    canAddStockItemToViewedStock() {
      return this.viewRecordType === "stock" && this.canManageStockItems;
    },
  },
  created() {
    this.activeModule = [
      "items",
      "stocks",
      "categories",
      "receivings",
      "withdrawals",
      "ris",
      "report",
    ].includes(this.initialTab)
      ? this.initialTab
      : "items";

    this.assignPaginated("itemRows", "itemMeta", "itemLinks", this.items);
    this.assignPaginated(
      "receivingRows",
      "receivingMeta",
      "receivingLinks",
      this.receivings
    );
    this.assignPaginated(
      "withdrawalRows",
      "withdrawalMeta",
      "withdrawalLinks",
      this.withdrawals
    );
    this.stockOptionRows = [...this.stockOptions];
    this.itemOptionRows = [...this.itemOptions];
    this.hydrateManualItemOrder();
    // Seed categories from server-side Inertia prop (list_dropdowns with 'Inventory Category')
    const serverCategories = this.dropdowns?.categories || [];
    this.categoryRows = Array.isArray(serverCategories)
      ? serverCategories
      : serverCategories?.data || [];
  },
  mounted() {
    if (this.itemRows.length === 0) this.fetchItems();
  },
  watch: {
    itemKeyword() {
      if (this.itemSearchTimer) {
        clearTimeout(this.itemSearchTimer);
      }

      this.itemSearchTimer = setTimeout(() => {
        this.fetchItems();
      }, 300);
    },
    itemSort(value) {
      if (value === "custom") {
        this.hydrateManualItemOrder();
        return;
      }

      this.fetchItems();
    },
    showStockModal(value) {
      if (!value) this.stockErrors = {};
    },
    showItemModal(value) {
      if (!value) {
        this.lockItemStock = false;
      }
    },
    activeModule(value) {
      if (value === "ris" && this.risRows.length === 0) this.fetchRis();
      if (value === "stocks" && this.stockRows.length === 0) this.fetchStocks();
    },
  },
  beforeUnmount() {
    if (this.stockSearchTimer) {
      clearTimeout(this.stockSearchTimer);
    }

    if (this.itemSearchTimer) {
      clearTimeout(this.itemSearchTimer);
    }
  },
  methods: {
    assignPaginated(rowsKey, metaKey, linksKey, payload) {
      this[rowsKey] = this.normalizeCollectionRows(payload);
      this[metaKey] = payload?.meta || payload?.data?.meta || null;
      this[linksKey] = payload?.links || payload?.data?.links || null;

      if (rowsKey === "itemRows") {
        this.hydrateManualItemOrder();
      }
    },
    normalizeCollectionRows(payload) {
      if (Array.isArray(payload)) {
        return payload;
      }

      if (Array.isArray(payload?.data)) {
        return payload.data;
      }

      if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
      }

      return [];
    },
    collectionParams(extra = {}) {
      return {
        json: 1,
        count: 10,
        ...extra,
      };
    },
    async fetchStocks(pageUrl = "/inventory-stocks") {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, {
          params: this.collectionParams({
            keyword: this.stockKeyword || undefined,
          }),
        });
        this.assignPaginated("stockRows", "stockMeta", "stockLinks", response.data);
      } finally {
        this.loading = false;
      }
    },
    async fetchItems(pageUrl = "/inventory-items") {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, {
          params: this.collectionParams({
            keyword: this.itemKeyword || undefined,
            sort: this.itemSort === "custom" ? "latest" : this.itemSort,
          }),
        });
        this.assignPaginated("itemRows", "itemMeta", "itemLinks", response.data);
      } finally {
        this.loading = false;
      }
    },
    async fetchReceivings(pageUrl = "/inventory-receivings") {
      this.loading = true;
      try {
        const response = await axios.get(
          pageUrl === "/inventory-receivings" ? "/faims/receiving-deliveries" : pageUrl,
          {
            params: this.collectionParams({
              option: "lists",
            }),
          }
        );
        this.assignPaginated(
          "receivingRows",
          "receivingMeta",
          "receivingLinks",
          response.data
        );
      } finally {
        this.loading = false;
      }
    },
    async fetchWithdrawals(pageUrl = "/inventory-withdrawals") {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, { params: this.collectionParams() });
        this.assignPaginated(
          "withdrawalRows",
          "withdrawalMeta",
          "withdrawalLinks",
          response.data
        );
      } finally {
        this.loading = false;
      }
    },
    async fetchRis(pageUrl = "/inventory-ris") {
      this.risLoading = true;
      try {
        const response = await axios.get(pageUrl, { params: this.collectionParams() });
        this.assignPaginated("risRows", "risMeta", "risLinks", response.data);
      } finally {
        this.risLoading = false;
      }
    },
    openRisCreate() {
      this.$refs.risLedger?.openCreate();
    },
    openActiveCreate() {
      if (this.activeModule === "stocks") {
        this.openStockCreate();
        return;
      }
      if (this.activeModule === "receivings") {
        this.openReceivingCreate();
        return;
      }
      if (this.activeModule === "withdrawals") {
        this.openWithdrawalCreate();
        return;
      }
      if (this.activeModule === "ris") {
        this.openRisCreate();
        return;
      }
      this.openItemCreate();
    },
    refreshStocks() {
      this.stockKeyword = "";
      this.fetchStocks();
    },
    handleStockKeywordChange(value) {
      this.stockKeyword = value;

      if (this.stockSearchTimer) {
        clearTimeout(this.stockSearchTimer);
      }

      this.stockSearchTimer = setTimeout(() => {
        this.fetchStocks();
      }, 300);
    },
    handleItemRefresh() {
      if (this.itemSearchTimer) {
        clearTimeout(this.itemSearchTimer);
      }

      this.itemKeyword = "";
      this.itemSort = "latest";
      this.fetchItems();
    },
    openViewModal(type, row) {
      this.viewRecordType = type;
      this.viewRecord = row;
      this.viewStockItems = [];
      this.showViewModal = true;

      if (type === "stock") {
        this.fetchStockItems(row.id);
      }
    },
    handleViewModalVisibility(value) {
      this.showViewModal = value;

      if (!value) {
        this.viewRecordType = "";
        this.viewRecord = null;
        this.viewStockItems = [];
        this.viewStockItemsLoading = false;
      }
    },
    openReceivingPoItems(row) {
      this.selectedReceivingPo = row;
      this.showReceivingPoItemsModal = true;
    },
    handleReceivingPoItemsVisibility(value) {
      this.showReceivingPoItemsModal = value;

      if (!value) {
        this.selectedReceivingPo = null;
      }
    },
    async fetchStockItems(stockId) {
      const currentStockId = Number(stockId);
      this.viewStockItemsLoading = true;

      try {
        const response = await axios.get("/inventory-items", {
          params: this.collectionParams({
            stock_id: currentStockId,
            count: 100,
            sort: "name_asc",
          }),
        });

        if (
          this.viewRecordType === "stock" &&
          Number(this.viewRecord?.id) === currentStockId
        ) {
          this.viewStockItems = response.data?.data || [];
        }
      } finally {
        if (
          this.viewRecordType === "stock" &&
          Number(this.viewRecord?.id) === currentStockId
        ) {
          this.viewStockItemsLoading = false;
        }
      }
    },
    openStockCreate(item = null) {
      this.stockForm = {
        id: null,
        item_id: item?.id ? String(item.id) : "",
        _lock_item: !!item?.id,
        quantity: "",
        unit_id: "",
        unit_cost: "",
        description: "",
      };
      this.stockErrors = {};
      this.showStockModal = true;
    },
    openStockEdit(row) {
      this.stockForm = {
        id: row.id,
        item_id: String(row.item_id || ""),
        _lock_item: true,
        quantity: row.quantity || "",
        unit_id: String(row.unit_id || ""),
        unit_cost: row.unit_cost || "",
        description: row.description || "",
      };
      this.stockErrors = {};
      this.showStockModal = true;
    },
    generateItemCode() {
      const now = new Date();
      const yy = String(now.getFullYear()).slice(-2);
      const mm = String(now.getMonth() + 1).padStart(2, "0");
      const dd = String(now.getDate()).padStart(2, "0");
      const rand = String(Math.floor(Math.random() * 900) + 100);
      return `ITM-${yy}${mm}${dd}-${rand}`;
    },
    openItemCreate() {
      this.itemForm = {
        id: null,
        code: this.generateItemCode(),
        name: "",
        category_id: "",
      };
      this.itemErrors = {};
      this.showItemModal = true;
    },
    openStockItemCreate() {
      this.handleViewModalVisibility(false);
      this.itemForm = { id: null, code: "", name: "", category_id: "" };
      this.itemErrors = {};
      this.showItemModal = true;
    },
    openItemEdit(row) {
      this.itemForm = {
        id: row.id,
        code: row.code || "",
        name: row.name || "",
        category_id: String(row.category_id || ""),
      };
      this.itemErrors = {};
      this.showItemModal = true;
    },
    openReceivingCreate() {
      this.receivingForm = {
        id: null,
        item_id: "",
        approved_by_id: "",
        status_id: "",
        received_at: "",
        remarks: "",
      };
      this.receivingErrors = {};
      this.showReceivingModal = true;
    },
    openReceivingEdit(row) {
      this.receivingForm = {
        id: row.id,
        item_id: String(row.item_id || ""),
        approved_by_id: String(row.approved_by_id || ""),
        status_id: String(row.status_id || ""),
        received_at: this.toInputDateTime(row.received_at),
        remarks: row.remarks || "",
      };
      this.receivingErrors = {};
      this.showReceivingModal = true;
    },
    openWithdrawalCreate() {
      this.withdrawalForm = {
        id: null,
        inventory_id: "",
        requested_by_id: "",
        approved_by_id: "",
        status_id: "",
        released_at: "",
        remarks: "",
      };
      this.withdrawalErrors = {};
      this.showWithdrawalModal = true;
    },
    openWithdrawalEdit(row) {
      this.withdrawalForm = {
        id: row.id,
        inventory_id: String(row.inventory_id || row.item_id || ""),
        requested_by_id: String(row.requested_by_id || ""),
        approved_by_id: String(row.approved_by_id || ""),
        status_id: String(row.status_id || ""),
        released_at: this.toInputDateTime(row.released_at),
        remarks: row.remarks || "",
      };
      this.withdrawalErrors = {};
      this.showWithdrawalModal = true;
    },
    isItemExpanded(itemId) {
      return !!this.expandedItems[itemId];
    },
    async toggleItemExpand(item) {
      if (this.expandedItems[item.id]) {
        const next = { ...this.expandedItems };
        delete next[item.id];
        this.expandedItems = next;
      } else {
        this.expandedItems = { ...this.expandedItems, [item.id]: true };
        if (!this.itemStocksCache[item.id]) {
          await this.fetchItemExpandStocks(item.id);
        }
      }
    },
    async fetchItemExpandStocks(itemId) {
      this.itemStocksLoading = { ...this.itemStocksLoading, [itemId]: true };
      try {
        const resp = await axios.get("/inventory-stocks", {
          params: { json: 1, item_id: itemId, count: 200 },
        });
        const rows = Array.isArray(resp.data?.data)
          ? resp.data.data
          : Array.isArray(resp.data)
          ? resp.data
          : [];
        this.itemStocksCache = { ...this.itemStocksCache, [itemId]: rows };
      } finally {
        const next = { ...this.itemStocksLoading };
        delete next[itemId];
        this.itemStocksLoading = next;
      }
    },
    async removeExpandedStock(stock) {
      if (!confirm("Delete this stock entry?")) return;
      await axios.delete(`/inventory-stocks/${stock.id}`);
      if (this.itemStocksCache[stock.item_id]) {
        this.itemStocksCache = {
          ...this.itemStocksCache,
          [stock.item_id]: this.itemStocksCache[stock.item_id].filter(
            (s) => s.id !== stock.id
          ),
        };
      }
      this.fetchItems();
    },
    async saveStock() {
      const { _lock_item, ...payload } = this.stockForm;
      const lockedItemId = this.stockForm.item_id;
      const response = await this.submitEntity(
        "/inventory-stocks",
        payload,
        "showStockModal",
        "stockErrors",
        this.fetchStocks
      );
      if (response) {
        this.syncOptionRow("stockOptionRows", response?.data?.data);
        await this.fetchItems();
        // Refresh the expanded sub-row for this item if it's open
        if (lockedItemId && this.expandedItems[lockedItemId]) {
          await this.fetchItemExpandStocks(lockedItemId);
        }
      }
    },
    async saveItem() {
      const response = await this.submitEntity(
        "/inventory-items",
        this.itemForm,
        "showItemModal",
        "itemErrors",
        this.fetchItems
      );
      if (!response) return;

      this.syncOptionRow("itemOptionRows", response?.data?.data);
      this.lockItemStock = false;
      await this.fetchStocks();
    },
    async saveReceiving() {
      await this.submitEntity(
        "/inventory-receivings",
        this.receivingForm,
        "showReceivingModal",
        "receivingErrors",
        this.fetchReceivings
      );
    },
    async saveWithdrawal() {
      await this.submitEntity(
        "/inventory-withdrawals",
        this.withdrawalForm,
        "showWithdrawalModal",
        "withdrawalErrors",
        this.fetchWithdrawals
      );
    },
    async submitEntity(baseUrl, form, modalKey, errorKey, refreshFn) {
      this.saving = true;
      this[errorKey] = {};
      const payload = { ...form };

      if (payload.entry_date)
        payload.entry_date = payload.entry_date.replace("T", " ") + ":00";
      if (payload.received_at)
        payload.received_at = payload.received_at.replace("T", " ") + ":00";
      if (payload.released_at)
        payload.released_at = payload.released_at.replace("T", " ") + ":00";

      try {
        let response;

        if (form.id) {
          response = await axios.put(`${baseUrl}/${form.id}`, payload);
        } else {
          response = await axios.post(baseUrl, payload);
        }

        this[modalKey] = false;
        await refreshFn.call(this);

        return response;
      } catch (error) {
        if (error?.response?.status === 422) {
          this[errorKey] = error.response.data.errors || {};
        }

        return null;
      } finally {
        this.saving = false;
      }
    },
    syncOptionRow(listKey, record) {
      if (!record?.id) return;

      const next = [
        ...this[listKey].filter((item) => Number(item.id) !== Number(record.id)),
        {
          id: record.id,
          code: record.code || "",
          name: record.name || record.item_name || "",
        },
      ].sort((a, b) => String(a.name || "").localeCompare(String(b.name || "")));

      this[listKey] = next;
    },
    hydrateManualItemOrder() {
      const savedOrder = this.readStoredItemOrder();
      const currentIds = this.itemRows.map((item) => Number(item.id)).filter(Boolean);
      const missingIds = currentIds.filter((id) => !savedOrder.includes(id));

      this.manualItemOrder = [...savedOrder, ...missingIds];
      this.persistManualItemOrder();
    },
    readStoredItemOrder() {
      if (typeof window === "undefined" || !window.localStorage) {
        return [];
      }

      try {
        const stored = JSON.parse(
          window.localStorage.getItem("inventory:item-order") || "[]"
        );

        return Array.isArray(stored)
          ? stored.map((id) => Number(id)).filter(Boolean)
          : [];
      } catch (error) {
        return [];
      }
    },
    persistManualItemOrder() {
      if (typeof window === "undefined" || !window.localStorage) {
        return;
      }

      window.localStorage.setItem(
        "inventory:item-order",
        JSON.stringify(this.manualItemOrder)
      );
    },
    moveItemRow(item, direction) {
      const visibleRows = [...this.sortedItemRows];
      const currentIndex = visibleRows.findIndex(
        (row) => Number(row.id) === Number(item.id)
      );
      const targetIndex = currentIndex + direction;

      if (currentIndex < 0 || targetIndex < 0 || targetIndex >= visibleRows.length) {
        return;
      }

      const [movingRow] = visibleRows.splice(currentIndex, 1);
      visibleRows.splice(targetIndex, 0, movingRow);

      const visibleIds = visibleRows.map((row) => Number(row.id));
      const currentIds = this.itemRows.map((row) => Number(row.id));
      const remainingStoredIds = this.manualItemOrder.filter(
        (id) => !visibleIds.includes(id)
      );
      const missingIds = currentIds.filter(
        (id) => !visibleIds.includes(id) && !remainingStoredIds.includes(id)
      );

      this.manualItemOrder = [...visibleIds, ...remainingStoredIds, ...missingIds];
      this.itemSort = "custom";
      this.persistManualItemOrder();
    },
    displayItemNumber(index) {
      return Number(this.itemMeta?.from || 1) + index;
    },
    removeOptionRow(listKey, id) {
      this[listKey] = this[listKey].filter((item) => Number(item.id) !== Number(id));
    },
    async removeStock(row) {
      if (!confirm(`Delete stock "${row.name}"?`)) return;
      await axios.delete(`/inventory-stocks/${row.id}`);
      this.removeOptionRow("stockOptionRows", row.id);
      this.fetchStocks();
    },
    confirmDeleteItem(item) {
      this.deletingItem = item;
      this.showDeleteItemModal = true;
    },
    async executeDeleteItem() {
      if (!this.deletingItem) return;
      this.deletingItemLoading = true;
      try {
        await axios.delete(`/inventory-items/${this.deletingItem.id}`);
        this.removeOptionRow("itemOptionRows", this.deletingItem.id);
        this.showDeleteItemModal = false;
        this.deletingItem = null;
        this.fetchItems();
      } finally {
        this.deletingItemLoading = false;
      }
    },
    async removeReceiving(row) {
      if (!confirm(`Delete receiving for "${row.item_name}"?`)) return;
      await axios.delete(`/inventory-receivings/${row.id}`);
      this.fetchReceivings();
    },
    async removeWithdrawal(row) {
      if (!confirm(`Delete withdrawal for "${row.item_name}"?`)) return;
      await axios.delete(`/inventory-withdrawals/${row.id}`);
      this.fetchWithdrawals();
    },
    async fetchCategories() {
      this.categoriesLoading = true;
      try {
        const response = await axios.get("/inventory-categories", {
          params: { json: 1, count: 200 },
        });
        this.categoryRows = Array.isArray(response.data?.data)
          ? response.data.data
          : Array.isArray(response.data)
          ? response.data
          : [];
      } finally {
        this.categoriesLoading = false;
      }
    },
    openCategoryCreate() {
      this.categoryForm = { id: null, name: "", is_active: true };
      this.categoryErrors = {};
      this.showCategoryModal = true;
    },
    openCategoryEdit(row) {
      this.categoryForm = {
        id: row.id,
        name: row.name || "",
        is_active: Boolean(row.is_active),
      };
      this.categoryErrors = {};
      this.showCategoryModal = true;
    },
    async saveCategory() {
      if (!this.categoryForm.name?.trim()) {
        this.categoryErrors = { name: ["Name is required."] };
        return;
      }
      this.saving = true;
      this.categoryErrors = {};
      try {
        if (this.categoryForm.id) {
          await axios.put(`/inventory-categories/${this.categoryForm.id}`, {
            name: this.categoryForm.name,
            is_active: this.categoryForm.is_active,
          });
        } else {
          await axios.post("/inventory-categories", {
            name: this.categoryForm.name,
            is_active: this.categoryForm.is_active,
          });
        }
        this.showCategoryModal = false;
        await this.fetchCategories();
      } catch (error) {
        if (error?.response?.status === 422) {
          this.categoryErrors = error.response.data.errors || {};
        } else {
          this.categoryErrors = { name: ["An error occurred. Please try again."] };
          console.error("saveCategory error:", error?.response ?? error);
        }
      } finally {
        this.saving = false;
      }
    },
    async removeCategory(row) {
      if (!confirm(`Delete category "${row.name}"?`)) return;
      await axios.delete(`/inventory-categories/${row.id}`);
      this.fetchCategories();
    },
    formatNumber(value) {
      return new Intl.NumberFormat().format(Number(value || 0));
    },
    toInputDateTime(value) {
      if (!value) return "";
      return String(value).replace(" ", "T").slice(0, 16);
    },
    currentInputDateTime() {
      const now = new Date();
      const offset = now.getTimezoneOffset();
      const local = new Date(now.getTime() - offset * 60000);

      return local.toISOString().slice(0, 16);
    },
    moduleMeta(moduleKey) {
      if (moduleKey === "items")
        return `${this.formatNumber(this.itemMeta?.total ?? this.itemRows.length)} items`;
      if (moduleKey === "stocks")
        return `${this.formatNumber(
          this.stockMeta?.total ?? this.stockRows.length
        )} records`;
      if (moduleKey === "categories")
        return `${this.formatNumber(this.categoryRows.length)} categories`;
      if (moduleKey === "receivings")
        return `${this.formatNumber(
          this.receivingMeta?.total ?? this.receivingRows.length
        )} logs`;
      if (moduleKey === "withdrawals")
        return `${this.formatNumber(
          this.withdrawalMeta?.total ?? this.withdrawalRows.length
        )} logs`;
      if (moduleKey === "ris")
        return `${this.formatNumber(this.risMeta?.total ?? this.risRows.length)} slips`;
      return "";
    },
  },
};
</script>

<style scoped>
/* ═══════════════════════════════════════════════════
   INVENTORY PAGE — DESIGN SYSTEM
   ════════════════════════════════════════════════════ */
.inventory-page {
  --inv-brand: #4b5b93;
  --inv-brand-deep: #38467a;
  --inv-brand-soft: #edf1fb;
  --inv-accent: #0ea5e9;
  --inv-success: #10b981;
  --inv-warning: #f59e0b;
  --inv-surface: #ffffff;
  --inv-bg: #f3f7ff;
  --inv-border: #dce4f2;
  --inv-muted: #64748b;
  --inv-ink: #0f172a;
  --inv-shadow: rgba(15, 23, 42, 0.07);
  min-height: 100vh;
  padding: 0.55rem 0.55rem 1.5rem;
  background: var(--inv-bg);
}

/* ── Hero ──────────────────────────────────────────── */
.inv-hero {
  position: relative;
  border-radius: 24px;
  overflow: hidden;
  background: radial-gradient(
      ellipse at 80% -10%,
      rgba(96, 165, 250, 0.35) 0%,
      transparent 45%
    ),
    radial-gradient(ellipse at -5% 90%, rgba(167, 139, 250, 0.2) 0%, transparent 40%),
    linear-gradient(135deg, #1e2d6b 0%, #2d3f8a 40%, #38467a 70%, #4b5b93 100%);
  box-shadow: 0 24px 60px rgba(30, 45, 107, 0.35);
  color: #fff;
}

/* Decorative elements */
.inv-hero-deco {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}
.inv-hero-deco-ring {
  position: absolute;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.07);
}
.inv-hero-deco-ring--1 {
  width: 420px;
  height: 420px;
  top: -140px;
  right: -60px;
}
.inv-hero-deco-ring--2 {
  width: 280px;
  height: 280px;
  top: -60px;
  right: 80px;
}
.inv-hero-deco-grid {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
  background-size: 32px 32px;
}

.inv-hero-body {
  position: relative;
  display: grid;
  grid-template-columns: minmax(300px, 1fr) minmax(0, 1.3fr);
  gap: 2rem;
  align-items: center;
  padding: 1.75rem 2rem;
}

/* ── Left: identity ─────────────────────────────────── */
.inv-hero-kicker {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.28rem 0.75rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.18);
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  margin-bottom: 0.8rem;
  color: rgba(255, 255, 255, 0.8);
}

.inv-hero-title {
  font-size: clamp(1.65rem, 2.4vw, 2.4rem);
  font-weight: 900;
  line-height: 1.12;
  margin: 0 0 0.6rem;
  color: #fff;
  letter-spacing: -0.02em;
}
.inv-hero-title em {
  font-style: normal;
  background: linear-gradient(90deg, #93c5fd, #c4b5fd);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.inv-hero-desc {
  font-size: 0.88rem;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.65);
  margin: 0 0 1.25rem;
  max-width: 420px;
}

.inv-hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
  margin-bottom: 1.1rem;
}

.inv-hero-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.65rem 1.2rem;
  border-radius: 12px;
  border: 0;
  background: #fff;
  color: #1e2d6b;
  font-size: 0.85rem;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
  transition: transform 0.15s, box-shadow 0.15s;
}
.inv-hero-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.22);
}
.inv-hero-btn-arrow {
  font-size: 0.95rem;
  transition: transform 0.15s;
}
.inv-hero-btn-primary:hover .inv-hero-btn-arrow {
  transform: translateX(3px);
}

.inv-hero-btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.65rem 1.2rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.25);
  background: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.15s, border-color 0.15s;
}
.inv-hero-btn-ghost:hover {
  background: rgba(255, 255, 255, 0.16);
  border-color: rgba(255, 255, 255, 0.4);
  color: #fff;
}

/* ── Quick-links strip ───────────────────────────────── */
.inv-hero-quicklinks {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}
.inv-hero-ql {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.22rem 0.65rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.06);
  color: rgba(255, 255, 255, 0.65);
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s;
}
.inv-hero-ql:hover,
.inv-hero-ql.active {
  background: rgba(255, 255, 255, 0.18);
  border-color: rgba(255, 255, 255, 0.3);
  color: #fff;
}

/* ── Stat cards 2×2 ─────────────────────────────────── */
.inv-hero-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.inv-hero-stat {
  --stat-accent: #60a5fa;
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding: 1rem 1.1rem 0.85rem;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.07);
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(12px);
  overflow: hidden;
  transition: background 0.2s;
}
.inv-hero-stat:hover {
  background: rgba(255, 255, 255, 0.11);
}

.inv-hero-stat::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--stat-accent);
  border-radius: 18px 18px 0 0;
}

.inv-hero-stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.inv-hero-stat-icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  background: color-mix(in srgb, var(--stat-accent) 20%, transparent);
  border: 1px solid color-mix(in srgb, var(--stat-accent) 30%, transparent);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  color: var(--stat-accent);
  flex-shrink: 0;
}

.inv-hero-stat-val {
  font-size: 1.7rem;
  font-weight: 900;
  color: #fff;
  line-height: 1;
  letter-spacing: -0.03em;
}

.inv-hero-stat-lbl {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.55);
}

.inv-hero-stat-bar {
  height: 3px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  margin-top: 0.1rem;
  position: relative;
  overflow: hidden;
}
.inv-hero-stat-bar::after {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 60%;
  background: var(--stat-accent);
  border-radius: 999px;
  opacity: 0.7;
}

/* ── Shell + Tab Nav ────────────────────────────────── */
.inv-shell {
  border: 1px solid var(--inv-border);
  border-radius: 22px;
  background: var(--inv-surface);
  overflow: hidden;
  box-shadow: 0 8px 32px var(--inv-shadow);
}

.inv-tab-nav {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.65rem 0.85rem;
  background: linear-gradient(180deg, #f8fbff, #f0f5ff);
  border-bottom: 1px solid var(--inv-border);
  overflow-x: auto;
  scrollbar-width: none;
}
.inv-tab-nav::-webkit-scrollbar {
  display: none;
}

.inv-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.9rem;
  border: 1px solid transparent;
  border-radius: 12px;
  background: transparent;
  color: var(--inv-muted);
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.inv-tab:hover:not(.active) {
  background: var(--inv-brand-soft);
  color: var(--inv-brand);
}

.inv-tab.active {
  background: linear-gradient(135deg, var(--inv-brand), var(--inv-brand-deep));
  color: #fff;
  border-color: transparent;
  box-shadow: 0 4px 14px rgba(75, 91, 147, 0.25);
}

.inv-tab em {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 18px;
  padding: 0 0.35rem;
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.08);
  font-style: normal;
  font-size: 0.68rem;
  font-weight: 800;
}

.inv-tab.active em {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}

.inv-tab-content {
  padding: 0.85rem;
}

/* ── Items section (inline in Index) ───────────────── */
.ledger-card {
  border-radius: 16px;
  overflow: hidden;
}

.ledger-card .card-header {
  background: linear-gradient(180deg, #f8fbff, #f0f5ff);
  border-bottom: 1px solid var(--inv-border);
  padding: 0.8rem 1rem;
}

.inventory-items-body {
  padding: 0.75rem;
}

.ledger-toolbar-wrap {
  display: flex;
  align-items: stretch;
  flex-wrap: nowrap;
  gap: 0;
  width: 100%;
}

.ledger-toolbar {
  flex: 1 1 auto;
  min-width: 0;
}
.ledger-search-group {
  height: 100%;
}

.ledger-refresh-btn {
  flex: 0 0 44px;
  min-width: 44px;
  border: 1px solid var(--inv-border);
  border-left: 0;
  border-radius: 0;
  background: #f8fbff;
  color: var(--inv-muted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.ledger-refresh-btn:hover {
  background: var(--inv-brand-soft);
  color: var(--inv-brand);
}

.withdrawal-create-btn {
  flex: 0 0 160px;
  min-width: 160px;
  border-radius: 0 8px 8px 0;
  background: var(--inv-brand-deep);
  border: 1px solid var(--inv-brand-deep);
  color: #fff;
  font-weight: 700;
  white-space: nowrap;
  cursor: pointer;
}

.inventory-items-toolbar {
  align-items: stretch;
}
.inventory-sort-select {
  flex: 0 0 200px;
  min-width: 200px;
  border-left: 0;
  border-radius: 0;
}

.inventory-toolbar-note {
  color: var(--inv-muted);
  font-size: 0.75rem;
  font-weight: 600;
  margin-top: 0.45rem;
}

/* ── Items table ────────────────────────────────────── */
.inv-table-wrap {
  max-height: calc(100vh - 320px);
  overflow: auto;
  border: 1px solid var(--inv-border);
  border-radius: 14px;
  background: #fff;
}

.inv-table thead th {
  background: linear-gradient(180deg, #f8fbff, #eef4ff);
  color: var(--inv-muted);
  font-size: 0.71rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

/* ── Items 3D grid ──────────────────────────────────── */
.items-3d-grid-wrap {
  max-height: calc(100vh - 310px);
  overflow: auto;
  margin-top: 0;
}

.items-3d-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}

.item-3d-card-wrapper {
  perspective: 1000px;
}

.item-3d-card {
  position: relative;
  background: var(--inv-surface);
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid var(--inv-border);
  box-shadow: 0 4px 16px var(--inv-shadow);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  display: flex;
  flex-direction: column;
}

.item-3d-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
}

.item-card-icon-area {
  position: relative;
  height: 100px;
  background: linear-gradient(135deg, #edf1fb, #dfe7fb);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.item-card-icon {
  font-size: 3rem;
  color: rgba(75, 91, 147, 0.25);
}

.item-card-overlay {
  position: absolute;
  inset: 0;
  background: rgba(56, 70, 122, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s;
}

.item-3d-card:hover .item-card-overlay {
  opacity: 1;
}

.item-actions {
  display: flex;
  gap: 0.45rem;
  transform: translateY(12px);
  transition: transform 0.25s ease;
}

.item-3d-card:hover .item-actions {
  transform: translateY(0);
}

.item-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  background: rgba(255, 255, 255, 0.92);
  color: var(--inv-ink);
  padding: 0.18rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 700;
  font-family: ui-monospace, monospace;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
}

.item-card-content {
  padding: 0.85rem;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  background: linear-gradient(180deg, #fff, #f8fafc);
}

.item-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--inv-ink);
  margin: 0;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.item-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
}

.item-footer {
  margin-top: auto;
  font-size: 0.75rem;
  color: var(--inv-muted);
  display: flex;
  align-items: center;
  padding-top: 0.5rem;
  border-top: 1px dashed var(--inv-border);
}

/* ── Order controls ─────────────────────────────────── */
.inventory-order-controls {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.2rem;
  padding: 0.18rem;
  border: 1px solid var(--inv-border);
  border-radius: 999px;
  background: #f8fbff;
}

.inventory-order-btn {
  width: 26px;
  height: 26px;
  padding: 0;
  border: 0;
  border-radius: 999px;
  color: var(--inv-ink);
  background: #fff;
}

.inventory-order-btn:not(:disabled):hover {
  color: #fff;
  background: var(--inv-brand-deep);
}
.inventory-order-number {
  min-width: 26px;
  color: var(--inv-muted);
  font-size: 0.75rem;
  font-weight: 800;
}

/* ── Inline inv-table classes (items tab uses Stocks.vue classes) */
.inv-module-card {
  border: 1px solid var(--inv-border);
  border-radius: 20px;
  background: var(--inv-surface);
  overflow: hidden;
}
.inv-table-shell {
  overflow: auto;
  max-height: calc(100vh - 375px);
  min-height: 200px;
}
.inv-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}
.inv-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: linear-gradient(180deg, #f3f7ff, #eaf0fd);
  padding: 0.65rem 0.85rem;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--inv-muted);
  border-bottom: 1px solid var(--inv-border);
  white-space: nowrap;
}
.inv-table-row td {
  padding: 0.72rem 0.85rem;
  border-bottom: 1px solid #f1f5ff;
  font-size: 0.85rem;
  color: var(--inv-ink);
  vertical-align: middle;
  background: var(--inv-surface);
  transition: background 0.12s;
}
.inv-table-row:hover td {
  background: #f8fbff;
}
.inv-table-row.item-row-open td {
  background: #f0f5ff;
  border-bottom-color: transparent;
}
.inv-table-empty td {
  padding: 0;
  border: 0;
}

/* ── Expand toggle button ───────────────────────────── */
.item-expand-btn {
  width: 26px;
  height: 26px;
  border: 1px solid var(--inv-border);
  border-radius: 7px;
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
  color: var(--inv-muted);
  padding: 0;
}
.item-expand-btn:hover,
.item-expand-btn.active {
  background: var(--inv-brand-soft);
  border-color: var(--inv-brand);
  color: var(--inv-brand);
}
.item-expand-icon {
  font-size: 1rem;
  transition: transform 0.2s ease;
  display: block;
}
.item-expand-icon.rotated {
  transform: rotate(90deg);
}

/* ── Sub-row shell ───────────────────────────────────── */
.item-sub-row > td {
  padding: 0 !important;
  border-bottom: 2px solid var(--inv-border) !important;
  border-top: 0 !important;
}

/* ══ Inventory Stocks Panel (isp) ══════════════════════ */
.item-stocks-panel {
  background: #f4f7ff;
  border-left: 3px solid var(--inv-brand);
}

/* Panel header */
.isp-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.6rem 1rem 0.6rem 1.25rem;
  background: linear-gradient(
    90deg,
    rgba(75, 91, 147, 0.09) 0%,
    rgba(75, 91, 147, 0.03) 100%
  );
  border-bottom: 1px solid rgba(75, 91, 147, 0.12);
}
.isp-header-left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.isp-header-icon {
  width: 30px;
  height: 30px;
  border-radius: 9px;
  background: var(--inv-brand);
  color: #fff;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.isp-header-title {
  display: block;
  font-size: 0.78rem;
  font-weight: 800;
  color: var(--inv-ink);
  line-height: 1.2;
}
.isp-header-sub {
  font-size: 0.72rem;
  color: var(--inv-muted);
  line-height: 1.2;
}
.isp-header-right {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-shrink: 0;
}
.isp-summary-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.55rem;
  border-radius: 20px;
  background: rgba(75, 91, 147, 0.1);
  color: var(--inv-brand);
  font-size: 0.72rem;
  font-weight: 700;
}
.isp-summary-chip.green {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}
.isp-add-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.28rem 0.75rem;
  border-radius: 8px;
  border: 0;
  background: var(--inv-brand);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  transition: opacity 0.15s;
}
.isp-add-btn:hover {
  opacity: 0.85;
}

/* Loading state */
.isp-loading {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 1.25rem 1.5rem;
  color: var(--inv-muted);
  font-size: 0.84rem;
}
.isp-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #dde4f5;
  border-top-color: var(--inv-brand);
  border-radius: 50%;
  animation: inv-spin 0.7s linear infinite;
  flex-shrink: 0;
}

/* Empty state */
.isp-empty {
  text-align: center;
  padding: 2rem 1rem;
}
.isp-empty-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(75, 91, 147, 0.1);
  color: var(--inv-brand);
  font-size: 1.4rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.65rem;
}
.isp-empty-title {
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--inv-ink);
  margin: 0 0 0.25rem;
}
.isp-empty-sub {
  font-size: 0.78rem;
  color: var(--inv-muted);
  margin: 0;
}

/* Table wrapper */
.isp-table-wrap {
  overflow-x: auto;
}
.isp-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}
.isp-table thead th {
  padding: 0.42rem 0.85rem;
  font-size: 0.67rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--inv-brand);
  background: rgba(75, 91, 147, 0.06);
  border-bottom: 1px solid rgba(75, 91, 147, 0.12);
  white-space: nowrap;
}

/* Data rows */
.isp-row td {
  padding: 0.6rem 0.85rem;
  border-bottom: 1px solid rgba(75, 91, 147, 0.06);
  font-size: 0.83rem;
  vertical-align: middle;
  background: transparent;
  transition: background 0.1s;
}
.isp-row:last-child td {
  border-bottom: 0;
}
.isp-row:hover td {
  background: rgba(75, 91, 147, 0.05);
}

/* Row number */
.isp-row-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: rgba(75, 91, 147, 0.1);
  color: var(--inv-brand);
  font-size: 0.68rem;
  font-weight: 800;
}

/* Qty + unit */
.isp-qty-cell {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.isp-qty-val {
  font-size: 1rem;
  font-weight: 800;
  color: var(--inv-ink);
}
.isp-unit-pill {
  display: inline-block;
  padding: 0.12rem 0.45rem;
  border-radius: 20px;
  background: rgba(75, 91, 147, 0.12);
  color: var(--inv-brand);
  font-size: 0.7rem;
  font-weight: 700;
}
.isp-unit-long {
  display: block;
  font-size: 0.7rem;
  color: var(--inv-muted);
  margin-top: 0.1rem;
}

/* Cost / total */
.isp-cost-val {
  font-size: 0.83rem;
  color: var(--inv-muted);
}
.isp-total-val {
  font-size: 0.88rem;
  font-weight: 700;
  color: #059669;
}

/* Date */
.isp-date {
  font-size: 0.76rem;
  color: var(--inv-muted);
}

/* Description */
.isp-desc {
  font-size: 0.8rem;
  color: var(--inv-ink);
}
.isp-desc-empty {
  color: #cbd5e1;
  font-size: 0.8rem;
}

/* Totals footer row */
.isp-totals-row td {
  padding: 0.5rem 0.85rem;
  background: linear-gradient(
    90deg,
    rgba(75, 91, 147, 0.07) 0%,
    rgba(75, 91, 147, 0.03) 100%
  );
  border-top: 1px solid rgba(75, 91, 147, 0.15);
  font-size: 0.83rem;
}
.isp-totals-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--inv-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.isp-totals-value {
  font-size: 0.95rem;
  font-weight: 800;
  color: #059669;
}

/* ── Stock count badge (inline in qty cell) ─────────── */
.inv-stock-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 0.3rem;
  border-radius: 999px;
  background: rgba(75, 91, 147, 0.15);
  color: var(--inv-brand);
  font-size: 0.65rem;
  font-weight: 800;
  vertical-align: middle;
}
.inv-code-chip {
  display: inline-block;
  padding: 0.22rem 0.6rem;
  border-radius: 7px;
  background: rgba(75, 91, 147, 0.08);
  color: var(--inv-brand);
  font-size: 0.78rem;
  font-weight: 800;
  font-family: ui-monospace, monospace;
  border: 1px solid rgba(75, 91, 147, 0.15);
}
.inv-count-badge {
  display: inline-block;
  min-width: 40px;
  padding: 0.2rem 0.5rem;
  border-radius: 20px;
  background: rgba(75, 91, 147, 0.1);
  color: var(--inv-brand);
  font-size: 0.78rem;
  font-weight: 800;
  text-align: center;
}
.inv-date-cell {
  color: var(--inv-muted);
  font-size: 0.8rem;
}
.inv-row-actions {
  display: inline-flex;
  gap: 0.3rem;
}
.inv-action-btn {
  width: 30px;
  height: 30px;
  border: 1px solid var(--inv-border);
  border-radius: 8px;
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.88rem;
  cursor: pointer;
  transition: all 0.15s;
}
.inv-action-btn.view:hover {
  background: #e0f2fe;
  border-color: #38bdf8;
  color: #0284c7;
}
.inv-action-btn.edit:hover {
  background: #fef3c7;
  border-color: #f59e0b;
  color: #d97706;
}
.inv-action-btn.stock:hover {
  background: #dcfce7;
  border-color: #4ade80;
  color: #16a34a;
}
.inv-action-btn.del:hover {
  background: #fee2e2;
  border-color: #f87171;
  color: #dc2626;
}
.inv-loading-state,
.inv-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 3rem 1rem;
  color: var(--inv-muted);
}
.inv-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--inv-brand);
  border-radius: 50%;
  animation: inv-spin 0.7s linear infinite;
}
@keyframes inv-spin {
  to {
    transform: rotate(360deg);
  }
}
.inv-empty-state i {
  font-size: 2.5rem;
  opacity: 0.35;
}
.inv-empty-state p {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 500;
}
.inv-pagination-bar {
  padding: 0.6rem 1rem;
  border-top: 1px solid var(--inv-border);
  background: #fafbff;
}
.del-modal-icon {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  background: #fee2e2;
  color: #dc2626;
  font-size: 1.6rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.inv-grid-shell {
  padding: 1rem;
  max-height: calc(100vh - 300px);
  overflow: auto;
}
.inv-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
  gap: 0.85rem;
}
.inv-stock-card {
  border: 1px solid var(--inv-border);
  border-radius: 16px;
  overflow: hidden;
  background: var(--inv-surface);
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;
  display: flex;
  flex-direction: column;
}
.inv-stock-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.1);
}
.inv-stock-card-top {
  --card-hue: 220;
  position: relative;
  height: 110px;
  background: radial-gradient(
      circle at 70% 30%,
      hsla(var(--card-hue), 70%, 75%, 0.3),
      transparent 55%
    ),
    linear-gradient(
      135deg,
      hsl(var(--card-hue), 50%, 28%) 0%,
      hsl(var(--card-hue), 60%, 20%) 100%
    );
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.inv-stock-card-icon {
  font-size: 2.8rem;
  color: rgba(255, 255, 255, 0.18);
}
.inv-stock-card-actions {
  position: absolute;
  top: 0.6rem;
  right: 0.6rem;
  display: flex;
  gap: 0.3rem;
  opacity: 0;
  transform: translateY(-4px);
  transition: opacity 0.2s, transform 0.2s;
}
.inv-stock-card:hover .inv-stock-card-actions {
  opacity: 1;
  transform: translateY(0);
}
.inv-stock-card-actions button {
  width: 30px;
  height: 30px;
  border: 0;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.88);
  color: #0f172a;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  cursor: pointer;
  backdrop-filter: blur(6px);
  transition: background 0.15s;
}
.inv-stock-card-actions button:hover {
  background: #fff;
}
.inv-stock-card-actions button.del:hover {
  background: #fee2e2;
  color: #dc2626;
}
.inv-stock-card-code {
  position: absolute;
  bottom: 0.6rem;
  left: 0.65rem;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.22);
  backdrop-filter: blur(6px);
  color: #fff;
  padding: 0.18rem 0.55rem;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 800;
  font-family: ui-monospace, monospace;
}
.inv-stock-card-body {
  padding: 0.85rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
.inv-stock-card-name {
  margin: 0;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--inv-ink);
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.inv-stock-card-stats {
  display: flex;
  align-items: center;
  background: var(--inv-bg);
  border: 1px solid var(--inv-border);
  border-radius: 10px;
  overflow: hidden;
}
.inv-stock-stat {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem 0.4rem;
}
.inv-stock-stat-sep {
  width: 1px;
  height: 32px;
  background: var(--inv-border);
  flex-shrink: 0;
}
.inv-stock-stat-val {
  font-size: 1rem;
  font-weight: 800;
  color: var(--inv-ink);
}
.inv-stock-stat-lbl {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--inv-muted);
  margin-top: 1px;
}
.inv-muted-val {
  color: var(--inv-muted);
  font-size: 0.8rem;
}
.inv-custom-order-note {
  font-size: 0.72rem;
  color: var(--inv-muted);
  font-weight: 600;
  padding-top: 0.3rem;
}

/* toolbar classes needed by the inline items toolbar */
.inv-toolbar {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-wrap: wrap;
}
.inv-search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.inv-search-icon {
  position: absolute;
  left: 0.65rem;
  color: var(--inv-muted);
  font-size: 0.95rem;
  pointer-events: none;
}
.inv-search-input {
  height: 38px;
  padding: 0 2rem 0 2.1rem;
  border: 1px solid var(--inv-border);
  border-radius: 10px;
  background: #fff;
  color: var(--inv-ink);
  font-size: 0.84rem;
  width: 220px;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.inv-search-input:focus {
  border-color: var(--inv-brand);
  box-shadow: 0 0 0 3px rgba(75, 91, 147, 0.1);
}
.inv-search-input::placeholder {
  color: #94a3b8;
}
.inv-search-clear {
  position: absolute;
  right: 0.5rem;
  border: 0;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  font-size: 1rem;
}
.inv-select {
  height: 38px;
  padding: 0 0.7rem;
  border: 1px solid var(--inv-border);
  border-radius: 10px;
  background: #fff;
  color: var(--inv-ink);
  font-size: 0.84rem;
  font-weight: 600;
  outline: none;
  cursor: pointer;
}
.inv-icon-btn {
  width: 38px;
  height: 38px;
  border: 1px solid var(--inv-border);
  border-radius: 10px;
  background: #fff;
  color: var(--inv-muted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.inv-icon-btn:hover {
  background: var(--inv-brand-soft);
  color: var(--inv-brand);
}
.inv-view-toggle {
  display: flex;
  border: 1px solid var(--inv-border);
  border-radius: 10px;
  overflow: hidden;
}
.inv-view-toggle button {
  width: 38px;
  height: 38px;
  border: 0;
  background: #fff;
  color: var(--inv-muted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.inv-view-toggle button.active {
  background: var(--inv-brand);
  color: #fff;
}
.inv-view-toggle button:not(.active):hover {
  background: var(--inv-brand-soft);
}
.inv-create-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  height: 38px;
  padding: 0 1rem;
  border: 0;
  border-radius: 10px;
  background: linear-gradient(135deg, #4b5b93, #38467a);
  color: #fff;
  font-size: 0.84rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.15s;
}
.inv-create-btn:hover {
  opacity: 0.9;
}
.inv-module-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.85rem 1rem;
  background: linear-gradient(180deg, #f8fbff, #f0f5ff);
  border-bottom: 1px solid var(--inv-border);
  flex-wrap: wrap;
}
.inv-module-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.inv-module-header-icon {
  width: 40px;
  height: 40px;
  border-radius: 13px;
  background: linear-gradient(135deg, #4b5b93, #38467a);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.inv-module-title {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 800;
  color: var(--inv-ink);
}
.inv-module-subtitle {
  margin: 0;
  font-size: 0.72rem;
  color: var(--inv-muted);
  font-weight: 500;
}

/* ── Utils ──────────────────────────────────────────── */
.fs-12 {
  font-size: 12px;
}

/* ── Responsive ─────────────────────────────────────── */
@media (max-width: 991.98px) {
  .inv-hero-body {
    grid-template-columns: 1fr;
  }
  .inv-hero-stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 767.98px) {
  .inv-hero-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  .inv-tab-nav {
    padding: 0.5rem;
  }
  .ledger-toolbar-wrap {
    overflow-x: auto;
  }
  .inventory-sort-select {
    flex: 0 0 180px;
    min-width: 180px;
  }
  .withdrawal-create-btn {
    flex: 0 0 140px;
    min-width: 140px;
  }
}

@media (min-width: 1200px) {
  .inv-hero-stats {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

/* ── Dark mode ──────────────────────────────────────── */
:global([data-bs-theme="dark"]) .inventory-page,
:global([data-layout-mode="dark"]) .inventory-page {
  --inv-surface: #111827;
  --inv-bg: #0b1220;
  --inv-border: #2e3a59;
  --inv-muted: #9ca9c7;
  --inv-ink: #e5e7eb;
  --inv-shadow: rgba(0, 0, 0, 0.25);
  --inv-brand-soft: #1a2540;
  background: radial-gradient(circle at 12% 0%, rgba(14, 165, 233, 0.07), transparent 28%),
    radial-gradient(circle at 88% 8%, rgba(99, 102, 241, 0.06), transparent 28%), #0b1220;
}

:global([data-bs-theme="dark"]) .inv-hero,
:global([data-layout-mode="dark"]) .inv-hero {
  background: radial-gradient(
      circle at 92% 12%,
      rgba(14, 165, 233, 0.18),
      transparent 30%
    ),
    linear-gradient(135deg, #1c2b52 0%, #14203d 50%, #0d1628 100%);
  box-shadow: 0 22px 52px rgba(0, 0, 0, 0.3);
}

:global([data-bs-theme="dark"]) .inv-shell,
:global([data-layout-mode="dark"]) .inv-shell {
  background: #111827;
  border-color: #2e3a59;
  box-shadow: none;
}

:global([data-bs-theme="dark"]) .inv-tab-nav,
:global([data-layout-mode="dark"]) .inv-tab-nav {
  background: linear-gradient(180deg, #151e33, #111827);
  border-bottom-color: #2e3a59;
}

:global([data-bs-theme="dark"]) .inv-tab:not(.active),
:global([data-layout-mode="dark"]) .inv-tab:not(.active) {
  color: #9ca9c7;
}

:global([data-bs-theme="dark"]) .inv-tab:hover:not(.active),
:global([data-layout-mode="dark"]) .inv-tab:hover:not(.active) {
  background: #1a2540;
  color: #8ea0f4;
}

:global([data-bs-theme="dark"]) .inv-tab-content > :deep(.card),
:global([data-bs-theme="dark"]) .inv-tab-content > :deep(.ledger-card),
:global([data-layout-mode="dark"]) .inv-tab-content > :deep(.card),
:global([data-layout-mode="dark"]) .inv-tab-content > :deep(.ledger-card) {
  background: #111827 !important;
  border-color: #2e3a59 !important;
}

:global([data-bs-theme="dark"]) .inv-tab-content :deep(.card-header),
:global([data-bs-theme="dark"]) .inv-tab-content :deep(.card-body),
:global([data-bs-theme="dark"]) .inv-tab-content :deep(.bg-white),
:global([data-bs-theme="dark"]) .inv-tab-content :deep(.bg-light-subtle),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.card-header),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.card-body),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.bg-white),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.bg-light-subtle) {
  background: #111827 !important;
  color: #e5e7eb !important;
  border-color: #2e3a59 !important;
}

:global([data-bs-theme="dark"]) .inv-table-wrap,
:global([data-bs-theme="dark"]) .inv-table thead th,
:global([data-layout-mode="dark"]) .inv-table-wrap,
:global([data-layout-mode="dark"]) .inv-table thead th {
  border-color: #2e3a59 !important;
  background: #182035 !important;
  color: #dbeafe !important;
}

:global([data-bs-theme="dark"]) .inv-tab-content :deep(.table),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.table) {
  --bs-table-bg: #111827;
  --bs-table-color: #e5e7eb;
  --bs-table-hover-bg: #182035;
  --bs-table-border-color: #2e3a59;
  color: #e5e7eb !important;
  background-color: #111827 !important;
}

:global([data-bs-theme="dark"]) .inv-tab-content :deep(.table-light th),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.table-light th) {
  background: #182035 !important;
  color: #dbeafe !important;
}

:global([data-bs-theme="dark"]) .item-3d-card,
:global([data-layout-mode="dark"]) .item-3d-card {
  background: #111827;
  border-color: #2e3a59;
}

:global([data-bs-theme="dark"]) .item-card-icon-area,
:global([data-layout-mode="dark"]) .item-card-icon-area {
  background: linear-gradient(135deg, #182035, #1a2540);
}

:global([data-bs-theme="dark"]) .item-card-content,
:global([data-layout-mode="dark"]) .item-card-content {
  background: linear-gradient(180deg, #111827, #0f172a);
}

:global([data-bs-theme="dark"]) .item-title,
:global([data-layout-mode="dark"]) .item-title {
  color: #f8fafc;
}

:global([data-bs-theme="dark"]) .inventory-order-controls,
:global([data-layout-mode="dark"]) .inventory-order-controls {
  border-color: #2e3a59;
  background: #182035;
}

:global([data-bs-theme="dark"]) .inventory-order-btn,
:global([data-layout-mode="dark"]) .inventory-order-btn {
  background: #111827;
  color: #dbeafe;
}

:global([data-bs-theme="dark"]) .inv-tab-content :deep(.input-group-text),
:global([data-bs-theme="dark"]) .inv-tab-content :deep(.form-control),
:global([data-bs-theme="dark"]) .inv-tab-content :deep(.form-select),
:global([data-bs-theme="dark"]) .ledger-refresh-btn,
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.input-group-text),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.form-control),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.form-select),
:global([data-layout-mode="dark"]) .ledger-refresh-btn {
  border-color: #2e3a59 !important;
  background: #182035 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"]) .inv-tab-content :deep(.page-link),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.page-link) {
  border-color: #2e3a59 !important;
  background: #182035 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"]) .inv-tab-content :deep(.page-item.active .page-link),
:global([data-layout-mode="dark"]) .inv-tab-content :deep(.page-item.active .page-link) {
  border-color: #8ea0f4 !important;
  background: #8ea0f4 !important;
  color: #0f172a !important;
}
</style>
