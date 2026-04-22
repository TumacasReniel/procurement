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

                    <div class="ms-1 header-item d-none d-sm-flex">
                        <BDropdown
                            class="dropdown topbar-head-dropdown"
                            variant="ghost-secondary"
                            no-caret
                            menu-class="p-0 dropdown-menu-end dropdown-menu-lg mention-notification-menu"
                            toggle-class="btn-icon btn-topbar rounded-circle material-shadow-none position-relative"
                            @show="refreshMentionNotifications"
                        >
                            <template #button-content>
                                <i class="bx bx-bell fs-22"></i>
                                <span
                                    v-if="mentionNotificationState.unreadCount"
                                    class="position-absolute translate-middle badge rounded-pill bg-danger topbar-badge"
                                >
                                    {{ mentionNotificationBadge }}
                                </span>
                            </template>

                            <div class="p-3 border-top-0 border-start-0 border-end-0 border-dashed border">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div>
                                        <h6 class="mb-1 fw-semibold fs-15">Notifications</h6>
                                        <p class="mb-0 text-muted fs-12"> alerts show up here in requests you are tagged or mention.</p>
                                    </div>
                                    <span class="badge bg-danger text-white fs-11">
                                        {{ mentionNotificationState.unreadCount }}
                                    </span>
                                </div>
                            </div>

                            <simplebar v-if="mentionNotificationItems.length" class="mention-notification-scroll">
                                <div class="p-2">
                                    <div
                                        v-for="notification in mentionNotificationItems"
                                        :key="notification.id"
                                        class="dropdown-item notification-item mention-notification-item"
                                        role="button"
                                        @click="openMentionNotification(notification)"
                                    >
                                        <div class="d-flex align-items-start gap-3">
                                            <img
                                                :src="resolveMentionAvatar(notification)"
                                                :alt="resolveMentionActorName(notification)"
                                                class="rounded-circle border mention-notification-avatar flex-shrink-0"
                                            />
                                            <div class="flex-grow-1 mention-notification-content">
                                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                                    <div class="mention-notification-content">
                                                        <div class="mention-notification-label mb-1">
                                                            {{ resolveMentionContextLabel(notification) }}
                                                        </div>
                                                        <div class="fw-semibold fs-13 text-truncate">
                                                            {{ resolveMentionHeadline(notification) }}
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-icon btn-ghost-secondary mention-notification-dismiss"
                                                        @click.stop="dismissMentionNotification(notification.id)"
                                                    >
                                                        <i class="ri-close-line fs-16"></i>
                                                    </button>
                                                </div>
                                                <div class="text-muted fs-12 mb-2 text-truncate">
                                                    {{ resolveMentionSubject(notification) }}
                                                </div>
                                                <p class="mb-2 fs-12 text-body mention-notification-preview">
                                                    {{ truncateMentionText(notification.comment_content, 96) }}
                                                </p>
                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                    <span class="text-muted fs-11">
                                                        {{ notification.created_ago || "Just now" }}
                                                    </span>
                                                    <span class="badge bg-light text-body">Open PR chat</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </simplebar>

                            <div v-else class="px-4 py-5 text-center mention-notification-empty">
                                <div class="avatar-sm mx-auto mb-3">
                                    <div class="avatar-title bg-light text-primary rounded-circle fs-20">
                                        <i class="ri-notification-3-line"></i>
                                    </div>
                                </div>
                                <h6 class="mb-1 fw-semibold">No new PR alerts</h6>
                                <p class="mb-0 text-muted fs-12">Mentions and updates on your procurement requests will appear here.</p>
                            </div>

                            <div class="mention-notification-footer">
                                <div class="d-flex align-items-center justify-content-between gap-3">
                                    <small class="text-muted">
                                        {{ mentionNotificationState.hasMore ? "Showing the latest unread PR alerts." : "This updates automatically." }}
                                    </small>
                                    <button type="button" class="btn btn-sm btn-light" @click="refreshMentionNotifications">
                                        Refresh
                                    </button>
                                </div>
                            </div>
                        </BDropdown>
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
    inject: {
        mentionNotificationCenter: {
            default: null,
        },
    },
    components: { simplebar },
    data() {
        return {
        text: null,
        value: null,
        myVar: 1,
        };
    },
    computed: {
        mentionNotificationState() {
            return this.mentionNotificationCenter?.state ?? {
                items: [],
                unreadCount: 0,
                hasMore: false,
                enabled: false,
            };
        },
        mentionNotificationItems() {
            return Array.isArray(this.mentionNotificationState?.items)
                ? this.mentionNotificationState.items
                : [];
        },
        mentionNotificationsEnabled() {
            return Boolean(this.mentionNotificationState?.enabled);
        },
        mentionNotificationBadge() {
            const unreadCount = Number(this.mentionNotificationState?.unreadCount) || 0;

            return unreadCount > 99 ? "99+" : unreadCount;
        },
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
        refreshMentionNotifications() {
            this.mentionNotificationCenter?.fetch?.();
        },
        dismissMentionNotification(notificationId) {
            this.mentionNotificationCenter?.dismiss?.(notificationId);
        },
        openMentionNotification(notification) {
            this.mentionNotificationCenter?.open?.(notification);
        },
        truncateMentionText(text, limit = 96) {
            if (this.mentionNotificationCenter?.truncate) {
                return this.mentionNotificationCenter.truncate(text, limit);
            }

            if (!text) {
                return "No comment preview available.";
            }

            return text.length > limit ? `${text.slice(0, limit)}...` : text;
        },
        resolveMentionActorName(notification) {
            return this.mentionNotificationCenter?.resolveActorName?.(notification)
                || notification?.actor?.name
                || notification?.actor?.username
                || notification?.mentioned_by?.name
                || notification?.mentioned_by?.username
                || "A teammate";
        },
        resolveMentionAvatar(notification) {
            return this.mentionNotificationCenter?.resolveAvatar?.(notification)
                || notification?.actor?.avatar
                || notification?.mentioned_by?.avatar
                || this.$page.props.user?.data?.avatar
                || "/images/avatars/avatar.jpg";
        },
        resolveMentionHeadline(notification) {
            if (this.mentionNotificationCenter?.resolveHeadline) {
                return this.mentionNotificationCenter.resolveHeadline(notification);
            }

            const actor = this.resolveMentionActorName(notification);

            if (notification?.reason === "owner") {
                return `${actor} commented on your PR`;
            }

            return `${actor} mentioned you in a PR comment`;
        },
        resolveMentionContextLabel(notification) {
            return this.mentionNotificationCenter?.resolveContextLabel?.(notification)
                || notification?.context_label
                || "PR comment";
        },
        resolveMentionSubject(notification) {
            return this.mentionNotificationCenter?.resolveSubject?.(notification)
                || notification?.procurement_code
                || notification?.procurement_purpose
                || "Procurement Request";
        },
    }
}
</script>
<style scoped>
.mention-notification-menu {
    width: min(26rem, calc(100vw - 2rem));
}

.mention-notification-scroll {
    max-height: 22rem;
}

.mention-notification-item {
    border-radius: 0.85rem;
    transition: background-color 0.2s ease, transform 0.2s ease;
}

.mention-notification-item:hover {
    background: rgba(var(--vz-primary-rgb), 0.08);
    transform: translateY(-1px);
}

.mention-notification-avatar {
    width: 42px;
    height: 42px;
    object-fit: cover;
    background: var(--vz-tertiary-bg);
}

.mention-notification-content {
    min-width: 0;
}

.mention-notification-label {
    display: inline-flex;
    align-items: center;
    padding: 0.18rem 0.5rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    background: rgba(var(--vz-primary-rgb), 0.12);
    color: var(--vz-primary);
}

.mention-notification-preview {
    white-space: normal;
    word-break: break-word;
}

.mention-notification-dismiss {
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 999px;
    border: 0;
    background: transparent;
    color: var(--vz-secondary-color);
}

.mention-notification-dismiss:hover {
    background: rgba(var(--vz-primary-rgb), 0.1);
    color: var(--vz-primary);
}

.mention-notification-empty {
    color: var(--vz-secondary-color);
}

.mention-notification-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--vz-border-color);
    background: var(--vz-secondary-bg);
}
</style>
