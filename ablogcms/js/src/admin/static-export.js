export default () => {
  if (!document.querySelector('#js-publish_result_tpl')) {
    return;
  }
  const resutlTemplate = _.template(document.querySelector('#js-publish_result_tpl').innerHTML);
  const errorTemplate = _.template(document.querySelector('#js-publish_error_tpl').innerHTML);
  const progressTemplate = _.template(document.querySelector('#js-publish_progress_tpl').innerHTML);
  const resultOut = document.querySelector('#js-result');
  const errorOut = document.querySelector('#js-error');
  const progress = document.querySelector('#js-publish_progress');
  const progressBar = progress.querySelector('.acms-admin-progress-bar');

  let errorCount = 0;
  const interval = setInterval(() => {
    const rand = Math.random().toString(36).slice(-16);

    $.getJSON(`${ACMS.Config.root}cache/${ACMS.Config.bid}_publish.json?${rand}`, (json) => {
      errorCount = 0;
      if (Array.isArray(json.processList)) {
        resultOut.innerHTML = resutlTemplate(json);
      }
      if (Array.isArray(json.errorList)) {
        errorOut.innerHTML = errorTemplate(json);
      }
      progressBar.style.width = `${json.percentage}%`;
      progressBar.querySelector('span').innerHTML = progressTemplate(json);
      progress.style.display = 'block';
    }).error(() => {
      errorCount++;
    });

    if (errorCount > 3) {
      clearInterval(interval);
      progress.style.display = 'none';
      document.querySelector('#js-publish_forced_termination').style.display = 'none';
    }
  }, 800);
};
