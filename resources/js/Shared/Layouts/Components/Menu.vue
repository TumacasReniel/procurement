<template>
    <BContainer fluid>
        <div id="two-column-menu"></div>
        <ul class="navbar-nav h-100" id="navbar-nav">
            <li class="nav-item">
                <Link href="/dashboard" class="nav-link menu-link"
                :class="{ 'active': $page.url === '/dashboard' || $page.url === '/'}">
                <i class="ri-apps-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Dashboard</span>
                </Link>
            </li>
            <template v-if="$page.props.user.data.signatory">
                <li class="nav-item">
                    <Link href="/approvals" class="nav-link menu-link"
                        :class="{'active': $page.component.startsWith('Modules/Portal/Approvals') }">
                    <i class="ri-ball-pen-line"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">For Approval</span>
                    </Link>
                </li>
            </template>
            <li class="nav-item">
                <Link href="/requests" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Modules/Portal/Requests') }">
                <i class="ri-list-check-2"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">My Requests</span>
                </Link>
            </li>
            <li class="nav-item">
                <Link href="/dtr" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Modules/Portal/Dtr') }">
                <i class="ri-alarm-fill"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Daily Time Record</span>
                </Link>
            </li>
            <li class="menu-title">
            <i class="ri-more-fill" aria-expanded="false"></i>
                <span data-key="t-menu">Procurement</span>
            </li>
            <li
                class="nav-item"
                v-if="
                $page.props.roles.includes('Procurement Officer') ||
                $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/procurement-dashboard"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Procurement/Dashboard'),
                }"
                >
                <i class="ri-apps-fill"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Dashboard</span>
                </Link>
            </li>
            <li class="nav-item">
                <Link
                href="/faims/procurements"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Procurement/Index') || ($page.component.startsWith('Modules/FAIMS/Procurement/View') && !isProcurementProcessActive),
                }"
                >
                <i class="ri-file-list-3-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Request</span>
                </Link>
            </li>
            <li
                class="nav-item"
            >
                <Link
                href="/faims/procurement-assignments"
                class="nav-link menu-link"
                :class="{ active: $page.component.startsWith('Modules/FAIMS/Procurement/Assignments') }"
                >
                <i class="ri-route-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Assignment</span>
                </Link>
            </li>
            <li
                class="nav-item"
                v-if="
                $page.props.roles.includes('Procurement Officer') ||
                $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/procurement-codes"
                class="nav-link menu-link"
                >
                <i class="ri-code-box-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">PAP Codes</span>
                </Link>
                

                <Link
                href="/faims/responsibility-centers"
                class="nav-link menu-link"
                >
                <i class="ri-code-box-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Responsibility Centers</span>
                </Link>
            </li>

            <li
                class="nav-item"
                v-if="
                $page.props.roles.includes('Procurement Staff') ||
                $page.props.roles.includes('Procurement Officer') ||
                $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/suppliers"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Procurement/Suppliers'),
                }"
                >
                <i class="ri-truck-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Suppliers</span>
                </Link>
            </li>
            <li
                class="nav-item"
                v-if="
                $page.props.roles.includes('Procurement Staff') ||
                $page.props.roles.includes('Procurement Officer') ||
                $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/bac-resolutions"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Procurement/BACResolution'),
                }"
                >
                <i class="ri-government-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">BAC Resolutions</span>
                </Link>
            </li>
            <li
                class="nav-item"
                v-if="
                $page.props.roles.includes('Procurement Staff') ||
                $page.props.roles.includes('Procurement Officer') ||
                $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/notice-of-awards"
                class="nav-link menu-link"
                :class="{ active: $page.component.startsWith('Modules/FAIMS/Procurement/NOA') }"
                >
                <i class="ri-file-text-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Notice of Awards</span>
                </Link>
            </li>

            <li
                class="nav-item"
                v-if="
                $page.props.roles.includes('Procurement Staff') ||
                $page.props.roles.includes('Procurement Officer') ||
                $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/purchase-orders"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/HumanResource/Employees'),
                }"
                >
                <i class="ri-file-paper-2-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Purchase Orders</span>
                </Link>
            </li>
            <li
                class="nav-item"
                v-if="
                $page.props.roles.includes('Procurement Staff') ||
                $page.props.roles.includes('Procurement Officer') ||
                $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/procurement-followers"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Procurement/Followers'),
                }"
                >
                <i class="ri-user-follow-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Status Assignee</span>
                </Link>
            </li>
            <li class="menu-title">
                <i class="ri-more-fill" aria-expanded="false"></i>
                <span data-key="t-menu">Finance</span>
            </li>
            <li class="nav-item"
                v-if="
                    $page.props.roles.includes('Finance Staff') ||
                    $page.props.roles.includes('Finance Officer') ||
                    $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/finance-dashboard"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Finance/Dashboard'),
                }"
                >
                <i class="ri-dashboard-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Dashboard</span>
                </Link>
            </li>
            <li class="nav-item"
            >
                <Link
                href="/faims/finance-requests"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Finance/Index') ||
                        $page.component.startsWith('Modules/FAIMS/Finance/View'),
                }"
                >
                <i class="ri-file-list-3-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Requests</span>
                </Link>
            </li>
            <li class="nav-item"
                 v-if="
                    $page.props.roles.includes('Finance Staff') ||
                    $page.props.roles.includes('Finance Officer') ||
                    $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/finance-disbursements-obligations"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Finance/DisbursementsObligations'),
                }"
                >
                <i class="ri-bank-card-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Disbursements & Obligations</span>
                </Link>
            </li>
            <li class="nav-item"
               v-if="
                    $page.props.roles.includes('Finance Staff') ||
                    $page.props.roles.includes('Finance Officer') ||
                    $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/finance-request-types"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Finance/RequestTypes'),
                }"
                >
                <i class="ri-shapes-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Request Types</span>
                </Link>
            </li>
            <li class="nav-item">
                <Link
                v-if="
                $page.props.roles.includes('Finance Staff') ||
                $page.props.roles.includes('Finance Officer') ||
                $page.props.roles.includes('Administrator')
                "
                href="/faims/finance-documents"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Finance/Documents'),
                }"
                >
                <i class="ri-file-copy-2-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Documents</span>
                </Link>
            </li>
            <li class="nav-item"
                 v-if="
                    $page.props.roles.includes('Finance Staff') ||
                    $page.props.roles.includes('Finance Officer') ||
                    $page.props.roles.includes('Administrator')
                "
                >
                <Link
                href="/faims/finance-projects"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Finance/Projects'),
                }"
                >
                <i class="ri-building-2-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Projects</span>
                </Link>
            </li>
            <li class="nav-item"
               v-if="
                    $page.props.roles.includes('Finance Staff') ||
                    $page.props.roles.includes('Finance Officer') ||
                    $page.props.roles.includes('Administrator')
                "
            >
                <Link
                href="/faims/finance-creditors"
                class="nav-link menu-link"
                :class="{
                    active: $page.component.startsWith('Modules/FAIMS/Finance/Creditors'),
                }"
                >
                <i class="ri-user-star-line"></i>
                <span class="fw-semibold fs-14" data-key="t-dashboards">Creditors</span>
                </Link>
            </li>

                        <template v-if="
                $page.props.roles.includes('Supply Officer') ||
                $page.props.roles.includes('Employee') ||
                $page.props.roles.includes('Administrator')
            ">
                <li class="menu-title">
                    <i class="ri-more-fill" aria-expanded="false"></i>
                    <span data-key="t-menu">Inventory</span>
                </li>
                <li class="nav-item">
                    <Link
                        href="/inventory-dashboard"
                        class="nav-link menu-link"
                        :class="{
                            active: $page.component.startsWith('Modules/Inventory/Dashboard'),
                        }"
                    >
                        <i class="ri-dashboard-line"></i>
                        <span class="fw-semibold fs-14" data-key="t-dashboards">Dashboard</span>
                    </Link>
                </li>

                <li class="nav-item">
                    <Link
                        href="/inventory-stocks"
                        class="nav-link menu-link"
                        :class="{
                            active: $page.component.startsWith('Modules/InventoryStocks'),
                        }"
                    >
                        <i class="ri-dashboard-line"></i>
                        <span class="fw-semibold fs-14" data-key="t-dashboards">Stocks</span>
                    </Link>
                </li>
            </template>

            <template v-if="$page.props.roles.includes('Document Management Officer')">
                <li class="menu-title">
                    <i class="ri-more-fill" aria-expanded="false"></i>
                    <span data-key="t-menu">Document Management</span>
                </li>
                <li class="nav-item">
                    <Link href="/humanresource" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Modules/Trace/Dashboard') }">
                    <i class="ri-apps-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Dashboard</span>
                    </Link>
                </li>
                <li class="nav-item">
                    <Link href="/events" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Modules/Trace/Events') }">
                    <i class="ri-calendar-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Events</span>
                    </Link>
                </li>
            </template>
            <template v-if="$page.props.roles.includes('Human Resource Officer')">
                <li class="menu-title">
                    <i class="ri-more-fill" aria-expanded="false"></i>
                    <span data-key="t-menu">Human Resource</span>
                </li>
                <li class="nav-item">
                    <Link href="/humanresource" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Modules/HumanResource/Dashboard') }">
                    <i class="ri-apps-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Dashboard</span>
                    </Link>
                </li>
                <li class="nav-item">
                    <Link href="/employees" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Modules/HumanResource/Employees') }">
                    <i class="ri-team-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Employees</span>
                    </Link>
                </li>
                <li class="nav-item">
                    <Link href="/dtrs" class="nav-link menu-link"
                        :class="{'active': $page.component.startsWith('Modules/HumanResource/Dtr') }">
                    <i class="ri-alarm-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Daily Time Record</span>
                    </Link>
                </li>
                <li class="nav-item">
                   <BLink class="nav-link menu-link" href="#payroll"
                   :class="{'active': $page.url.startsWith('Modules/HumanResource/Payroll') }"
                    data-bs-toggle="collapse" role="button" :aria-expanded="$page.url.startsWith('/payroll')" aria-controls="payroll">
                        <i class="ri-hand-coin-fill"></i>
                        <span data-key="t-dashboards">Payroll</span>
                    </BLink>
                    <div class="collapse menu-dropdown" id="payroll">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <Link href="/payroll/regular" :class="{'active': $page.component.startsWith('Modules/HumanResource/Payroll/Regular') }" class="nav-link" data-key="t-basic">
                                    Regular
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link href="/payroll/contractual" :class="{'active': $page.component.startsWith('Modules/HumanResource/Payroll/Contractual') }" class="nav-link" data-key="t-basic">
                                    Contractual
                                </Link>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <Link href="/credits" class="nav-link menu-link"
                        :class="{'active': $page.component.startsWith('Modules/HumanResource/Credits') }">
                    <i class="ri-file-list-line"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Leave Credits</span>
                    </Link>
                </li>
                <li class="nav-item">
                    <Link href="/surveys" class="nav-link menu-link"
                        :class="{'active': $page.component.startsWith('Modules/HumanResource/Surveys') }">
                    <i class="ri-article-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Morale Surveys</span>
                    </Link>
                </li>
                <li class="nav-item">
                    <Link href="/calendar" class="nav-link menu-link"
                        :class="{'active': $page.component.startsWith('Modules/HumanResource/Calendar') }">
                    <i class="ri-calendar-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Calendar</span>
                    </Link>
                </li>
                <li class="nav-item">
                    <Link href="/visitors" class="nav-link menu-link"
                        :class="{'active': $page.component.startsWith('Modules/HumanResource/Visitors') }">
                    <i class="ri-group-2-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Visitors</span>
                    </Link>
                </li>
            </template>
            <template v-if="$page.props.roles.includes('Asset Management Officer')">
                <li class="menu-title">
                    <i class="ri-more-fill" aria-expanded="false"></i>
                    <span data-key="t-menu">Assets Management</span>
                </li>
                <li class="nav-item">
                    <Link href="/vehicles" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Modules/Assets/Vehicles') }">
                    <i class="ri-car-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Vehicles</span>
                    </Link>
                </li>
                <li class="nav-item">
                    <Link href="/buildings" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Modules/Assets/Buildings') }">
                    <i class="ri-government-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Buildings</span>
                    </Link>
                </li>
                <li class="nav-item">
                    <Link href="/equipments" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Modules/Assets/Equipments') }">
                    <i class="ri-mac-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">Equipments</span>
                    </Link>
                </li>
            </template>
            <template v-if="$page.props.roles.includes('Administrator')">
                <li class="menu-title">
                    <i class="ri-more-fill" aria-expanded="false"></i>
                    <span data-key="t-menu">Executive Modules</span>
                </li>
                <li class="nav-item">
                    <Link href="/users" class="nav-link menu-link"
                    :class="{'active': $page.component.startsWith('Executive/Users') }">
                    <i class="ri-team-fill"></i>
                    <span class="fw-semibold fs-14" data-key="t-dashboards">User Management</span>
                    </Link>
                </li>
                <li class="nav-item">
                    <BLink class="nav-link menu-link" href="#sidebarDashboards"
                    :class="{'active': $page.url.startsWith('Modules/System/References') }"
                    data-bs-toggle="collapse" role="button" :aria-expanded="$page.url.startsWith('/references')" aria-controls="sidebarDashboards">
                        <i class="ri-database-2-fill"></i>
                        <span data-key="t-dashboards">References</span>
                    </BLink>
                    <div class="collapse menu-dropdown" id="sidebarDashboards">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <Link href="/references/units" :class="{'active': $page.component.startsWith('Executive/References/Units') }" class="nav-link" data-key="t-basic">
                                    Units
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link href="/references/positions" :class="{'active': $page.component.startsWith('Executive/References/Positions') }" class="nav-link" data-key="t-basic">
                                    Positions
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link href="/references/leaves" :class="{'active': $page.component.startsWith('Executive/References/Leaves') }" class="nav-link" data-key="t-basic">
                                    Leaves
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link href="/references/salaries" :class="{'active': $page.component.startsWith('Executive/References/Salaries') }" class="nav-link" data-key="t-basic">
                                    Salaries
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link href="/references/deductions" :class="{'active': $page.component.startsWith('Executive/References/Deductions') }" class="nav-link" data-key="t-basic">
                                    Deductions
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link href="/references/dropdowns" :class="{'active': $page.component.startsWith('Executive/References/Dropdowns') }" class="nav-link" data-key="t-basic">
                                    Dropdowns
                                </Link>
                            </li>
                        </ul>
                    </div>
                </li>
            </template>
        </ul>
    </BContainer>
