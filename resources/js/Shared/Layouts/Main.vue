<template>
    <div>
        <Vertical v-if="layoutType === 'vertical' || layoutType === 'semibox'" :layout="layoutType">
            <slot />
        </Vertical>

        <Horizontal v-if="layoutType === 'horizontal'" :layout="layoutType">
            <slot />
        </Horizontal>

        <TwoColumns v-if="layoutType === 'twocolumn'" :layout="layoutType">
            <slot />
        </TwoColumns>
    </div>
    <b-modal v-model="flashMessageVisible" hide-footer class="v-modal-custom" modal-class="zoomIn" body-class="p-0" centered hide-header-close style="z-index: 5000;">
        <div class="text-end me-4">
            <button type="button" class="btn-close text-end" @click="check()"></button>
        </div>
        <div class="text-center px-5 pt-2">
            <div class="mt-2">
                 <div class="avatar-md mx-auto">
                    <div class="avatar-title rounded-circle bg-light">
                        <i v-if="$page.props.flash.status" class="ri-checkbox-circle-fill text-success h1 mb-0"></i>
                        <i v-else class="ri-close-circle-fill text-danger h1 mb-0"></i>
                    </div>
                </div>
                <h5 class="mb-1 mt-4 fs-14">{{$page.props.flash.message }}</h5>
                <p v-if="$page.props.flash.info" class="text-muted fs-12">{{$page.props.flash.info }}</p>
            </div>
        </div>
        <div class="modal-footer bg-light p-3 mt-5 justify-content-center">
            <p class="mb-0 text-muted fs-10">Any suggestions please contact
                <b-link href="fb.com/rjumli.gov" target="_blank" class="link-secondary fw-semibold">Administrator</b-link>
            </p>
        </div>
    </b-modal>
    <Survey v-if="showSurveyModal" v-model="surveyRequired" :questions="surveyQuestions" @success="handleSurveySubmit" />
    <Update v-if="showUpdateModal" v-model="updateRequired" @success="handleUpdateSubmit"/>
</template>
<script>
import { router } from "@inertiajs/vue3";
import { layoutComputed } from "@/Shared/State/helpers";
import Vertical from "./Vertical.vue";
import Horizontal from "./Horizontal.vue";
import TwoColumns from "./Twocolumn.vue";
import Survey from "./Components/Survey.vue";
import Update from "./Components/Update.vue";

