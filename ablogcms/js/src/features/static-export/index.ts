export default function dispatchStaticExport(context: Element | Document = document) {
  const resultTplElement = context.querySelector<HTMLScriptElement>('#js-publish_result_tpl');
  if (resultTplElement === null) {
    return;
  }

  const resultTemplateString = resultTplElement.innerHTML;
  const errorTemplateString = context.querySelector<HTMLScriptElement>('#js-publish_error_tpl')?.innerHTML ?? '';
  const progressTemplateString = context.querySelector<HTMLScriptElement>('#js-publish_progress_tpl')?.innerHTML ?? '';
  const removedFilesTemplateString =
    context.querySelector<HTMLScriptElement>('#js-publish_removed_files_tpl')?.innerHTML ?? '';
  const resutlTemplate = window._.template(resultTemplateString);
  const errorTemplate = window._.template(errorTemplateString);
  const removedFilesTemplate = window._.template(removedFilesTemplateString);
  const progressTemplate = window._.template(progressTemplateString);
  const resultOut = context.querySelector<HTMLElement>('#js-result');
  const errorOut = context.querySelector<HTMLElement>('#js-error');
  const removedFilesOut = context.querySelector<HTMLElement>('#js-removed-files');
  const progress = context.querySelector<HTMLDivElement>('#js-publish_progress');
  const progressBar = progress?.querySelector<HTMLDListElement>('.acms-admin-progress-bar');

  let errorCount = 0;
  const interval = setInterval(() => {
    const rand = Math.random().toString(36).slice(-16);

    $.getJSON(`${ACMS.Config.root}cache/${ACMS.Config.bid}_publish.json?${rand}`, (json) => {
      errorCount = 0;
      if (Array.isArray(json.processList) && resultOut !== null) {
        resultOut.innerHTML = resutlTemplate(json);
      }
      if (Array.isArray(json.errorList) && errorOut !== null) {
        errorOut.innerHTML = errorTemplate(json);
      }

      if (Array.isArray(json.removedFiles) && removedFilesOut !== null) {
        removedFilesOut.innerHTML = removedFilesTemplate(json);
      }

      if (progressBar != null) {
        progressBar.style.width = `${json.percentage}%`;
        const span = progressBar.querySelector('span');
        if (span !== null) {
          span.innerHTML = progressTemplate(json);
        }
      }
      if (progress !== null) {
        progress.style.display = 'block';
      }
    }).catch(() => {
      errorCount++;
    });

    if (errorCount > 3) {
      clearInterval(interval);
      if (progress !== null) {
        progress.style.display = 'none';
      }
      const forceTerminationForm = context.querySelector<HTMLFormElement>('#js-publish_forced_termination');
      if (forceTerminationForm !== null) {
        forceTerminationForm.style.display = 'none';
      }
    }
  }, 800);
}
