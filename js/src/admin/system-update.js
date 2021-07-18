export default () => {
  const systemUpdate = document.querySelector('#js-systemUpdate');
  const submitForm = document.querySelector('.js-system-update-submit');
  if (submitForm) {
    submitForm.addEventListener('submit', () => {
      setTimeout(() => {
        window.location.replace(window.location.href);
      }, 5000);
    });
  }
  if (systemUpdate) {
    const template = systemUpdate.querySelector('#js-processing-template').innerText;
    const box = systemUpdate.querySelector('#js-processing-box');
    const progress = systemUpdate.querySelector('#js-progress');
    const progressBar = progress.querySelector('.acms-admin-progress-bar');
    const $progressBar = $(progressBar);
    const progressMessage = progress.querySelector('span');
    let interval;

    const check = () => {
      const rand = Math.random().toString(36).slice(-16);
      $.getJSON(`${ACMS.Config.root}cache/update-process.json?${rand}`, (json) => {
        const engine = _.template(template);
        box.innerHTML = engine(json);

        if (json.processing) {
          progress.style.display = '';
          if (json.error) {
            $progressBar.css('width', '100%');
            $progressBar.addClass('acms-admin-progress-bar-danger');
            $progressBar.removeClass('acms-admin-progress-bar-info');
            progressMessage.innerHTML = json.error;
          } else {
            $progressBar.css('width', `${json.percentage}%`);
            $progressBar.addClass('acms-admin-progress-bar-info');
            $progressBar.removeClass('acms-admin-progress-bar-danger');
            progressMessage.innerHTML = json.inProcess;
          }
        } else {
          progress.style.display = 'none';
        }
      }).error(() => {
        clearInterval(interval);
      });
    };

    interval = setInterval(() => {
      check();
    }, 1000);
  }
};
