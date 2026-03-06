<template>
    <PageHeader class="m-3 mt-4" title="Abstract of Bids" />

    <div>
        <b-row class="align-items-center">
            <!-- Left Content -->

            <!-- Right-Aligned Action Button -->
            <b-col class="text-end">
                <button
                    v-if="
                        !isForApprovalOfBACResolution &&
                        (((this.procurement.status?.name ==
                            'For BAC Resolution' ||
                            (this.procurement.status?.name === 'Rebid' &&
                                this.procurement.sub_status?.name ===
                                    'For BAC Resolution')) &&
                            $page.props.roles.includes(
                                'Procurement Officer',
                            )) ||
                        $page.props.roles.includes('Procurement Staff'))
                    "
                    @click="openBACReso(procurement)"
                    class="btn btn-outline-primary btn-sm me-2"
                    v-b-tooltip.hover
                    title="Generate BAC Resolution"
                >
                    <i class="ri-file-line"></i>
                    Generate BAC Resolution
                </button>

                <button
                    v-if="procurement.quotations.length > 0"
                    @click="printBids(procurement)"
                    class="btn btn-outline-dark btn-sm me-2"
                    v-b-tooltip.hover
                    title="Print"
                >
                    <i class="ri-printer-line"></i>
                    Print AOB
                </button>
            </b-col>
        </b-row>
    </div>

    <div
        v-if="
            procurement.quotations.filter((bid) => bid.status_id == 46)
                .length === 0
        "
        class="text-center py-5"
    >
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ri-auction-line"></i>
            </div>
            <h6 class="empty-state-title">No Abstract of Bids</h6>
            <p class="empty-state-message">
                No bids have been submitted for this procurement yet.
            </p>
        </div>
    </div>

    <b-tabs v-else class="horizontal-scroll-tabs bg-white" card>
        <template
            v-for="(bid, bidIndex) in procurement.quotations"
            :key="bid.id"
        >
            <b-tab v-if="bid.status_id == 46">
                <template #title v-if="bid.status_id == 46">
                    {{ bid.supplier.name }}

                    <b-badge
                        variant="primary"
                        v-if="getCheckedBidsCount(bid.items) > 0"
                    >
                        {{ getCheckedBidsCount(bid.items) }}
                    </b-badge>
                </template>

                <div>
                    <div
                        class="w-100 pt-0 pb-0 mb-3"
                        style="height: calc(100vh - 305px); overflow: auto"
                        ref="box"
                    >
                        <div>
                            <table
                                style="
                                    width: 100%;
                                    border-collapse: collapse;
                                    border: 1px solid;
                                "
                            >
                                <thead>
                                    <tr>
                                        <th style="width: 2px">Item No</th>
                                        <th style="width: 20px">Status</th>
                                        <th style="width: 500px">
                                            Item Description
                                        </th>
                                        <th style="width: 20px">
                                            Quantity/Unit
                                        </th>
                                        <th style="width: 20px">Unit Cost</th>
                                        <th style="width: 20px">ABC</th>
                                        <th style="width: 20px">Bid Price</th>
                                        <th style="width: 20px">
                                            Total Bid Price
                                        </th>
                                        <th style="width: 500px">
                                            Technical Proposal / Offer
                                        </th>
                                        <th style="width: 100px">
                                            Delivery Term
                                        </th>

                                        <th
                                            v-if="
                                                !isForBACResolution &&
                                                (procurement.status.name ==
                                                    'For Bids' ||
                                                (this.procurement.status
                                                    .name === 'Rebid' &&
                                                    this.procurement.sub_status
                                                        ?.name === 'For Bids' &&
                                                    $page.props.roles.includes(
                                                        'Procurement Officer',
                                                    )) ||
                                                $page.props.roles.includes(
                                                    'Procurement Staff',
                                                ))
                                            "
                                        >
                                            Recommend Bid For Award?
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(item, itemIndex) in bid.items"
                                        :key="item.item_id"
                                    >
                                        <td>{{ itemIndex + 1 }}</td>
                                        <td>
                                            <b-badge
                                                v-if="item.status"
                                                :variant="
                                                    getBadgeVariant(
                                                        item.status.name,
                                                    )
                                                "
                                                style="color: white"
                                            >
                                                {{ item.status.name }}
                                                <i
                                                    v-if="
                                                        item.status.name ==
                                                        'Not Available for Award'
                                                    "
                                                    class="ri-close-line"
                                                ></i>
                                                <i
                                                    v-if="
                                                        item.status.name ==
                                                        'Available for Award'
                                                    "
                                                    class="ri-check-line"
                                                ></i>
                                                <i
                                                    v-if="
                                                        item.status.name ==
                                                        'Awarded'
                                                    "
                                                    class="ri-check-line"
                                                ></i>
                                            </b-badge>
                                        </td>
                                        <td
                                            style="
                                                text-align: left;
                                                width: break-word;
                                                word-break: break-word;
                                                white-space: normal;
                                            "
                                        >
                                            <span
                                                v-html="
                                                    item.item.item_description
                                                "
                                            ></span>
                                        </td>

                                        <td>
                                            {{ item.item.item_quantity }}
                                            {{
                                                item.item.item_quantity > 1
                                                    ? item.item.item_unit_type
                                                          .name_long
                                                    : item.item.item_unit_type
                                                          .name_short
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    item.item.item_unit_cost,
                                                )
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    item.item.total_cost,
                                                )
                                            }}
                                        </td>

                                        <td @click="openEditOffer(item)">
                                            <span
                                                v-if="item.bid_price > 0"
                                                :class="{
                                                    'text-danger':
                                                        item.bid_price >
                                                        item.item
                                                            .item_unit_cost,
                                                }"
                                            >
                                                <u>{{
                                                    formatCurrency(
                                                        item.bid_price,
                                                    )
                                                }}</u>
                                            </span>
                                            <span
                                                v-else-if="item.bid_price == 0"
                                            >
                                                <b
                                                    ><i class="text-primary"
                                                        ><u>free</u></i
                                                    ></b
                                                >
                                            </span>
                                            <span v-else>
                                                <b
                                                    ><i class="text-primary"
                                                        ><u>not set</u></i
                                                    ></b
                                                >
                                            </span>
                                        </td>
                                        <td>
                                            <span v-if="item.bid_price == 0">
                                                <b
                                                    ><i class="text-primary"
                                                        >free</i
                                                    ></b
                                                >
                                            </span>
                                            <span
                                                v-else-if="item.bid_price > 0"
                                            >
                                                {{
                                                    formatCurrency(
                                                        item.item
                                                            .item_quantity *
                                                            item.bid_price,
                                                    )
                                                }}
                                            </span>
                                            <span v-else>
                                                <b
                                                    ><i class="text-primary"
                                                        >not set</i
                                                    ></b
                                                >
                                            </span>
                                        </td>

                                        <td
                                            style="
                                                text-align: left;
                                                width: break-word;
                                                word-break: break-word;
                                                white-space: normal;
                                            "
                                        >
                                            <div
                                                v-html="item.technical_proposal"
                                            ></div>
                                        </td>
                                        <td>{{ bid.delivery_term }}</td>

                                        <td
                                            v-if="
                                                !isForBACResolution &&
                                                (((procurement.status.name ==
                                                    'For Bids' ||
                                                    (this.procurement.status
                                                        .name === 'Rebid' &&
                                                        this.procurement
                                                            .sub_status
                                                            ?.name ===
                                                            'For Bids')) &&
                                                    $page.props.roles.includes(
                                                        'Procurement Officer',
                                                    )) ||
                                                $page.props.roles.includes(
                                                    'Procurement Staff',
                                                ))
                                            "
                                        >
                                            <span
                                                class="d-flex justify-content-center"
                                            >
                                                <b-form-checkbox
                                                    v-model="item.is_checked"
                                                    name="checkbox"
                                                    class="border-primary bg-primary"
                                                    :value="true"
                                                    :disabled="
                                                        isOtherSupplierChecked(
                                                            itemIndex,
                                                            bid,
                                                        ) ||
                                                        item.bid_price == null
                                                    "
                                                    @change="
                                                        handleCheckboxChange(
                                                            itemIndex,
                                                            bid,
                                                        )
                                                    "
                                                >
                                                </b-form-checkbox>
                                            </span>
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
                        </div>
                    </div>
                </div>
            </b-tab>
        </template>

        <div class="input-group mb-1">
            <b-button
                v-if="
                    !isForBACResolution &&
                    (((procurement?.status.name == 'For Bids' ||
                        procurement?.sub_status?.name == 'For Bids') &&
                        $page.props.roles.includes('Procurement Officer')) ||
                    $page.props.roles.includes('Procurement Staff'))
                "
                class="text-end"
                @click="openRecommendAward()"
                variant="primary"
                block
                >Save Bids For Award</b-button
            >
        </div>
    </b-tabs>

    <Offer ref="editOffer" />
    <Award ref="award" :procurement="procurement" />
    <BACResolution ref="BACReso" :procurement="procurement" :action="'Award'" />
