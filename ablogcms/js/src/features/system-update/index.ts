export default function dispatchSystemUpdate(context: Element | Document = document) {
  const systemUpdate = context.querySelector<HTMLElement>('#js-systemUpdate');
  const submitForm = context.querySelector<HTMLFormElement>('.js-system-update-submit');
  if (submitForm) {
    submitForm.addEventListener('submit', () => {
      setTimeout(() => {
        window.location.replace(window.location.href);
      }, 5000);
    });
  }
  if (systemUpdate) {
    const template = systemUpdate.querySelector<HTMLScriptElement>('#js-processing-template')?.innerText || '';
    const box = systemUpdate.querySelector<HTMLElement>('#js-processing-box');
    const progress = systemUpdate.querySelector<HTMLElement>('#js-progress');
    if (!box || !template || !progress) {
      return;
    }
    const progressBar = progress.querySelector<HTMLElement>('.acms-admin-progress-bar');
    if (!progressBar) {
      return;
    }
    const progressMessage = progress.querySelector('span');
    let interval: NodeJS.Timeout;

    const check = () => {
      const rand = Math.random().toString(36).slice(-16);
      $.getJSON(`${ACMS.Config.root}cache/update-process.json?${rand}`, (json) => {
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

    interval = setInterval(() => {
      check();
    }, 1000);
  }
}
