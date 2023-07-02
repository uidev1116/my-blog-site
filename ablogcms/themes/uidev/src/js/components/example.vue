<template>
  <section>
    <h1><i class="fab fa-vuejs" /> Vue example</h1>

    <p>{{ count }}</p>
    <button @click="plus">
      <i class="fas fa-plus" /> plus
    </button>
  </section>
</template>

<script>
import Vue from 'vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faPlus } from '@fortawesome/free-solid-svg-icons';
import { faVuejs } from '@fortawesome/free-brands-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { store } from '../containers/store';

library.add(faPlus, faVuejs);
Vue.component('FontAwesomeIcon', FontAwesomeIcon);

export default {
  name: 'Example',
  data() {
    return {
      store,
      count: 0,
    };
  },
  methods: {
    plus() {
      this.count += 1;
    },
  },
};
</script>

<style scoped>
.sample {
  font-size: 20px;
}
</style>
