<template>
    <header id="page-topbar">
        <div class="layout-width">
            <div class="navbar-header">
                <div class="d-flex">
                    <div class="navbar-brand-box horizontal-logo">
                        <Link href="/" class="logo logo-dark">
                            <span class="logo-sm">
                                <img src="@assets/images/logo-sm.png" alt="" height="22" />
                            </span>
                            <span class="logo-lg">
                                <img src="@assets/images/logo-dark.png" alt="" height="17" />
                            </span>
                        </Link>
                        <Link href="/" class="logo logo-light">
                            <span class="logo-sm">
                                <img src="@assets/images/logo-sm.png" alt="" height="22" />
                            </span>
                            <span class="logo-lg">
                                <img src="@assets/images/logo-light.png" alt="" height="17" />
                            </span>
                        </Link>
                    </div>

                    <BButton variant="white" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger  material-shadow-none" id="topnav-hamburger-icon">
                        <span class="hamburger-icon">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </BButton>
                </div>

                <div class="d-flex align-items-center">
                    <BDropdown class="dropdown" variant="ghost-secondary" dropstart :offset="{ alignmentAxis: 57, crossAxis: 0, mainAxis: -42 }" toggle-class="btn-icon btn-topbar rounded-circle mode-layout ms-1 material-shadow-none" no-caret menu-class="p-0 dropdown-menu-end">
                        <template #button-content>
                            <i class="bx bx-category-alt fs-22"></i>
                        </template>
                        <div
                            class="p-3 border-top-0 dropdown-head border-start-0 border-end-0 border-dashed border dropdown-menu-lg">
                            <BRow class="align-items-center">
                                <BCol>
                                    <h6 class="m-0 fw-semibold fs-15">Web Apps</h6>
                                </BCol>
                                <BCol cols="auto">
                                    <BLink href="#!" class="btn btn-sm btn-soft-info">
                                        View All Apps
                                        <i class="ri-arrow-right-s-line align-middle"></i>
                                    </BLink>
                                </BCol>
                            </BRow>
                        </div>

                        <div class="p-2">
                            <BRow class="g-0">
                                <BCol>
                                    <BLink class="dropdown-icon-item" @click="openInNewTab('/key-officials')">
                                        <img src="@assets/images/apps/customer.png" alt="invent" />
                                        <span>Key Officials</span>
                                    </BLink>
                                </BCol>
                                <BCol>
                                    <BLink class="dropdown-icon-item" @click="openInNewTab('/bac-committee')">
                                        <img src="@assets/images/apps/customer.png" alt="invent" />
                                        <span>BAC Committee</span>
                                    </BLink>
                                </BCol>
                                <BCol>
                                    <BLink class="dropdown-icon-item" @click="openInNewTab('/iar-committee')">
                                        <img src="@assets/images/apps/customer.png" alt="invent" />
                                        <span>IAR Committee</span>
                                    </BLink>
                                </BCol>
                                <BCol>
                                    <BLink class="dropdown-icon-item" @click="openInNewTab('/schedules')">
                                        <img src="@assets/images/apps/calendar.png" alt="chatbox" />
                                        <span>Calendar</span>
                                    </BLink>
                                </BCol>
                                <BCol>
                                    <BLink class="dropdown-icon-item" href="#!">
                                        <img src="@assets/images/brands/dribbble.png" alt="dribbble" />
                                        <span>Dribbble</span>
                                    </BLink>
                                </BCol>
                            </BRow>

                            <!-- <BRow class="g-0">
                                <BCol>
                                    <BLink class="dropdown-icon-item" href="#!">
                                        <img src="@assets/images/brands/dropbox.png" alt="dropbox" />
                                        <span>Dropbox</span>
                                    </BLink>
                                </BCol>
                                <BCol>
                                    <BLink class="dropdown-icon-item" href="#!">
                                        <img src="@assets/images/brands/mail_chimp.png" alt="mail_chimp" />
                                        <span>Mail Chimp</span>
                                    </BLink>
                                </BCol>
                                <BCol>
                                    <BLink class="dropdown-icon-item" href="#!">
                                        <img src="@assets/images/brands/slack.png" alt="slack" />
                                        <span>Slack</span>
                                    </BLink>
                                </BCol>
                            </BRow> -->
                        </div>
                    </BDropdown>

                    <div class="ms-1 header-item d-none d-sm-flex">
                        <BButton type="button" variant="ghost-secondary" class="btn-icon btn-topbar rounded-circle material-shadow-none" data-toggle="fullscreen" @click="initFullScreen">
                            <i class="bx bx-fullscreen fs-22"></i>
                        </BButton>
                    </div>

                    <div class="ms-1 header-item d-none d-sm-flex">
                        <BButton type="button" variant="ghost-secondary" class="btn-icon btn-topbar rounded-circle light-dark-mode material-shadow-none" @click="toggleDarkMode">
                            <i class="bx bx-moon fs-22"></i>
                        </BButton>
                    </div>

                    <BDropdown variant="link" class="ms-sm-3 header-item topbar-user" toggle-class="rounded-circle material-shadow-none" no-caret menu-class="dropdown-menu-end" :offset="{ alignmentAxis: -14, crossAxis: 0, mainAxis: 0 }">
                        <template #button-content>
                            <span class="d-flex align-items-center">
                                <img class="rounded-circle header-profile-user" :src="$page.props.user.data.avatar" @error="setDefaultImage($event)" :alt="$page.props.user.data.username">
                                <span class="text-start ms-xl-2">
                                    <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ $page.props.user.data.name }}</span>
                                    <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">{{ $page.props.user.data.position }}</span>
                                </span>
                            </span>
                        </template>
                        <h6 class="dropdown-header">Welcome {{ $page.props.user.data.username }}!</h6>
                        <Link class="dropdown-item" href="/profile"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle">Profile</span>
                        </Link>
                        <!-- <div class="dropdown-divider"></div>
                        <Link class="dropdown-item" href="/chat">
                            <i class=" mdi mdi-message-text-outline text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle"> Messages</span>
                        </Link>
                        <Link class="dropdown-item" href="/apps/tasks-kanban">
                            <i class="mdi mdi-calendar-check-outline text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle"> Taskboard</span>
                        </Link>
                        <Link class="dropdown-item" href="/pages/faqs">
                            <i class="mdi mdi-lifebuoy text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle"> Help</span>
                        </Link> -->
                        <div class="dropdown-divider"></div>
                        <Link class="dropdown-item" href="/pages/profile-setting">
                            <!-- <BBadge variant="success-subtle" class="bg-success-subtle text-success mt-1 float-end">New</BBadge> -->
                            <i class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle"> Settings</span>
                        </Link>
                        <Link class="dropdown-item" href="/confirm-password">
                            <i class="mdi mdi-lock text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle"> Lock screen</span>
                        </Link>

                        <form method="POST" @submit.prevent="logout" class="dropdown-item">
                            <BButton variant="none" type="submit" class="btn p-0"><i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> Logout</BButton>
                        </form>
                    </BDropdown>
                </div>
            </div>
        </div>
    </header>
