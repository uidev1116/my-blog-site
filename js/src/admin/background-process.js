const check = (jsonFileName, target, interval) => {
  const rand = Math.random().toString(36).slice(-16);
  const template = target.querySelector('.js-processing-template').innerText;
  const box = target.querySelector('.js-processing-box');
  const progress = target.querySelector('.js-progress');
  const progressBar = progress.querySelector('.acms-admin-progress-bar');
  const $progressBar = $(progressBar);
  const progressMessage = progress.querySelector('span');

  $.getJSON(`${ACMS.Config.root}cache/${jsonFileName}?${rand}`, (json) => {
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

export default (selector, jsonName, interval = 1000) => {
  const progress = document.querySelector(selector);
  if (progress) {
    const i = setInterval(() => {
      check(jsonName, progress, i);
    }, interval);
  }
};
