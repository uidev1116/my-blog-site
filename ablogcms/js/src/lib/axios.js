import axios from 'axios';

const axiosLib = axios.create();
axiosLib.interceptors.request.use((config) => {
  config.headers = {
    'X-Requested-With': 'XMLHttpRequest',
    'X-Csrf-Token': window.csrfToken || '',
  };
  return config;
});
export default axiosLib;
