<template>
  <PageHeader class="m-3 mt-4" title="Quotation Requests" />
  <b-row class="g-2 mb-3 mt-n2">
    <b-col lg>
      <div class="input-group mb-1">
        <span class="input-group-text"> <i class="ri-search-line search-icon"></i></span>
        <input
          type="text"
          v-model="filter.keyword"
          placeholder="Search Quotation Request"
          class="form-control"
          style="width: 60%"
        />
        <span
          @click="refresh()"
          class="input-group-text"
          v-b-tooltip.hover
          title="Refresh"
          style="cursor: pointer"
        >
          <i class="bx bx-refresh search-icon"></i>
        </span>
        <b-button
          type="button"
          variant="primary"
          @click="openCreate()"
          v-if="
            $page.props.roles.includes('Procurement Officer') ||
            $page.props.roles.includes('Procurement Staff')
          "
        >
          <i class="ri-add-circle-fill align-bottom me-1"></i> New
        </b-button>
      </div>
    </b-col>
  </b-row>
  <b-card no-body>
    <table class="table mb-0">
      <thead class="table-light">
        <tr class="fs-11">
          <th>#</th>
          <th>RFQ No.</th>
          <th>Submission not later than</th>
          <th>Supplier</th>
          <th>Supply Officer</th>
          <th>Status</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>

      <tbody>
        <tr class="custom-hover-row" v-for="(list, index) in lists" :key="index">
          <td>{{ index + 1 }}</td>
          <td>{{ list.code }}</td>
          <td>{{ list.submission_not_later_than }}</td>
          <td style="line-height: 0.1">
            <p>{{ list.supplier?.name }}</p>

            <p class="text-muted">
              {{ list.supplier.address.address }}
            </p>
          </td>
          <td>{{ list.supply_officer }}</td>

          <td>
            <b-badge :class="list.status.bg">
              {{ list.status.name }}
            </b-badge>
          </td>

          <td class="text-center">
            <b-button @click="print(list)" variant="dark" size="sm" class="me-2 mb-2">
              <i class="ri-printer-fill align-bottom "></i>
              <!-- Icon for Print -->
            </b-button>

            <b-button
              @click="removeRFQ(list)"
              variant="danger"
              size="sm"
              class="me-2 mb-2"
              v-if="
                $page.props.roles.includes('Procurement Officer') ||
                $page.props.roles.includes('Procurement Staff')
              "
            >
              <i class="ri-delete-bin-line"></i>
            </b-button>
          </td>
        </tr>
        <tr v-if="lists.length === 0">
          <td colspan="7" class="text-center py-5">
            <div class="empty-state">
              <div class="empty-state-icon">
                <i class="ri-file-list-3-line"></i>
              </div>
              <h6 class="empty-state-title">No Quotation Requests</h6>
              <p class="empty-state-message">No quotation requests have been created for this procurement yet.</p>
             
            </div>
          </td>
        </tr>
      </tbody>
    </table>
    <Pagination
      class="ms-2 me-2"
      v-if="meta"
      @fetch="fetch"
      :lists="lists.length"
      :links="links"
      :pagination="meta"
    />

    <Quotation
      @add="fetch()"
      :procurement="procurement"
      :dropdowns="dropdowns"
      :rfqs="lists"
      ref="create"
    />

    <Delete @delete="fetch()" ref="delete" />
  </b-card>
</template>
<script>
import _ from "lodash";
import Delete from "../Modals/Delete.vue";
import Quotation from "../Modals/Quotation.vue";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
export default {
  props: ["procurement", "dropdowns"],
  components: { PageHeader, Pagination, Quotation, Delete },
  data() {
    return {
      currentUrl: window.location.origin,
      lists: [],
      meta: {},
      links: {},
      filter: {
        keyword: null,
      },
      index: null,
    };
  },
  watch: {
    "filter.keyword"(newVal) {
      this.checkSearchStr(newVal);
    },
  },
  created() {
    this.fetch();
  },
  methods: {
    checkSearchStr: _.debounce(function (string) {
      this.fetch();
    }, 300),
    fetch(page_url) {
      page_url = "/faims/quotations";
      axios
        .get(page_url, {
          params: {
            keyword: this.filter.keyword,
            option: "lists",
            procurement_id: this.procurement.id,
          },
        })
        .then((response) => {
          if (response) {
            this.lists = response.data.data;
            this.meta = response.data.meta;
            this.links = response.data.links;
          }
        })
        .catch((err) => console.log(err));
    },

    openCreate() {
      this.$refs.create.show();
    },

    editRFQ(data, lists) {
      this.$refs.create.edit(data, lists);
    },

    // // remove RFQ
    removeRFQ(data) {
      this.$refs.delete.show(data, "Request for Quotation");
    },

    print(data) {
      window.open(`/faims/quotations/${data.id}?option=print&type=quotations`);
    },

    goBackPage() {
      this.$inertia.visit("/faims/procurements");
    },
  },
};
</script>

<style scoped>
.custom-hover-row:hover {
  background-color: hsl(0, 29%, 97%);
}
</style>