</template>
<script setup> 
import { router } from '@inertiajs/vue3';
import { layoutMethods } from "@/Shared/State/helpers";
const logout = () => {
  router.post('/logout');
}; 
</script>
<script>
import simplebar from "simplebar-vue";
export default {
    components: { simplebar },
    data() {
        return {
        text: null,
        value: null,
        myVar: 1,
        };
    },
    mounted() {
        document.addEventListener("scroll", function () {
        var pageTopbar = document.getElementById("page-topbar");
        if (pageTopbar) {
            document.body.scrollTop >= 50 || document.documentElement.scrollTop >= 50 ? pageTopbar.classList.add(
            "topbar-shadow") : pageTopbar.classList.remove("topbar-shadow");
        }
        });
        if (document.getElementById("topnav-hamburger-icon"))
            document.getElementById("topnav-hamburger-icon").addEventListener("click", this.toggleHamburgerMenu);

    },
    methods: {
        ...layoutMethods,
        toggleHamburgerMenu() {
            var windowSize = document.documentElement.clientWidth;
            let layoutType = document.documentElement.getAttribute("data-layout");

            document.documentElement.setAttribute("data-sidebar-visibility", "show");
            let visiblilityType = document.documentElement.getAttribute("data-sidebar-visibility");

            if (windowSize > 767)
                document.querySelector(".hamburger-icon").classList.toggle("open");

            //For collapse horizontal menu
            if (
                document.documentElement.getAttribute("data-layout") === "horizontal"
            ) {
                document.body.classList.contains("menu") ?
                document.body.classList.remove("menu") :
                document.body.classList.add("menu");
            }

            //For collapse vertical menu

            if (visiblilityType === "show" && (layoutType === "vertical" || layoutType === "semibox")) {
                if (windowSize < 1025 && windowSize > 767) {
                document.body.classList.remove("vertical-sidebar-enable");
                document.documentElement.getAttribute("data-sidebar-size") == "sm" ?
                    document.documentElement.setAttribute("data-sidebar-size", "") :
                    document.documentElement.setAttribute("data-sidebar-size", "sm");
                } else if (windowSize > 1025) {
                document.body.classList.remove("vertical-sidebar-enable");
                document.documentElement.getAttribute("data-sidebar-size") == "lg" ?
                    document.documentElement.setAttribute("data-sidebar-size", "sm") :
                    document.documentElement.setAttribute("data-sidebar-size", "lg");
                } else if (windowSize <= 767) {
                document.body.classList.add("vertical-sidebar-enable");
                document.documentElement.setAttribute("data-sidebar-size", "lg");
                }
            }

            //Two column menu
            if (document.documentElement.getAttribute("data-layout") == "twocolumn") {
                document.body.classList.contains("twocolumn-panel") ?
                document.body.classList.remove("twocolumn-panel") :
                document.body.classList.add("twocolumn-panel");
            }
            },
        toggleMenu() {
        this.$parent.toggleMenu();
        },
        toggleRightSidebar() {
        this.$parent.toggleRightSidebar();
        },
        initFullScreen() {
        document.body.classList.toggle("fullscreen-enable");
        if (
            !document.fullscreenElement &&
            /* alternative standard method */
            !document.mozFullScreenElement &&
            !document.webkitFullscreenElement
        ) {
            // current working methods
            if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
            } else if (document.documentElement.mozRequestFullScreen) {
            document.documentElement.mozRequestFullScreen();
            } else if (document.documentElement.webkitRequestFullscreen) {
            document.documentElement.webkitRequestFullscreen(
                Element.ALLOW_KEYBOARD_INPUT
            );
            }
        } else {
            if (document.cancelFullScreen) {
            document.cancelFullScreen();
            } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
            } else if (document.webkitCancelFullScreen) {
            document.webkitCancelFullScreen();
            }
        }
        },

        toggleDarkMode() {

        if (document.documentElement.getAttribute("data-bs-theme") == "dark") {
            document.documentElement.setAttribute("data-bs-theme", "light");
        } else {
            document.documentElement.setAttribute("data-bs-theme", "dark");
        }

        const mode = document.documentElement.getAttribute("data-bs-theme")
        this.changeMode({
            mode: mode,
        });
        },
        setDefaultImage(event) {
        event.target.src = '/images/avatars/avatar.jpg';
        },
        openInNewTab(url) {
            window.open(url, '_blank');
        },
    }
}
</script>