import backgroundProcess from '../../lib/background-process';

export default function dispatchCsvImport(context: Element | Document = document) {
  const element = context.querySelector('#js-background-csv-import');
  if (!element) {
    return;
  }
  const json = element.getAttribute('data-json') || '';
  backgroundProcess('#js-background-csv-import', json, 1000);
}
