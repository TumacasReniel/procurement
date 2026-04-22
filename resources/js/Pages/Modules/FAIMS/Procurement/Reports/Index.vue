<template>
    <Head title="Procurement Results" />
    <PageHeader title="Procurement Results" pageTitle="List" />

    <BRow class="procurement-index-page">
        <div class="col-md-12">
            <div class="card bg-light-subtle shadow-none border">
                <div class="card-header bg-light-subtle">
                    <div class="d-flex mb-n3">
                        <div class="flex-shrink-0 me-3">
                            <div style="height: 2.5rem; width: 2.5rem">
                                <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                                    <i class="ri-file-chart-line text-primary fs-24"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fs-14">
                                <span class="text-body">Procurement Results</span>
                            </h5>
                            <p class="text-muted text-truncate-two-lines fs-12">
                                A procurement evaluation sheet view for monitoring request progress
                                across the allowable procurement timeline.
                            </p>
                        </div>
                        <div class="flex-shrink-0 ms-auto d-flex gap-2">
                            <!-- <b-button
                                variant="outline-success"
                                size="sm"
                                class="d-inline-flex align-items-center gap-1 report-action-btn"
                                @click="downloadExcel"
                            >
                                <i class="ri-file-excel-line"></i>
                                Excel
                            </b-button> -->
                            <b-button
                                variant="outline-dark"
                                size="sm"
                                class="d-inline-flex align-items-center gap-1 report-action-btn"
                                @click="printReport"
                            >
                                <i class="ri-printer-line"></i>
                                Print
                            </b-button>
                        </div>
                    </div>
                </div>

                <div class="car-body bg-white border-bottom shadow-none">
                    <b-row class="mb-2 ms-1 me-1" style="margin-top: 12px">
                        <b-col lg>
                            <div class="input-group mb-1">
                                <span class="input-group-text">
                                    <i class="ri-search-line search-icon"></i>
                                </span>
                                <input
                                    type="text"
                                    v-model="filter.keyword"
                                    placeholder="Search Procurement Result"
                                    class="form-control"
                                    style="width: 30%"
                                />
                                <Multiselect
                                    class="white"
                                    style="width: 12%"
                                    :options="dropdowns.statuses"
                                    v-model="filter.status"
                                    label="name"
                                    :searchable="true"
                                    placeholder="Select Status"
                                />
                                <Multiselect
                                    class="white"
                                    style="width: 12%"
                                    :options="reportTypeOptions"
                                    v-model="filter.report_type"
                                    label="name"
                                    :searchable="true"
                                    value-prop="value"
                                    placeholder="Select Type"
                                />
                                <Multiselect
                                    class="white"
                                    style="width: 10%"
                                    :options="dateFilterOptions"
                                    v-model="filter.date_filter_type"
                                    :searchable="false"
                                    label="label"
                                    value-prop="value"
                                    placeholder="Filter Type"
                                />
                                <Multiselect
                                    v-if="filter.date_filter_type === 'monthly'"
                                    class="white"
                                    style="width: 10%"
                                    :options="monthOptions"
                                    v-model="filter.month"
                                    :searchable="false"
                                    label="label"
                                    value-prop="value"
                                    placeholder="Select Month"
                                />
                                <Multiselect
                                    v-if="filter.date_filter_type === 'monthly'"
                                    class="white"
                                    style="width: 8%"
                                    :options="yearOptions"
                                    v-model="filter.year"
                                    :searchable="true"
                                    label="label"
                                    value-prop="value"
                                    placeholder="Select Year"
                                />
                                <Multiselect
                                    v-else-if="filter.date_filter_type === 'quarterly'"
                                    class="white"
                                    style="width: 10%"
                                    :options="quarterOptions"
                                    v-model="filter.quarter"
                                    :searchable="false"
                                    label="label"
                                    value-prop="value"
                                    placeholder="Quarter"
                                />
                                <Multiselect
                                    v-else-if="filter.date_filter_type === 'yearly'"
                                    class="white"
                                    style="width: 10%"
                                    :options="yearOptions"
                                    v-model="filter.year"
                                    :searchable="true"
                                    label="label"
                                    value-prop="value"
                                    placeholder="Select Year"
                                />
                                <template v-else-if="filter.date_filter_type === 'range'">
                                    <input
                                        type="date"
                                        v-model="filter.start_date"
                                        class="form-control"
                                        style="width: 10%"
                                    />
                                    <input
                                        type="date"
                                        v-model="filter.end_date"
                                        class="form-control"
                                        style="width: 10%"
                                    />
                                </template>
                                <span
                                    @click="refresh()"
                                    class="input-group-text"
                                    v-b-tooltip.hover
                                    title="Refresh"
                                    style="cursor: pointer"
                                >
                                    <i class="bx bx-refresh search-icon"></i>
                                </span>
                            </div>
                        </b-col>
                    </b-row>
                </div>

                <div class="card-body bg-white rounded-bottom">
                    <div class="evaluation-sheet-wrap">
                        <div class="evaluation-sheet-header">
                            <div class="evaluation-sheet-title">
                                DOST IX PROCUREMENT EVALUATION SHEET
                            </div>
                            <div class="evaluation-sheet-subtitle">
                                {{ reportSubtitle }}
                            </div>
                        </div>

                        <div class="evaluation-sheet-meta">
                            <div>{{ reportPeriodLabel }}</div>
                            <div>{{ foLabel }}</div>
                        </div>

                        <div class="table-responsive evaluation-table-scroll">
                            <table class="table evaluation-table mb-0">
                                <thead>
                                    <tr>
                                        <th :colspan="isPrinting ? 13 : 14" class="evaluation-band">
                                            ALLOWABLE TIMELINE/PERIOD FOR PROCUREMENT PROCESS
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>APPROVED PURCHASE REQUEST NUMBER</th>
                                        <th>DATE APPROVED PURCHASE REQUEST</th>
                                        <th>Pre-Procurement Conference<br />(1 cd)</th>
                                        <th>Advertisement / Posting of Invitation to Bid<br />(7 cd)</th>
                                        <th>Pre-Bid Conference<br />(8-45 or 60 cd)</th>
                                        <th>Deadline of Submission and Receipt of Bids / Bid Opening<br />(1- 50 or 65 cd)</th>
                                        <th>Approval of Resolution/Issuance of Notice of Award<br />(1-15 cd)</th>
                                        <th>Contract Preparation and Signing<br />(1-10 cd)</th>
                                        <th>Approval of contract by higher authority<br />(1-20 or 30 cd)</th>
                                        <th>Bid Evaluation<br />(1-7 cd)</th>
                                        <th>Post-Qualification<br />(2-45 cd)</th>
                                        <th>Issuance of Notice to Proceed<br />(1-7 cd)</th>
                                        <th>Total No. of Days</th>
                                        <th v-if="!isPrinting">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in filteredLists" :key="item.id">
                                        <td>{{ item.code }}</td>
                                        <td>{{ formatSheetDate(item.date) }}</td>
                                        <td>N/A</td>
                                        <td>N/A</td>
                                        <td>N/A</td>
                                        <td>{{ formatTimelineDate(item.updated_at || item.date) }}</td>
                                        <td>{{ formatTimelineDate(item.updated_at || item.date) }}</td>
                                        <td>{{ contractSigningDate(item.updated_at || item.date) }}</td>
                                        <td>N/A</td>
                                        <td>{{ formatTimelineDate(item.updated_at || item.date) }}</td>
                                        <td>{{ formatTimelineDate(item.updated_at || item.date) }}</td>
                                        <td>N/A</td>
                                        <td>{{ totalDays(item.date, item.updated_at || item.date) }}</td>
                                        <td v-if="!isPrinting" class="status-cell">
                                            <span class="status-chip">
                                                {{ item.status?.name || "N/A" }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="index in fillerRowCount"
                                        :key="`filler-${index}`"
                                        class="filler-row"
                                    >
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td v-if="!isPrinting">&nbsp;</td>
                                    </tr>
                                    <tr v-if="filteredLists.length === 0">
                                        <td :colspan="isPrinting ? 13 : 14" class="text-center text-muted py-4">
                                            No procurement results available.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="evaluation-signatories print-only">
                            <div class="signatory-headings">
                                <div class="signatory-heading signatory-heading-left">
                                    Prepared by:
                                </div>
                                <div class="signatory-heading ">
                                    Noted by:
                                </div>
                            </div>

                            <div class="signatory-grid">
                                <div
                                    v-for="(signatory, index) in preparedBySignatories"
                                    :key="`prepared-${index}`"
                                    class="signatory-card"
                                >
                                    <div class="signatory-name">{{ signatory.name }}</div>
                                    <div class="signatory-role">{{ signatory.role }}</div>
                                </div>

                                <div v-if="signatories?.supply_officer" class="signatory-card">
                                    <div class="signatory-name">
                                        {{ signatories.supply_officer.name }}
                                    </div>
                                    <div class="signatory-role">
                                        {{ signatories.supply_officer.role }}
                                    </div>
                                </div>

                                <div v-if="signatories?.noted_by" class="signatory-card">
                                    <div class="signatory-name">
                                        {{ signatories.noted_by.name }}
                                    </div>
                                    <div class="signatory-role">
                                        {{ signatories.noted_by.designation }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="print-page-number print-only">Page 1 of 1</div>
                    </div>

                    <div class="card-footer px-0 pb-0">
                        <Pagination
                            class="ms-2 me-2 mt-2"
                            v-if="meta"
                            @fetch="fetch"
                            :lists="lists.length"
                            :links="links"
                            :pagination="meta"
                        />
                    </div>
                </div>
            </div>
        </div>
    </BRow>
</template>

<script>
import _ from "lodash";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import Multiselect from "@vueform/multiselect";

export default {
    components: { PageHeader, Pagination, Multiselect },
    props: ["dropdowns", "signatories"],
    data() {
        return {
            lists: [],
            meta: {},
            links: {},
            dateFilterOptions: [
                { label: "Monthly", value: "monthly" },
                { label: "Quarterly", value: "quarterly" },
                { label: "Yearly", value: "yearly" },
                { label: "Date Range", value: "range" },
            ],
            reportTypeOptions: [
                { name: "Goods and Services", value: "goods_and_services" },
                { name: "Infrastracture Projects", value: "infrastructure" },
                { name: "Consulting Services", value: "consulting" },
            ],
            quarterOptions: [
                { label: "Q1", value: "Q1" },
                { label: "Q2", value: "Q2" },
                { label: "Q3", value: "Q3" },
                { label: "Q4", value: "Q4" },
            ],
            monthOptions: [
                { label: "January", value: "01" },
                { label: "February", value: "02" },
                { label: "March", value: "03" },
                { label: "April", value: "04" },
                { label: "May", value: "05" },
                { label: "June", value: "06" },
                { label: "July", value: "07" },
                { label: "August", value: "08" },
                { label: "September", value: "09" },
                { label: "October", value: "10" },
                { label: "November", value: "11" },
                { label: "December", value: "12" },
            ],
            yearOptions: this.buildYearOptions(),
            filter: {
                keyword: null,
                report_type: "goods_and_services",
                status: null,
                date_filter_type: "monthly",
                month: String(new Date().getMonth() + 1).padStart(2, "0"),
                quarter: "Q1",
                year: new Date().getFullYear(),
                start_date: null,
                end_date: null,
            },
            isPrinting: false,
        };
    },
    computed: {
        preparedBySignatories() {
            const prepared = this.signatories?.prepared_by || [];

            if (prepared.length > 0) {
                return prepared;
            }

            return [
                { name: "________________________", role: "Procurement Staff" },
                { name: "________________________", role: "Procurement Staff" },
            ];
        },
        fillerRowCount() {
            const minimumRows = 12;
            const count = minimumRows - this.filteredLists.length;
            return count > 0 ? count : 0;
        },
        reportSubtitle() {
            const selectedType = this.reportTypeOptions.find(
                (type) => type.value === this.filter.report_type,
            );

            return (selectedType?.name || "Goods and Services").toUpperCase();
        },
        foLabel() {
            const foMap = {
                goods_and_services: "F.O.: 26 to 136 calendar days",
                infrastructure: "F.O.: 26 to *141 or *156 calendar days",
                consulting: "F.O.: 36 to 180 calendar days",
            };

            return foMap[this.filter.report_type] || foMap.goods_and_services;
        },
        reportPeriodLabel() {
            if (this.filter.date_filter_type === "quarterly") {
                return `Quarter of ${this.filter.quarter || "-"}, ${this.filter.year || "-"}`;
            }

            if (this.filter.date_filter_type === "yearly") {
                return `Year of ${this.filter.year || "-"}`;
            }

            if (this.filter.date_filter_type === "range") {
                const start = this.filter.start_date || "-";
                const end = this.filter.end_date || "-";
                return `Date Range: ${start} to ${end}`;
            }

            const monthValue = this.filter.month || new Date().toISOString().slice(0, 7);
            const date = new Date(Number(this.filter.year), Number(monthValue) - 1, 1);

            return `Month of ${date
                .toLocaleDateString("en-US", {
                    month: "long",
                    year: "numeric",
                })
                .toUpperCase()}`;
        },
        filteredLists() {
            return this.lists.filter((item) => {
                if (!this.matchesSelectedReportType(item)) return false;

                if (!item.date) return true;

                const itemDate = this.parseItemDate(item.date);
                if (Number.isNaN(itemDate.getTime())) return true;

                if (this.filter.date_filter_type === "quarterly") {
                    const month = itemDate.getMonth() + 1;
                    const quarter =
                        month <= 3 ? "Q1" : month <= 6 ? "Q2" : month <= 9 ? "Q3" : "Q4";
                    return quarter === this.filter.quarter;
                }

                if (this.filter.date_filter_type === "yearly") {
                    return itemDate.getFullYear() === Number(this.filter.year);
                }

                if (this.filter.date_filter_type === "range") {
                    const start = this.filter.start_date ? new Date(this.filter.start_date) : null;
                    const end = this.filter.end_date ? new Date(this.filter.end_date) : null;

                    if (start && itemDate < start) return false;
                    if (end) {
                        end.setHours(23, 59, 59, 999);
                        if (itemDate > end) return false;
                    }
                    return true;
                }

                if (!this.filter.month) return true;
                return (
                    String(itemDate.getMonth() + 1).padStart(2, "0") === this.filter.month &&
                    itemDate.getFullYear() === Number(this.filter.year)
                );
            });
        },
    },
    watch: {
        "filter.keyword"() {
            this.checkSearchStr();
        },
        "filter.status"() {
            this.fetch();
        },
        "filter.report_type"() {
            this.fetch();
        },
        "filter.date_filter_type"() {
            this.resetDateFilterValues();
        },
    },
    created() {
        this.fetch();
    },
    methods: {
        checkSearchStr: _.debounce(function () {
            this.fetch();
        }, 300),
        fetch(page_url) {
            page_url = page_url || "/faims/procurements";
            axios
                .get(page_url, {
                    params: {
                        keyword: this.filter.keyword,
                        report_type: this.filter.report_type,
                        status: this.filter.status,
                        count: 50,
                        option: "lists",
                    },
                })
                .then((response) => {
                    this.lists = response.data.data;
                    this.meta = response.data.meta;
                    this.links = response.data.links;
                })
                .catch((err) => console.log(err));
        },
        formatSheetDate(date) {
            if (!date) return "N/A";

            return this.parseItemDate(date)
                .toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short",
                    year: "2-digit",
                })
                .replace(/ /g, "-");
        },
        formatTimelineDate(date) {
            if (!date) return "N/A";

            return this.parseItemDate(date)
                .toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short",
                    year: "2-digit",
                })
                .replace(/ /g, "-");
        },
        contractSigningDate(date) {
            if (!date) return "N/A";

            const base = this.parseItemDate(date);
            base.setDate(base.getDate() + 10);
            return this.formatTimelineDate(base);
        },
        totalDays(start, end) {
            if (!start || !end) return "N/A";

            const startDate = new Date(start);
            const endDate = new Date(end);
            const diff = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

            return diff >= 0 ? diff : 0;
        },
        refresh() {
            this.filter.keyword = null;
            this.filter.report_type = "goods_and_services";
            this.filter.status = null;
            this.filter.date_filter_type = "monthly";
            this.resetDateFilterValues();
            this.fetch();
        },
        downloadPdf() {
            this.printReport();
        },
        downloadExcel() {
            const headers = [
                "APPROVED PURCHASE REQUEST NUMBER",
                "DATE APPROVED PURCHASE REQUEST",
                "Pre-Procurement Conference (1 cd)",
                "Advertisement / Posting of Invitation to Bid (7 cd)",
                "Pre-Bid Conference (8-45 or 60 cd)",
                "Deadline of Submission and Receipt of Bids / Bid Opening (1-50 or 65 cd)",
                "Approval of Resolution/Issuance of Notice of Award (1-15 cd)",
                "Contract Preparation and Signing (1-10 cd)",
                "Approval of contract by higher authority (1-20 or 30 cd)",
                "Bid Evaluation (1-7 cd)",
                "Post-Qualification (2-45 cd)",
                "Issuance of Notice to Proceed (1-7 cd)",
                "Total No. of Days",
                "Status",
            ];

            const rows = this.filteredLists.map((item) => [
                item.code,
                this.formatSheetDate(item.date),
                "N/A",
                "N/A",
                "N/A",
                this.formatTimelineDate(item.updated_at || item.date),
                this.formatTimelineDate(item.updated_at || item.date),
                this.contractSigningDate(item.updated_at || item.date),
                "N/A",
                this.formatTimelineDate(item.updated_at || item.date),
                this.formatTimelineDate(item.updated_at || item.date),
                "N/A",
                this.totalDays(item.date, item.updated_at || item.date),
                item.status?.name || "N/A",
            ]);

            const csv = [headers, ...rows]
                .map((row) =>
                    row
                        .map((value) => `"${String(value ?? "").replace(/"/g, '""')}"`)
                        .join(","),
                )
                .join("\r\n");

            const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = `procurement-results-${this.fileSafePeriodLabel()}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },
        printReport() {
            const statusValue =
                this.filter.status && typeof this.filter.status === "object"
                    ? this.filter.status.value ?? this.filter.status.id ?? null
                    : this.filter.status;
            const params = new URLSearchParams({
                option: "print",
                report_type: this.filter.report_type || "goods_and_services",
                date_filter_type: this.filter.date_filter_type || "monthly",
            });

            if (this.filter.keyword) {
                params.set("keyword", this.filter.keyword);
            }

            if (statusValue) {
                params.set("status", statusValue);
            }

            if (this.filter.year) {
                params.set("year", this.filter.year);
            }

            if (this.filter.month) {
                params.set("month", this.filter.month);
            }

            if (this.filter.quarter) {
                params.set("quarter", this.filter.quarter);
            }

            if (this.filter.start_date) {
                params.set("start_date", this.filter.start_date);
            }

            if (this.filter.end_date) {
                params.set("end_date", this.filter.end_date);
            }

            window.open(`/faims/procurement-reports?${params.toString()}`, "_blank");
        },
        parseItemDate(date) {
            if (date instanceof Date) {
                return new Date(date);
            }

            const raw = String(date || "").trim();
            const parsed = new Date(raw);
            if (!Number.isNaN(parsed.getTime())) {
                return parsed;
            }

            const match = raw.match(/^([A-Za-z]+)\s+(\d{1,2}),\s+(\d{4})$/);
            if (match) {
                const monthMap = {
                    january: 0,
                    february: 1,
                    march: 2,
                    april: 3,
                    may: 4,
                    june: 5,
                    july: 6,
                    august: 7,
                    september: 8,
                    october: 9,
                    november: 10,
                    december: 11,
                };

                const monthIndex = monthMap[match[1].toLowerCase()];
                const day = Number(match[2]);
                const year = Number(match[3]);

                if (monthIndex !== undefined) {
                    return new Date(year, monthIndex, day);
                }
            }

            return new Date("invalid");
        },
        resetDateFilterValues() {
            this.filter.month = String(new Date().getMonth() + 1).padStart(2, "0");
            this.filter.quarter = "Q1";
            this.filter.year = new Date().getFullYear();
            this.filter.start_date = null;
            this.filter.end_date = null;
        },
        buildYearOptions() {
            const currentYear = new Date().getFullYear();
            const years = [];

            for (let year = currentYear + 2; year >= currentYear - 10; year--) {
                years.push({
                    label: String(year),
                    value: year,
                });
            }

            return years;
        },
        fileSafePeriodLabel() {
            if (this.filter.date_filter_type === "quarterly") {
                return `${this.filter.quarter}-${this.filter.year}`;
            }

            if (this.filter.date_filter_type === "yearly") {
                return `${this.filter.year}`;
            }

            if (this.filter.date_filter_type === "range") {
                return `${this.filter.start_date || "start"}-to-${this.filter.end_date || "end"}`;
            }

            return `${this.filter.year}-${this.filter.month}`;
        },
        matchesSelectedReportType(item) {
            const reportTypeModes = {
                goods_and_services: [
                    "Competitive Public Bidding",
                    "Limited Source Bidding",
                ],
                infrastructure: [],
                consulting: [],
            };

            const allowedModes = reportTypeModes[this.filter.report_type] ?? [];

            if (!allowedModes.length) {
                return false;
            }

            const itemModes = (item.codes || [])
                .map((code) => code?.procurement_code?.mode_of_procurement?.name)
                .filter(Boolean);

            return itemModes.some((mode) => allowedModes.includes(mode));
        },
    },
};
</script>

<style scoped>
.evaluation-sheet-wrap {
    border: 1px solid #222;
    background: #fff;
    box-shadow: inset 0 0 0 1px #222;
}

.evaluation-sheet-header {
    padding: 0.85rem 1rem 0.45rem;
    text-align: center;
    border-bottom: 1px solid #222;
}

.evaluation-sheet-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #111827;
}

.evaluation-sheet-subtitle {
    font-size: 1rem;
    font-weight: 700;
    color: #111827;
}

.evaluation-sheet-meta {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.45rem 0.35rem;
    border-bottom: 1px solid #222;
    font-size: 0.88rem;
}

.evaluation-table-scroll {
    max-height: calc(100vh - 360px);
    overflow: auto;
}

.evaluation-table {
    min-width: 1750px;
    border-collapse: collapse;
}

.evaluation-table th,
.evaluation-table td {
    border: 1px solid #222;
    text-align: center;
    vertical-align: middle;
    padding: 0.45rem 0.4rem;
    font-size: 0.8rem;
    color: #111827;
}

.evaluation-table thead th {
    background: #d7e6fa;
    font-weight: 700;
    line-height: 1.15;
}

.filler-row td {
    height: 32px;
}

.evaluation-band {
    background: #eef3d0 !important;
    font-size: 0.86rem !important;
}

.evaluation-table thead tr:nth-child(2) th:last-child {
    background: #dbe8b4;
}

.report-action-btn {
    border-radius: 8px;
    white-space: nowrap;
    min-height: auto;
}

.status-cell {
    text-align: center;
}

.status-chip {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #d7e6fa;
    color: #0f2f57;
    font-weight: 700;
    line-height: 1.2;
}

:deep(.report-action-btn.btn),
:deep(.report-action-btn.btn-sm) {
    padding: 0.18rem 0.5rem !important;
    font-size: 0.76rem !important;
    line-height: 1.15 !important;
}

:deep(.report-action-btn i) {
    font-size: 0.82rem !important;
}

.print-only {
    display: none;
}

.evaluation-signatories {
    margin-top: 1rem;
    padding: 0.8rem 0.35rem 0 0.35rem;
}

.signatory-headings {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.signatory-heading {
    font-size: 0.85rem;
    color: #111827;
}

.signatory-heading-left {
    grid-column: 1 / span 3;
    text-align: left;
}

.signatory-heading-right {
    grid-column: 4;
    text-align: right;
}

.signatory-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    align-items: end;
}

.signatory-card {
    text-align: center;
    min-height: 48px;
}

.signatory-name {
    font-weight: 700;
    text-transform: uppercase;
    line-height: 1.2;
}

.signatory-role {
    line-height: 1.2;
}

.print-page-number {
    display: none;
}

@media print {
    @page {
        size: landscape;
        margin: 5mm 6mm 6mm 6mm;
    }

    :global(body) {
        background: #fff !important;
        font-family: Arial, sans-serif !important;
        font-size: 9px !important;
    }

    :global(.page-content),
    :global(.main-content),
    :global(.content-wrapper),
    :global(.container-fluid),
    :global(.row),
    :global(.col-md-12) {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    :global(.navbar-header),
    :global(.app-menu),
    :global(.vertical-menu),
    :global(.page-title-box),
    :global(.card-header),
    :global(.car-body),
    :global(.card-footer),
    :global(.btn),
    :global(.input-group),
    :global(.multiselect),
    :global(.multiselect__tags),
    :global(.multiselect__select) {
        display: none !important;
    }

    .card,
    .card-body {
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #fff !important;
    }

    .evaluation-sheet-wrap {
        border: 1px solid #222 !important;
        box-shadow: inset 0 0 0 1px #222 !important;
        width: 100% !important;
        padding-bottom: 105px !important;
    }

    .evaluation-sheet-header {
        padding: 0.15rem 0.3rem 0.1rem !important;
        border-bottom: 1px solid #000 !important;
    }

    .evaluation-sheet-title {
        font-size: 16pt !important;
        font-weight: 700 !important;
        color: #000 !important;
    }

    .evaluation-sheet-subtitle {
        font-size: 11pt !important;
        font-weight: 700 !important;
        color: #000 !important;
    }

    .evaluation-sheet-meta {
        padding: 0.1rem 0.2rem !important;
        border-bottom: 1px solid #000 !important;
        font-size: 9pt !important;
        color: #000 !important;
    }

    .evaluation-table-scroll {
        max-height: none !important;
        overflow: visible !important;
    }

    .evaluation-table {
        min-width: 0 !important;
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        border: 1.5px solid #000 !important;
    }

    .evaluation-table thead {
        display: table-header-group !important;
    }

    .evaluation-table th,
    .evaluation-table td {
        border: 1px solid #000 !important;
        color: #000 !important;
        font-size: 7.5px !important;
        line-height: 1.1 !important;
        padding: 3px 4px !important;
        word-break: break-word !important;
        white-space: normal !important;
        background: #fff !important;
        vertical-align: top !important;
    }

    .evaluation-table thead th {
        background: #d7e6fa !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        text-align: center !important;
        font-weight: bold !important;
    }

    .evaluation-band {
        background: #eef3d0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .evaluation-table thead tr:nth-child(2) th:last-child {
        background: #dbe8b4 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-only {
        display: block !important;
    }

    .evaluation-signatories {
        position: fixed !important;
        left: 6mm !important;
        right: 6mm !important;
        bottom: 14mm !important;
        page-break-inside: avoid !important;
        margin-top: 0 !important;
        padding: 8px 10px 6px 10px !important;
        min-height: 95px !important;
        background: #fff !important;
    }

    .signatory-headings {
        gap: 12px !important;
        margin-bottom: 38px !important;
    }

    .signatory-heading {
        font-size: 8.5pt !important;
        color: #000 !important;
    }

    .signatory-grid {
        gap: 12px !important;
        align-items: end !important;
    }

    .signatory-card {
        min-height: auto !important;
        padding-top: 0 !important;
    }

    .signatory-name {
        font-size: 9pt !important;
        font-weight: 700 !important;
        color: #000 !important;
        min-height: auto !important;
        text-decoration: underline !important;
    }

    .signatory-role {
        font-size: 8.5pt !important;
        color: #000 !important;
        margin-top: 1px !important;
    }

    .print-page-number {
        display: block !important;
        position: fixed !important;
        right: 6mm !important;
        bottom: 3mm !important;
        font-size: 8pt !important;
        color: #000 !important;
        text-align: right !important;
        padding: 0 !important;
    }
}
</style>
