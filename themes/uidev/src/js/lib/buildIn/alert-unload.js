export default (target) => {
  const onBeforeunloadHandler = (e) => {
    e.returnValue = '入力途中のデータがあります';
  };
  window.addEventListener('beforeunload', onBeforeunloadHandler, false);

  if (target) {
    target.addEventListener('submit', () => {
      window.removeEventListener('beforeunload', onBeforeunloadHandler, false);
    });
  }
};
