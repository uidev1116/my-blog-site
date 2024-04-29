export default () => {
  const resultTplElement = document.querySelector<HTMLScriptElement>(
    '#js-publish_result_tpl',
  )
  if (resultTplElement === null) {
    return
  }

  const resultTemplateString = resultTplElement.innerHTML
  const errorTemplateString =
    document.querySelector<HTMLScriptElement>('#js-publish_error_tpl')
      ?.innerHTML ?? ''
  const progressTemplateString =
    document.querySelector<HTMLScriptElement>('#js-publish_progress_tpl')
      ?.innerHTML ?? ''
  const removedFilesTemplateString =
    document.querySelector<HTMLScriptElement>('#js-publish_removed_files_tpl')
      ?.innerHTML ?? ''
  const resutlTemplate = window._.template(resultTemplateString)
  const errorTemplate = window._.template(errorTemplateString)
  const removedFilesTemplate = window._.template(removedFilesTemplateString)
  const progressTemplate = window._.template(progressTemplateString)
  const resultOut = document.querySelector<HTMLElement>('#js-result')
  const errorOut = document.querySelector<HTMLElement>('#js-error')
  const removedFilesOut =
    document.querySelector<HTMLElement>('#js-removed-files')
  const progress = document.querySelector<HTMLDivElement>(
    '#js-publish_progress',
  )
  const progressBar = progress?.querySelector<HTMLDListElement>(
    '.acms-admin-progress-bar',
  )

  let errorCount = 0
  const interval = setInterval(() => {
    const rand = Math.random().toString(36).slice(-16)

    $.getJSON(
      `${ACMS.Config.root}cache/${ACMS.Config.bid}_publish.json?${rand}`,
      (json) => {
        errorCount = 0
        if (Array.isArray(json.processList) && resultOut !== null) {
          resultOut.innerHTML = resutlTemplate(json)
        }
        if (Array.isArray(json.errorList) && errorOut !== null) {
          errorOut.innerHTML = errorTemplate(json)
        }

        if (Array.isArray(json.removedFiles) && removedFilesOut !== null) {
          removedFilesOut.innerHTML = removedFilesTemplate(json)
        }

        if (progressBar != null) {
          progressBar.style.width = `${json.percentage}%`
          const span = progressBar.querySelector('span')
          if (span !== null) {
            span.innerHTML = progressTemplate(json)
          }
        }
        if (progress !== null) {
          progress.style.display = 'block'
        }
      },
    ).catch(() => {
      errorCount++
    })

    if (errorCount > 3) {
      clearInterval(interval)
      if (progress !== null) {
        progress.style.display = 'none'
      }
      const forceTerminationForm = document.querySelector<HTMLFormElement>(
        '#js-publish_forced_termination',
      )
      if (forceTerminationForm !== null) {
        forceTerminationForm.style.display = 'none'
      }
    }
  }, 800)
}
