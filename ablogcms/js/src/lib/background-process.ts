const check = (jsonFileName: string, element: HTMLElement, interval: NodeJS.Timeout) => {
  const rand = Math.random().toString(36).slice(-16);
  const template = element.querySelector<HTMLScriptElement>('.js-processing-template')?.innerText;
  const box = element.querySelector<HTMLElement>('.js-processing-box');
  const progress = element.querySelector<HTMLElement>('.js-progress');
  if (!box || !template || !progress) {
    return;
  }
  const progressBar = progress.querySelector<HTMLElement>('.acms-admin-progress-bar');

  if (!progressBar) {
    return;
  }
  const progressMessage = progress.querySelector('span');

  $.getJSON(`${ACMS.Config.root}cache/${jsonFileName}?${rand}`, (json) => {
    const engine = window._.template(template);
    box.innerHTML = engine(json);

    if (json.processing) {
      progress.style.display = '';
      if (json.error) {
        progressBar.style.width = '100%';
        progressBar.classList.add('acms-admin-progress-bar-danger');
        progressBar.classList.remove('acms-admin-progress-bar-info');
        if (progressMessage) {
          progressMessage.innerHTML = json.error;
        }
      } else {
        progressBar.style.width = `${json.percentage}%`;
        progressBar.classList.add('acms-admin-progress-bar-info');
        progressBar.classList.remove('acms-admin-progress-bar-danger');
        if (progressMessage) {
          progressMessage.innerHTML = json.inProcess;
        }
      }
    } else {
      progress.style.display = 'none';
    }
  }).catch(() => {
    clearInterval(interval);
  });
};

export default (selector: string, jsonName: string, interval = 1000) => {
  const progress = document.querySelector<HTMLElement>(selector);
  if (progress) {
    const i = setInterval(() => {
      check(jsonName, progress, i);
    }, interval);
  }
};
