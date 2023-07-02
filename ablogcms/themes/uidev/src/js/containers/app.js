import Vue from 'vue';
import App from '../components/example.vue';

export default () => {
  // eslint-disable-next-line no-new
  new Vue({
    el: '#js-vue-example',
    components: { App },
    template: '<App/>',
  });
};