export default {
    components: {
        Vertical,
        Horizontal,
        TwoColumns,
        Survey,
        Update,
    },
    provide() {
        return {
            mentionNotificationCenter: {
                state: this.mentionNotificationState,
                fetch: () => this.fetchMentionNotifications(),
                dismiss: (notificationId) => this.dismissMentionNotification(notificationId),
                open: (notification) => this.openMentionNotification(notification),
                truncate: (text, limit = 120) => this.truncateMentionText(text, limit),
                resolveAvatar: (notification) => this.resolveMentionAvatar(notification),
                resolveActorName: (notification) => this.resolveMentionActorName(notification),
                resolveHeadline: (notification) => this.resolveMentionHeadline(notification),
                resolveContextLabel: (notification) => this.resolveMentionContextLabel(notification),
                resolveSubject: (notification) => this.resolveMentionSubject(notification),
            },
        };
    },
    props: {
        surveyQuestions: Array,
    },
    data() {
        return {
            surveyRequired: false,
            updateRequired: false,
            flashMessageVisible: false,
            mentionNotificationState: {
                items: [],
                unreadCount: 0,
                hasMore: false,
                enabled: false,
            },
            mentionNotificationTimer: null,
        };
    },
    created() {
        this.updateRequired = this.$page.props.updateRequired;
        this.surveyRequired = this.$page.props.surveyRequired;
        this.flashMessageVisible = Boolean(this.$page.props.flash.message);
        this.hydrateMentionNotificationsFromProps(
            this.$page.props.procurement_mention_notification_feed,
        );
    },
    mounted() {
        this.syncMentionNotificationCenter();
        this.startMentionNotificationPolling();
        window.addEventListener("focus", this.handleWindowFocus);
        document.addEventListener("visibilitychange", this.handleVisibilityChange);
    },
    beforeUnmount() {
        this.stopMentionNotificationPolling();
        window.removeEventListener("focus", this.handleWindowFocus);
        document.removeEventListener("visibilitychange", this.handleVisibilityChange);
    },
    computed: {
        ...layoutComputed,
        showUpdateModal() {
            return this.updateRequired === true;
        },
        showSurveyModal() {
            return this.updateRequired === false && this.surveyRequired === true;
        },
        mentionNotificationsEnabled() {
            return Boolean(
                this.$page.props.features?.procurement_mention_notifications &&
                this.$page.props.user?.data?.id,
            );
        },
    },
    watch: {
        mentionNotificationsEnabled() {
            this.syncMentionNotificationCenter();

            if (this.mentionNotificationsEnabled) {
                this.startMentionNotificationPolling();
                this.fetchMentionNotifications();
                return;
            }

            this.stopMentionNotificationPolling();
            this.clearMentionNotifications();
        },
        "$page.props.procurement_mention_notification_feed": {
            deep: true,
            handler(feed) {
                this.hydrateMentionNotificationsFromProps(feed);
            },
        },
        "$page.props.flash.message"(value) {
            this.flashMessageVisible = Boolean(value);
        },
    },
    methods: {
        check() {
            this.$page.props.flash = {};
            this.flashMessageVisible = false;
        },
        handleUpdateSubmit() {
            this.updateRequired = false;
        },
        handleSurveySubmit() {
            this.surveyRequired = false;
        },
        truncateMentionText(text, limit = 120) {
            if (!text) {
                return "No comment preview available.";
            }

            return text.length > limit ? `${text.slice(0, limit)}...` : text;
        },
        syncMentionNotificationCenter() {
            this.mentionNotificationState.enabled = this.mentionNotificationsEnabled;
        },
        clearMentionNotifications() {
            this.mentionNotificationState.items = [];
            this.mentionNotificationState.unreadCount = 0;
            this.mentionNotificationState.hasMore = false;
        },
        resolveMentionUnreadCount(meta, fallbackItems = []) {
            const unreadCount = Number(meta?.unread_count);

            if (Number.isFinite(unreadCount) && unreadCount >= 0) {
                return unreadCount;
            }

            return Array.isArray(fallbackItems) ? fallbackItems.length : 0;
        },
        hydrateMentionNotificationsFromProps(feed) {
            if (!feed || typeof feed !== "object") {
                return;
            }

            this.mentionNotificationState.items = Array.isArray(feed.data)
                ? feed.data
                : [];
            this.mentionNotificationState.unreadCount = this.resolveMentionUnreadCount(
                feed.meta,
                this.mentionNotificationState.items,
            );
            this.mentionNotificationState.hasMore = Boolean(feed.meta?.has_more);
        },
        resolveMentionActorName(notification) {
            return notification?.actor?.name
                || notification?.actor?.username
                || notification?.mentioned_by?.name
                || notification?.mentioned_by?.username
                || "A teammate";
        },
        resolveMentionAvatar(notification) {
            return notification?.actor?.avatar
                || notification?.mentioned_by?.avatar
                || this.$page.props.user?.data?.avatar
                || "/images/avatars/avatar.jpg";
        },
        resolveMentionHeadline(notification) {
            const actor = this.resolveMentionActorName(notification);

            if (notification?.notification_type === "supplier_pending_approval") {
                return `${actor} submitted a supplier for approval`;
            }

            if (notification?.reason === "owner") {
                return `${actor} commented on your PR`;
            }

            return `${actor} mentioned you in a PR comment`;
        },
        resolveMentionContextLabel(notification) {
            return notification?.context_label || "PR comment";
        },
        resolveMentionSubject(notification) {
            return notification?.procurement_code
                || notification?.procurement_purpose
                || "Procurement Request";
        },
        startMentionNotificationPolling() {
            this.syncMentionNotificationCenter();

            if (!this.mentionNotificationsEnabled || this.mentionNotificationTimer) {
                return;
            }

            this.fetchMentionNotifications();
            this.mentionNotificationTimer = window.setInterval(() => {
                this.fetchMentionNotifications();
            }, 20000);
        },
        stopMentionNotificationPolling() {
            if (!this.mentionNotificationTimer) {
                return;
            }

            window.clearInterval(this.mentionNotificationTimer);
            this.mentionNotificationTimer = null;
        },
        handleWindowFocus() {
            this.fetchMentionNotifications();
        },
        handleVisibilityChange() {
            if (document.visibilityState === "visible") {
                this.fetchMentionNotifications();
            }
        },
        fetchMentionNotifications() {
            this.syncMentionNotificationCenter();

            if (!this.mentionNotificationsEnabled) {
                this.stopMentionNotificationPolling();
                this.clearMentionNotifications();
                return;
            }

            axios.get("/faims/procurement-mention-notifications", {
                params: {
                    limit: 6,
                },
            })
                .then((response) => {
                    this.mentionNotificationState.items = Array.isArray(response.data?.data)
                        ? response.data.data
                        : [];
                    this.mentionNotificationState.unreadCount = this.resolveMentionUnreadCount(
                        response.data?.meta,
                        this.mentionNotificationState.items,
                    );
                    this.mentionNotificationState.hasMore = Boolean(response.data?.meta?.has_more);
                })
                .catch((error) => {
                    console.log(error);

                    if (error?.response?.status === 401) {
                        this.clearMentionNotifications();
                    }
                });
        },
        markMentionNotificationRead(notificationId) {
            return axios.patch(`/faims/procurement-mention-notifications/${notificationId}/read`);
        },
        dismissMentionNotification(notificationId) {
            this.markMentionNotificationRead(notificationId)
                .finally(() => {
                    this.fetchMentionNotifications();
                });
        },
        openMentionNotification(notification) {
            if (!notification?.id) {
                return;
            }

            this.markMentionNotificationRead(notification.id)
                .finally(() => {
                    this.fetchMentionNotifications();

                    const targetRoute = notification?.target?.route || "/faims/procurements";
                    const targetQuery = {
                        ...(notification?.target?.query || {}),
                    };
                    const fallbackRequestId =
                        targetQuery?.comment_request_id
                        || targetQuery?.chat_request_id
                        || notification?.procurement_id;

                    if (!Object.keys(targetQuery).length && fallbackRequestId) {
                        targetQuery.comment_request_id = fallbackRequestId;
                    }

                    router.get(targetRoute, targetQuery);
                });
        },
    },
};
</script>
