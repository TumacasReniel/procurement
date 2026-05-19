<template>
    <div class="app-pagination align-items-center justify-content-between d-flex">
        <div class="flex-shrink-0">
            <div class="text-muted">
                Showing
                <span class="fw-semibold">
                    {{ resultFrom }}-{{ resultTo }}
                </span>
                of
                <span class="fw-semibold">{{ totalResults }}</span>
                Results
            </div>
        </div>
        <ul class="pagination pagination-separated pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: !links.first }">
                <a class="page-link" href="#/" @click.prevent="fetch(links.first)" target="_self">first</a>
            </li>
            <li class="page-item" :class="{ disabled: !links.prev }">
                <a class="page-link" href="#/" @click.prevent="fetch(links.prev)" target="_self">&larr;</a>
            </li>
            <li class="page-item" :class="{ disabled: !links.next }">
                <a class="page-link" href="#/" @click.prevent="fetch(links.next)" target="_self">&rarr;</a>
            </li>
            <li class="page-item" :class="{ disabled: !links.last }">
                <a class="page-link" href="#/" @click.prevent="fetch(links.last)" target="_self">last</a>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    props: ['pagination', 'links', 'lists'],
    data() {
        return {
            count: 0
        };
    },
    computed: {
        currentPage() {
            return this.numberOrDefault(this.pagination?.current_page, 1);
        },
        perPage() {
            return this.numberOrDefault(this.pagination?.per_page, this.lists || 0);
        },
        lastPage() {
            return this.numberOrDefault(this.pagination?.last_page, 1);
        },
        totalResults() {
            return this.numberOrDefault(this.pagination?.total, this.lists || 0);
        },
        resultFrom() {
            if (this.totalResults <= 0 || this.perPage <= 0) {
                return 0;
            }

            return ((this.currentPage - 1) * this.perPage) + 1;
        },
        resultTo() {
            if (this.totalResults <= 0 || this.perPage <= 0) {
                return 0;
            }

            if (this.lastPage === this.currentPage) {
                return this.totalResults;
            }

            return Math.min(this.currentPage * this.perPage, this.totalResults);
        },
    },
    methods: {
        numberOrDefault(value, fallback = 0) {
            const number = Number(value);

            return Number.isFinite(number) ? number : fallback;
        },
        fetch(data) {
            if (!data) return;

            this.$emit('fetch', data);
        },
        next() {
            this.fetch();
        }
    }
};
</script>
