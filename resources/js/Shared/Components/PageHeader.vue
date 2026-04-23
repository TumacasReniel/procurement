<script>
    export default {
        components: {},
        props: {
            title: {
                type: String,
                default: "",
            },
            pageTitle: {
                type: String,
                default: "",
            }
        },
    };

</script>
<template>
    <BRow>
        <BCol cols="12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ title }}</h4>
                <div v-if="$slots.actions || pageTitle" class="page-title-right">
                    <slot name="actions"></slot>
                    <ol class="breadcrumb m-0">
                        <li v-if="pageTitle" class="breadcrumb-item">
                            <BLink href="javascript: void(0);">{{ pageTitle }}</BLink>
                        </li>
                        <li v-if="pageTitle" class="breadcrumb-item active">{{ title }}</li>
                    </ol>
                </div>
            </div>
        </BCol>
    </BRow>
</template>
