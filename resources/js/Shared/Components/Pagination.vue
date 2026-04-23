<template>
    <div class="align-items-center mt-4 justify-content-between d-flex">
        <div class="flex-shrink-0">
            <div class="text-muted">
                Showing
                <span class="fw-semibold">
                    {{ (pagination.current_page == 1) ? '1' : ((pagination.current_page - 1) * pagination.per_page) + 1 }}-{{ (pagination.last_page == pagination.current_page) ? pagination.total : pagination.current_page * pagination.per_page }}
                </span>
                of
                <span class="fw-semibold">{{ pagination.total }}</span>
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
    methods: {
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