</template>
<script>
import { layoutComputed } from "@/Shared/State/helpers";
import simplebar from "simplebar-vue";
export default {
    components: {
        simplebar,
    },
    data() {
        return {
            currentUrl: window.location.origin,
            menus: [],
            settings: {
                minScrollbarLength: 60,
            },
        };
    },
    computed: {
        ...layoutComputed,
        layoutType: {
            get() {
                return this.$store ? this.$store.state.layout.layoutType : {} || {};
            },
        },
        isProcurementProcessActive() {
            if (!this.$page.component.startsWith("Modules/FAIMS/Procurement/View")) {
                return false;
            }
            const query = this.$page.url.split("?")[1] || "";
            return new URLSearchParams(query).get("tab") === "7";
        },
    },
    mounted() {
        this.initActiveMenu();
        this.onRoutechange();
        // this.fetch();
        if (document.querySelectorAll(".navbar-nav .collapse")) {
            let collapses = document.querySelectorAll(".navbar-nav .collapse");

            collapses.forEach((collapse) => {
                // Hide sibling collapses on `show.bs.collapse`
                collapse.addEventListener("show.bs.collapse", (e) => {
                    e.stopPropagation();
                    let closestCollapse = collapse.parentElement.closest(".collapse");
                    if (closestCollapse) {
                        let siblingCollapses =
                            closestCollapse.querySelectorAll(".collapse");
                        siblingCollapses.forEach((siblingCollapse) => {
                            if (siblingCollapse.classList.contains("show")) {
                                siblingCollapse.classList.remove("show");
                                siblingCollapse.parentElement.firstChild.setAttribute(
                                    "aria-expanded", "false");
                            }
                        });
                    } else {
                        let getSiblings = (elem) => {
                            // Setup siblings array and get the first sibling
                            let siblings = [];
                            let sibling = elem.parentNode.firstChild;
                            // Loop through each sibling and push to the array
                            while (sibling) {
                                if (sibling.nodeType === 1 && sibling !== elem) {
                                    siblings.push(sibling);
                                }
                                sibling = sibling.nextSibling;
                            }
                            return siblings;
                        };
                        let siblings = getSiblings(collapse.parentElement);
                        siblings.forEach((item) => {
                            if (item.childNodes.length > 2) {
                                item.firstElementChild.setAttribute("aria-expanded",
                                    "false");
                                item.firstElementChild.classList.remove("active");
                            }
                            let ids = item.querySelectorAll("*[id]");
                            ids.forEach((item1) => {
                                item1.classList.remove("show");
                                item1.parentElement.firstChild.setAttribute(
                                    "aria-expanded", "false");
                                item1.parentElement.firstChild.classList.remove(
                                    "active");
                                if (item1.childNodes.length > 2) {
                                    let val = item1.querySelectorAll("ul li a");

                                    val.forEach((subitem) => {
                                        if (subitem.hasAttribute(
                                                "aria-expanded"))
                                            subitem.setAttribute(
                                                "aria-expanded", "false");
                                    });
                                }
                            });
                        });
                    }
                });

                // Hide nested collapses on `hide.bs.collapse`
                collapse.addEventListener("hide.bs.collapse", (e) => {
                    e.stopPropagation();
                    let childCollapses = collapse.querySelectorAll(".collapse");
                    childCollapses.forEach((childCollapse) => {
                        let childCollapseInstance = childCollapse;
                        childCollapseInstance.classList.remove("show");
                        childCollapseInstance.parentElement.firstChild.setAttribute(
                            "aria-expanded", "false");
                    });
                });
            });
        }
    },

    methods: {
        checkUrl() {
            const path = window.location.pathname;
            return path.split('/')[1] || null;
        },
        onRoutechange() {
            // this.initActiveMenu();
            setTimeout(() => {
                var currentPath = window.location.pathname;
                if (document.querySelector("#navbar-nav")) {
                    let currentPosition = document.querySelector("#navbar-nav").querySelector('[href="' +
                        currentPath + '"]') ?.offsetTop;
                    if (currentPosition > document.documentElement.clientHeight) {
                        document.querySelector("#scrollbar .simplebar-content-wrapper") ? document
                            .querySelector("#scrollbar .simplebar-content-wrapper").scrollTop =
                            currentPosition + 300 : '';
                    }
                }
            }, 500);
        },

        initActiveMenu() {
            setTimeout(() => {
                var currentPath = window.location.pathname;
                if (document.querySelector("#navbar-nav")) {
                    let a = document.querySelector("#navbar-nav").querySelector('[href="' + currentPath +
                        '"]');
                    if (a) {
                        a.classList.add("active");
                        let parentCollapseDiv = a.closest(".collapse.menu-dropdown");
                        if (parentCollapseDiv) {
                            parentCollapseDiv.classList.add("show");
                            parentCollapseDiv.parentElement.children[0].classList.add("active");
                            parentCollapseDiv.parentElement.children[0].setAttribute("aria-expanded",
                                "true");
                            if (parentCollapseDiv.parentElement.closest(".collapse.menu-dropdown")) {
                                parentCollapseDiv.parentElement.closest(".collapse").classList.add("show");
                                if (parentCollapseDiv.parentElement.closest(".collapse")
                                    .previousElementSibling)
                                    parentCollapseDiv.parentElement.closest(".collapse")
                                    .previousElementSibling.classList.add("active");
                                const grandparent = parentCollapseDiv.parentElement.closest(".collapse")
                                    .previousElementSibling.parentElement.closest(".collapse");
                                if (grandparent && grandparent && grandparent.previousElementSibling) {
                                    grandparent.previousElementSibling.classList.add("active");
                                    grandparent.classList.add("show");
                                }
                            }
                        }
                    }
                }
            }, 0);
        },
    },
};
</script>
