<template>
  <AppPlanView :ppmp="ppmp" :dropdowns="dropdowns" plan-kind="SPP" />
</template>

<script>
import AppPlanView from "./AppPlanView.vue";

export default {
  components: { AppPlanView },
  props: {
    ppmp: { type: Object, required: true },
    dropdowns: { type: Object, default: () => ({}) },
  },
};
</script>
