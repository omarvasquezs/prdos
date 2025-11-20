<template>
  <div class="admin-layout">
    <TopBar />
    <slot />
  </div>
</template>

<script>
import TopBar from '@/components/TopBar.vue';

export default {
  name: 'AdminLayout',
  components: {
    TopBar
  }
}
</script>

<style scoped>
.admin-layout {
  min-height: 100vh;
  background: #f8f9fa;
}
</style>
