export const rewriteUrl = (uri: string) => {
  if (!uri) {
    return uri;
  }
  const key = 'acms-preview-mode';
  const regex = new RegExp(`([?&])${key}=.*?(&|$)`, 'i');
  const separator = uri.indexOf('?') !== -1 ? '&' : '?';
  let hash = '';
  if (uri.match(/#(.*)$/)) {
    uri = uri.replace(/#(.*)$/, (val1) => {
      hash = val1;
      return '';
    });
  }
  if (uri.match(regex)) {
    return uri.replace(regex, `$1${key}=${window.csrfToken}$2`);
  }
  return `${uri + separator + key}=${window.csrfToken}&timestamp=${new Date().getTime()}${hash}`;
};