</template>
<script>
import Checkbox from "@/Shared/Components/Forms/Checkbox.vue";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Multiselect from "@vueform/multiselect";
import Award from "../Modals/Award.vue";
import BACResolution from "../Modals/BACResolution.vue";
import Offer from "../Modals/Offer.vue";

export default {
    components: {
        PageHeader,
        InputError,
        InputLabel,
        TextInput,
        Multiselect,
        Checkbox,
        Offer,
        Award,
        BACResolution,
    },
    props: ["procurement", "dropdowns", "option"],
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
            is_checked: false,
            checkedItems: [],
            recommendedBidsForAward: {},
            showBACResoForm: false,
        };
    },

    computed: {
        isForBACResolution() {
            return (
                this.procurement?.status?.name === "For BAC Resolution" ||
                (this.procurement?.status?.name === "Rebid" &&
                    this.procurement?.sub_status?.name === "For BAC Resolution") ||
                this.procurement?.status?.name === "For Approval of BAC Resolution" ||
                (this.procurement?.status?.name === "Rebid" &&
                    this.procurement?.sub_status?.name === "For Approval of BAC Resolution")
            );
        },
        isForApprovalOfBACResolution() {
            return (
                this.procurement?.status?.name === "For Approval of BAC Resolution" ||
                (this.procurement?.status?.name === "Rebid" &&
                    this.procurement?.sub_status?.name === "For Approval of BAC Resolution")
            );
        },
    },

    methods: {
        openEditOffer(item) {
            if (
                this.procurement.status?.name == "For Bids" ||
                (this.procurement.status.name === "Rebid" &&
                    this.procurement.sub_status?.name === "For Bids")
            ) {
                this.$refs.editOffer.edit(item);
            }
        },

        openRecommendAward() {
            this.$refs.award.edit(this.checkedItems);
        },

        getCurrentDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, "0"); // Months are zero-based
            const day = String(today.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        },

        formatCurrency(value) {
            return new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
            }).format(value);
        },

        getBadgeVariant(status_name) {
            switch (status_name) {
                case "Not Available for Award/Re-award":
                    return "danger"; // Maps to Bootstrap's warning variant
                case "Not Awarded":
                    return "warning"; // Maps to Bootstrap's warning variant
                case "For Bids":
                    return "info"; // Maps to Bootstrap's info variant
                case "Awarded":
                    return "success"; // Maps to Bootstrap's success variant
                case "Completed":
                    return "dark"; // Maps to Bootstrap's dark variant
                default:
                    return "secondary"; // Default variant if none match
            }
        },

        handleCheckboxChange(itemIndex, currentBid) {
            // If checked → store supplier name
            if (currentBid.items[itemIndex].is_checked) {
                this.checkedItems[itemIndex] = currentBid;
            } else {
                delete this.checkedItems[itemIndex];
            }
        },

        isOtherSupplierChecked(itemIndex, currentItem) {
            return (
                this.checkedItems[itemIndex] &&
                this.checkedItems[itemIndex] !== currentItem
            );
        },

        //count how how many items checked in a supplier
        getCheckedBidsCount(items) {
            return items.filter((item) => item.is_checked).length;
        },

        getTotalBidPrice() {
            this.form.total_bid_price =
                this.form.bid_price * this.form.item_quantity;
            return this.form.total_bid_price;
        },

        openEditItemBidOffer(bid_item) {
            if (this.procurement.status_id == 4) {
                this.$refs.editItem.edit(bid_item);
            }
        },
        openBACReso(data) {
            this.$refs.BACReso.show("Award");
        },

        printBids(data) {
            window.open(
                `/faims/procurements/${data.id}?option=print&type=abstract_of_bids`,
            );
        },
    },
};
</script>

<style>
.horizontal-scroll-tabs .nav-tabs .nav-link {
    background-color: white !important;
    color: black !important; /* Ensure text is visible */
    border-bottom: 5px lightgrey solid;
    border-top: 5px lightgrey solid;
    width: 200px;
}

/* Change background when tab is active */
.horizontal-scroll-tabs .nav-tabs .nav-link.active {
    border-bottom: 5px darkblue solid;
    border-top: 5px darkblue solid;
    font-weight: bolder;
    color: darkblue !important;
}
</style>

<style scoped>
td,
th {
    border: 1px solid;
    padding: 5px;
    vertical-align: top;
    text-align: center;
}
</style>
